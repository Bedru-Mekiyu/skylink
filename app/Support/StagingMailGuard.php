<?php

namespace App\Support;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

/**
 * TEST-SITE ONLY safety net (MAIL_STAGING_GUARD=true in that site's .env; never set on the live site).
 *
 * The test site holds a copy of the real database (real customers, real subscribers). With the guard on, an email may
 * only reach the company inboxes (CONTACT_INBOX, QUOTE_INBOX, SUPPORT_INBOX) and the addresses in
 * MAIL_STAGING_ALLOWLIST. Every other recipient (To, Cc, Bcc) is replaced by MAIL_STAGING_REDIRECT, and the subject says
 * who it was meant for. If no redirect address is set, such an email is not sent at all.
 */
class StagingMailGuard
{
    public static function enabled(): bool
    {
        return (bool) config('mail.staging_guard.enabled');
    }

    /** @return string[] lower-case addresses that may receive mail while the guard is on */
    public static function allowed(): array
    {
        $company = array_merge([config('contact.inbox')], array_values((array) config('contact.inboxes')));

        return array_values(array_unique(array_filter(array_map(
            fn ($a) => strtolower(trim((string) $a)),
            array_merge($company, (array) config('mail.staging_guard.allowlist'))
        ))));
    }

    /** Returning false from a MessageSending listener cancels the email. */
    public function handle(MessageSending $event): ?bool
    {
        if (! self::enabled()) {
            return null;
        }

        $message = $event->message;
        $allowed = self::allowed();
        $redirect = strtolower(trim((string) config('mail.staging_guard.redirect')));

        $swapped = [];
        $rewrite = function (array $addresses) use ($allowed, $redirect, &$swapped): array {
            $out = [];
            foreach ($addresses as $address) {
                $mail = strtolower($address->getAddress());
                if (in_array($mail, $allowed, true)) {
                    $out[$mail] = $address;
                } elseif ($redirect !== '') {
                    $swapped[] = $mail;
                    $out[$redirect] = new Address($redirect);
                } else {
                    $swapped[] = $mail;
                }
            }

            return array_values($out);
        };

        $to = $rewrite($message->getTo());
        $cc = $rewrite($message->getCc());
        $bcc = $rewrite($message->getBcc());

        if (! $to && ! $cc && ! $bcc) {
            return false; // nothing may be sent to anybody
        }

        $message->to(...$to);
        $message->cc(...$cc);
        $message->bcc(...$bcc);
        $message->getHeaders()->addTextHeader('X-Staging-Guard', 'on');

        if ($swapped) {
            $message->getHeaders()->addTextHeader('X-Staging-Original-Recipients', implode(', ', array_unique($swapped)));
            $message->subject('[TEST SITE, meant for '.implode(', ', array_unique($swapped)).'] '.$message->getSubject());
        }

        return null;
    }
}
