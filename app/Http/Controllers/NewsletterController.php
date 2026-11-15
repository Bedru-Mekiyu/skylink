<?php

// app/Http/Controllers/NewsletterController.php

namespace App\Http\Controllers;

use App\Mail\NewsLetterMail;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Newsletter sending, admin only.
 *
 * The old public `GET /send-newsletter` trigger is gone. Sending now needs an
 * admin session, a POST with a CSRF token, an explicit confirmation checkbox,
 * and is rate limited (see routes/web.php).
 */
class NewsletterController extends Controller
{
    /**
     * Confirmation page. Shows how many subscribers would receive the mail;
     * sends nothing.
     */
    public function confirm()
    {
        return view('admin.newsletter.confirm', [
            'subscriberCount' => Subscriber::count(),
        ]);
    }

    /**
     * Send the newsletter to every subscriber.
     */
    public function send(Request $request)
    {
        $request->validate([
            'confirm' => ['accepted'],
        ]);

        $content = "Here's the latest news..."; // You can make this dynamic

        $sent = 0;
        foreach (Subscriber::query()->cursor() as $subscriber) {
            Mail::to($subscriber->email)->send(new NewsLetterMail($content, $subscriber));
            $sent++;
        }

        // Audit trail without personal data.
        Log::info('Newsletter sent.', [
            'recipients' => $sent,
            'by_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.newsletter.confirm')
            ->with('success', "Newsletter sent to {$sent} subscribers.");
    }
}
