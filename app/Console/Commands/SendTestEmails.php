<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Controlled test send: ONE labelled email per form type (contact, quote, service, support, subscription) to the
 * company address (COMPANY_EMAIL, config/company.php) and to nobody else. DRY RUN BY DEFAULT: nothing is sent
 * without --send. The recipient cannot be changed on the command line. Error texts are shown with any password removed.
 */
class SendTestEmails extends Command
{
    protected $signature = 'mail:send-test {--send : Really send the five test emails (without it only the plan is shown)}';

    protected $description = 'Send one labelled test email per form type to the company address only (dry run by default)';

    private const TYPES = ['contact', 'quote', 'service', 'support', 'subscription'];

    public function handle(): int
    {
        $to = (string) config('company.email');
        $from = (string) config('mail.from.address');
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('COMPANY_EMAIL is not a valid address.');

            return self::FAILURE;
        }
        $this->line('Mailer: '.config('mail.default').'   From: '.$from.'   To (fixed): '.$to);
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->warn('The mailer is "'.config('mail.default').'": mails are only written to the log, not delivered.');
        }
        if (! $this->option('send')) {
            foreach (self::TYPES as $t) {
                $this->line("  would send: [TEST {$t} form] to {$to}");
            }
            $this->warn('DRY RUN: nothing was sent. Add --send to send the test emails.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach (self::TYPES as $t) {
            try {
                Mail::raw(
                    "This is a labelled TEST of the \"{$t}\" form email path from ".config('app.url').".\nNo visitor is involved. Sent ".now()->toDateTimeString().'.',
                    fn ($m) => $m->to($to)->subject("[TEST {$t} form] Sky Link website email test")
                );
                $this->info("  sent:   [TEST {$t} form] -> {$to}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  FAILED: {$t}: ".$this->clean($e->getMessage()));
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** Never show a secret: remove the mail password (and username) if the error text contains them. */
    private function clean(string $text): string
    {
        foreach ([config('mail.mailers.smtp.password'), config('mail.mailers.smtp.username')] as $secret) {
            if (is_string($secret) && strlen($secret) >= 4) {
                $text = str_replace($secret, '***', $text);
            }
        }

        return mb_substr(preg_replace('/\s+/', ' ', $text), 0, 400);
    }
}
