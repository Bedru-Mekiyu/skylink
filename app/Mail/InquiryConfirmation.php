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
 * To the visitor: "we received your message", in Amharic and English, with the
 * reference number. Replying reaches the team inbox.
 *
 * Deliberately does NOT repeat the visitor's own text or name: anyone can type
 * any email address into a form, and an echo would let strangers use this site
 * to send their text to others. (Also limited by the public-forms rate limit.)
 */
class InquiryConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [60, 300, 900];

    public string $mailKind = 'visitor';

    public function __construct(public Inquiry $inquiry)
    {
        // Queue only after the inquiry row is committed.
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $inbox = Inquiry::inboxFor($this->inquiry->type);

        return new Envelope(
            replyTo: $inbox ? [new Address($inbox, config('mail.from.name'))] : [],
            subject: sprintf('መልዕክትዎ ደርሶናል / We received your message (%s)', $this->inquiry->reference),
        );
    }

    public function headers(): Headers
    {
        // Ask auto-responders not to answer this automatic email.
        return new Headers(text: [
            'X-Inquiry-Reference' => $this->inquiry->reference,
            'Auto-Submitted' => 'auto-generated',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiries.confirmation',
            text: 'emails.inquiries.confirmation-text',
        );
    }

    public function failed(Throwable $e): void
    {
        $this->inquiry->forceFill(['visitor_confirm_error' => mb_substr(class_basename($e).': '.$e->getMessage(), 0, 255)])->saveQuietly();
    }
}
