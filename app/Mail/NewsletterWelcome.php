<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * To a new newsletter subscriber: "you are on the list", in Amharic or English (the visitor's browser language),
 * with their personal signed unsubscribe link. Sent once, when the address is first added.
 */
class NewsletterWelcome extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [60, 300, 900];

    public string $unsubscribeUrl;

    public function __construct(public Subscriber $subscriber, public string $language = 'en')
    {
        $this->afterCommit();
        $this->unsubscribeUrl = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->language === 'am' ? 'ወደ ስካይሊንክ ጋዜጣ ተመዝግበዋል' : 'You are on the Sky Link newsletter',
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
            'Auto-Submitted' => 'auto-generated',
        ]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.newsletter-welcome', text: 'emails.newsletter-welcome-text');
    }
}
