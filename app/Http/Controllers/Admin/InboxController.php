<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Admin inbox: every message sent through a website form (admin only, see
 * routes/web.php). Opening a message marks it read.
 */
class InboxController extends Controller
{
    public function index(Request $request)
    {
        $type = array_key_exists($request->query('type'), Inquiry::TYPES) ? $request->query('type') : null;
        $status = array_key_exists($request->query('status'), Inquiry::STATUSES) ? $request->query('status') : null;
        $unread = $request->boolean('unread');
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);

        $inquiries = Inquiry::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($unread, fn ($q) => $q->unread())
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('message', 'like', $like));
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inbox.index', [
            'inquiries' => $inquiries,
            'type' => $type,
            'status' => $status,
            'unread' => $unread,
            'search' => $search,
            'unreadCount' => Inquiry::unread()->count(),
            'inbox' => config('contact.inbox'),
        ]);
    }

    /** Mark every unread message (only those the inbox lists: trashed ones are excluded by SoftDeletes) as read. */
    public function readAll(): RedirectResponse
    {
        $count = Inquiry::unread()->update(['read_at' => now()]);

        return back()->with('success', $count === 1 ? '1 message marked as read.' : "{$count} messages marked as read.");
    }

    public function show(Inquiry $inquiry)
    {
        if ($inquiry->read_at === null) {
            $inquiry->forceFill(['read_at' => now()])->saveQuietly();
        }

        return view('admin.inbox.show', [
            'inquiry' => $inquiry,
            'teamInbox' => Inquiry::inboxFor($inquiry->type),
        ]);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Inquiry::STATUSES))],
        ]);
        $inquiry->forceFill(['status' => $data['status']])->save();

        return back()->with('success', "Status set to \"{$inquiry->status_label}\".");
    }

    /** Send the team email again (e.g. after the inbox address was fixed). */
    public function resend(Inquiry $inquiry): RedirectResponse
    {
        $inbox = Inquiry::inboxFor($inquiry->type);
        if (! $inbox || ! filter_var($inbox, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'No valid team inbox is configured (CONTACT_INBOX in .env).');
        }

        try {
            $inquiry->forceFill(['team_notify_error' => null])->saveQuietly();
            Mail::to($inbox)->queue(new InquiryReceived($inquiry));
        } catch (Throwable $e) {
            $inquiry->forceFill(['team_notify_error' => mb_substr(class_basename($e).': '.$e->getMessage(), 0, 255)])->saveQuietly();

            return back()->with('error', 'The email could not be queued. See the delivery status below.');
        }

        return back()->with('success', "The message was queued again for {$inbox}.");
    }

    /** Hidden from the inbox (soft delete); kept in the database. */
    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return redirect()->route('admin.inbox.index')->with('success', "Message {$inquiry->reference} deleted.");
    }
}
