<?php

namespace App\Support;

use App\Mail\InquiryConfirmation;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Queues the two emails for a saved inquiry:
 *  - to the team inbox (config/contact.php -> CONTACT_INBOX), Reply-To = visitor;
 *  - to the visitor ("we received your message"), Reply-To = team inbox.
 *
 * Never throws: the message is already saved and visible in the admin inbox,
 * so a mail problem must not turn into an error page for the visitor. Problems
 * are recorded on the inquiry (team_notify_error / visitor_confirm_error) and
 * logged without personal data.
 */
class InquiryNotifier
{
    public function notify(Inquiry $inquiry): void
    {
        $inbox = Inquiry::inboxFor($inquiry->type);

        if (! $inbox || ! filter_var($inbox, FILTER_VALIDATE_EMAIL)) {
            $this->record($inquiry, 'team_notify_error', 'No valid team inbox configured (CONTACT_INBOX).');
        } else {
            $this->queue($inquiry, fn () => Mail::to($inbox)->queue(new InquiryReceived($inquiry)), 'team_notify_error');
        }

        if (config('contact.confirm_visitor')) {
            $this->queue($inquiry, fn () => Mail::to($inquiry->email, $inquiry->name)->queue(new InquiryConfirmation($inquiry)), 'visitor_confirm_error');
        }
    }

    private function queue(Inquiry $inquiry, callable $send, string $errorColumn): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            // With QUEUE_CONNECTION=sync the mail is sent right here, so a mail
            // server problem surfaces here; with a real queue, Mailable::failed().
            $this->record($inquiry, $errorColumn, class_basename($e).': '.mb_substr($e->getMessage(), 0, 180));
        }
    }

    private function record(Inquiry $inquiry, string $column, string $error): void
    {
        $inquiry->forceFill([$column => mb_substr($error, 0, 255)])->saveQuietly();
        Log::warning('Inquiry email could not be queued or sent.', [
            'inquiry_id' => $inquiry->id,
            'which' => $column,
            'error' => mb_substr($error, 0, 120),
        ]);
    }
}
