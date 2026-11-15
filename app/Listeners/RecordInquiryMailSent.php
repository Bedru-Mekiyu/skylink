<?php

namespace App\Listeners;

use App\Models\Inquiry;
use Illuminate\Mail\Events\MessageSent;

/**
 * When an inquiry email has actually been handed to the mail server, record
 * it on the inquiry, so the admin inbox shows "emailed" / "confirmation sent".
 * (Auto-discovered by Laravel from app/Listeners.)
 */
class RecordInquiryMailSent
{
    public function handle(MessageSent $event): void
    {
        $inquiry = $event->data['inquiry'] ?? null;
        if (! $inquiry instanceof Inquiry) {
            return;
        }

        $column = ($event->data['mailKind'] ?? null) === 'visitor' ? 'visitor_confirmed_at' : 'team_notified_at';
        $error = $column === 'visitor_confirmed_at' ? 'visitor_confirm_error' : 'team_notify_error';

        Inquiry::withTrashed()->whereKey($inquiry->id)->update([$column => now(), $error => null]);
    }
}
