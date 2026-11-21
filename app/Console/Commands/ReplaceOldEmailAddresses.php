<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Finds the old company addresses inside database content (news, pages, services ...) and, with --apply, replaces
 * them with the company address (COMPANY_EMAIL). Dry run by default; only these exact old addresses are touched.
 */
class ReplaceOldEmailAddresses extends Command
{
    protected $signature = 'email:replace-old-addresses {--apply : Replace them (without it only list them)}';

    protected $description = 'Replace the old company email addresses in database text with COMPANY_EMAIL (dry run by default)';

    private const OLD = ['info@skylinkict.com', 'info@skylink.com', 'info@skylinktech.com'];

    public function handle(): int
    {
        $new = (string) config('company.email');
        $apply = (bool) $this->option('apply');
        $skip = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'password_reset_tokens', 'inquiries', 'contact_messages', 'messages', 'subscribers', 'subscriptions', 'users'];
        $hits = 0;
        foreach (Schema::getTableListing() as $table) {
            $table = preg_replace('/^.*\./', '', $table);
            if (in_array($table, $skip, true)) {
                continue; // visitor data is never edited
            }
            foreach (Schema::getColumns($table) as $col) {
                if (! preg_match('/text|char|json/i', $col['type_name'] ?? $col['type'] ?? '')) {
                    continue;
                }
                foreach (self::OLD as $old) {
                    $n = DB::table($table)->where($col['name'], 'like', '%'.$old.'%')->count();
                    if ($n === 0) {
                        continue;
                    }
                    $hits += $n;
                    $this->line("{$table}.{$col['name']}: {$n} row(s) contain {$old}");
                    if ($apply) {
                        DB::table($table)->where($col['name'], 'like', '%'.$old.'%')->update([$col['name'] => DB::raw('REPLACE('.DB::getQueryGrammar()->wrap($col['name']).', '.DB::getPdo()->quote($old).', '.DB::getPdo()->quote($new).')')]);
                    }
                }
            }
        }
        $this->info($hits === 0 ? 'No old address found in the database content.' : ($apply ? "Replaced in {$hits} row(s) with {$new}." : "{$hits} row(s) found. DRY RUN: add --apply to replace with {$new}."));
        if ($apply) {
            \App\Support\PublicCache::bust();
        }

        return self::SUCCESS;
    }
}
