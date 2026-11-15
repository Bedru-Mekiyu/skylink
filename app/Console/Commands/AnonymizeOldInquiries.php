<?php

namespace App\Console\Commands;

use App\Models\Inquiry;
use Illuminate\Console\Command;

/**
 * Privacy: removes the personal data (name, email, phone, company, message,
 * IP hash, browser) from website messages older than
 * config('contact.retention_months') (CONTACT_RETENTION_MONTHS, default 24;
 * 0 = off). The type, service, plan, status and dates stay for statistics.
 * Runs monthly from the scheduler; --dry-run only counts.
 */
class AnonymizeOldInquiries extends Command
{
    protected $signature = 'inquiries:anonymize {--dry-run : Only count, change nothing}';

    protected $description = 'Remove personal data from website messages older than the retention period';

    public function handle(): int
    {
        $months = (int) config('contact.retention_months');
        if ($months <= 0) {
            $this->info('Retention is switched off (CONTACT_RETENTION_MONTHS=0). Nothing to do.');

            return self::SUCCESS;
        }

        $query = Inquiry::withTrashed()
            ->whereNull('anonymized_at')
            ->where('created_at', '<', now()->subMonths($months));

        $count = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("{$count} message(s) older than {$months} months would be anonymised (dry run, nothing changed).");

            return self::SUCCESS;
        }

        $query->update([
            'name' => '(removed)',
            'email' => 'removed@invalid.invalid',
            'phone' => null,
            'company' => null,
            'message' => null,
            'ip_hash' => null,
            'user_agent' => null,
            'anonymized_at' => now(),
        ]);
        $this->info("{$count} message(s) older than {$months} months anonymised.");

        return self::SUCCESS;
    }
}
