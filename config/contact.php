<?php

/*
| Where website messages go.
|
| ONE setting decides it: CONTACT_INBOX in .env. Every form type (contact,
| support, quote, service request, plan subscription) is emailed there.
|
| QUOTE_INBOX and SUPPORT_INBOX are optional. Left empty (or missing), they
| fall back to CONTACT_INBOX, so changing that one line changes everything.
| If CONTACT_INBOX itself is missing, the site's sender address
| (MAIL_FROM_ADDRESS) is used, so messages are never dropped silently.
|
| After changing .env on the server, clear the config cache
| (php artisan config:clear); see the release runbook.
*/

$inbox = env('CONTACT_INBOX') ?: env('MAIL_FROM_ADDRESS');
$quoteInbox = env('QUOTE_INBOX') ?: $inbox;

return [
    'inbox' => $inbox,

    // Per form type. Types not listed here use 'inbox'.
    'inboxes' => [
        'contact' => $inbox,
        'subscription' => $inbox,
        'quote' => $quoteInbox,
        'service' => $quoteInbox,
        'support' => env('SUPPORT_INBOX') ?: $inbox,
    ],

    // Send the visitor a short "we received your message" email.
    'confirm_visitor' => (bool) env('CONTACT_CONFIRM_VISITOR', true),

    // Messages older than this many months are anonymised by the monthly
    // `inquiries:anonymize` task. 0 switches it off.
    'retention_months' => (int) env('CONTACT_RETENTION_MONTHS', 24),
];
