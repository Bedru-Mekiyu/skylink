<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * To the team inbox: the full message. "Reply" answers the visitor directly.
 * Queued with retries (1, 5 and 15 minutes), so a short mail outage loses nothing.
 */
class InquiryReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [60, 300, 900];

    /** Read by App\Listeners\RecordInquiryMailSent. */
    public string $mailKind = 'team';

    public function __construct(public Inquiry $inquiry)
    {
        // Queue only after the inquiry row is committed.
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->inquiry->email, $this->inquiry->name)],
            subject: sprintf('[Website] %s from %s (%s)', $this->inquiry->type_label, $this->inquiry->name, $this->inquiry->reference),
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: ['X-Inquiry-Reference' => $this->inquiry->reference]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiries.received',
            text: 'emails.inquiries.received-text',
        );
    }

    /** All retries failed: show it in the admin inbox. */
    public function failed(Throwable $e): void
    {
        $this->inquiry->forceFill(['team_notify_error' => mb_substr(class_basename($e).': '.$e->getMessage(), 0, 255)])->saveQuietly();
    }
}
