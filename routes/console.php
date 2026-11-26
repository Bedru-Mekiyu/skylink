<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Marks scheduled news as published once their time has passed. Pages already
// check the time on every request, so this is a tidy-up, not a requirement.
// Needs the cron line from the deploy checklist (php artisan schedule:run).
\Illuminate\Support\Facades\Schedule::command('news:publish-scheduled')->everyMinute()->withoutOverlapping();

// Duplicate news check: DRY RUN ONLY. Writes a report the admin sees on the
// News list (storage/app/private/reports/). Never add --apply here: hiding
// duplicates stays a manual, reviewed step (php artisan news:clean-duplicates --apply).
\Illuminate\Support\Facades\Schedule::command('news:clean-duplicates --report')->dailyAt('02:00')->withoutOverlapping();

// Website messages: the emails are queued (database queue), so a slow or
// failing mail server never affects the visitor. This sends them, retrying
// failures. It runs from the same cron line (php artisan schedule:run).
// Without any cron on the host, set QUEUE_CONNECTION=sync in .env instead
// (mail is then sent during the request; failures are still caught and shown
// in the admin inbox).
\Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=4')->everyMinute()->withoutOverlapping();

// Privacy: anonymise website messages older than CONTACT_RETENTION_MONTHS (default 24).
\Illuminate\Support\Facades\Schedule::command('inquiries:anonymize')->monthlyOn(1, '03:00');

// Recurring plans: create the next order and email its payment link. Does nothing unless RENEWAL_REMINDERS_ENABLED=true.
\Illuminate\Support\Facades\Schedule::command('billing:renew')->dailyAt('08:00')->withoutOverlapping();
