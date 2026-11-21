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

    /** PERMANENT delete: the message row is removed from the database (download the CSV first if you need a copy). */
    public function destroy(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $id = $inquiry->id;
        $inquiry->forceDelete();
        \Illuminate\Support\Facades\Log::notice('inbox message deleted', ['user_id' => $request->user()->id, 'inquiry_id' => $id]);

        return redirect()->route('admin.inbox.index')->with('success', 'Message deleted.');
    }

    /** CSV of all inbox messages (or only the ticked ones: ?ids[]=). Spreadsheet formulas are neutralised. */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $ids = array_filter((array) $request->query('ids', []), 'is_numeric');
        $cols = ['Received' => 'created_at', 'Type' => 'type', 'Name' => 'name', 'Email' => 'email', 'Phone' => 'phone', 'Company' => 'company', 'Service' => 'service', 'Plan' => 'plan', 'Message' => 'message', 'Language' => 'language'];

        return response()->streamDownload(function () use ($ids, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_keys($cols));
            Inquiry::query()->when($ids, fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->chunk(500, function ($rows) use ($out, $cols) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map(fn ($a) => preg_match('/^[=+\-@\t\r]/', (string) $r->{$a}) ? "'".$r->{$a} : (string) $r->{$a}, array_values($cols)));
                }
            });
            fclose($out);
        }, 'inbox-messages-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** "Delete selected": soft-deletes every ticked message. Unknown or already deleted ids are ignored. */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'Tick at least one message to delete.', 'ids.min' => 'Tick at least one message to delete.']);

        $count = 0;
        foreach (Inquiry::whereIn('id', $data['ids'])->get() as $inquiry) {
            $inquiry->forceDelete();
            $count++;
        }
        \Illuminate\Support\Facades\Log::notice('inbox messages bulk deleted', ['user_id' => $request->user()->id, 'count' => $count]);

        return redirect()->route('admin.inbox.index')->with('success', $count === 1 ? 'Message deleted.' : "{$count} messages deleted.");
    }
}
