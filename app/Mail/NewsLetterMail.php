<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewsLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $content; // data passed to the email

    /** Personal, signed unsubscribe link (null when no subscriber is given). */
    public ?string $unsubscribeUrl = null;

    public function __construct($content, ?Subscriber $subscriber = null)
    {
        $this->content = $content;
        if ($subscriber) {
            $this->unsubscribeUrl = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]);
        }
    }

    public function build()
    {
        return $this->view('emails.newsletter')
                    ->with(['content' => $this->content, 'unsubscribeUrl' => $this->unsubscribeUrl]);
    }

    /** Lets mail apps show their own "Unsubscribe" button. */
    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl ? ['List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>'] : []);
    }
}
