<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\NewsSlugRedirect;
use App\Support\NewsDuplicates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Hides duplicate news articles (same normalised title, same language).
 *
 * DRY RUN BY DEFAULT: prints the plan and changes nothing.
 *   --apply     backs up first, then hides each duplicate (soft delete, kept in
 *               the database, restorable from the admin) and redirects its
 *               address (301) to the article that stays.
 *   --keep=     older (default, or config news.duplicates.keep) | newer
 *   --report    also write the plan to storage/app/private/reports/ for the admin
 *               (this is what the scheduler runs; it never applies anything).
 * Exceptions: config/news.php -> duplicates.always_keep / duplicates.not_duplicates.
 */
class CleanDuplicateNews extends Command
{
    protected $signature = 'news:clean-duplicates
        {--apply : Make the changes (after a backup). Without it nothing is changed}
        {--keep= : Which article of a group stays visible: older or newer}
        {--report : Write the dry-run report for the admin page}
        {--force : With --apply, do not ask for confirmation}';

    protected $description = 'Find duplicate news articles; with --apply hide them and redirect their addresses (dry run by default)';

    public function handle(): int
    {
        $keep = $this->option('keep') ?: config('news.duplicates.keep', 'older');
        if (! in_array($keep, ['older', 'newer'], true)) {
            $this->error('--keep must be "older" or "newer".');

            return self::INVALID;
        }
        $apply = (bool) $this->option('apply');
        if ($apply && $this->option('report')) {
            $this->error('--report is for the scheduled dry run; it cannot be combined with --apply.');

            return self::INVALID;
        }

        $plan = NewsDuplicates::plan($keep);
        $lines = $this->describe($plan, $keep, $apply);
        foreach ($lines as $line) {
            $this->line($line);
        }

        if ($this->option('report')) {
            $this->writeReport($plan, $lines);
        }

        $pending = NewsDuplicates::pendingChanges($plan);
        if (! $apply) {
            $this->info($pending
                ? "DRY RUN: nothing was changed. Run with --apply to make these {$pending} change(s)."
                : 'DRY RUN: nothing to change.');

            return self::SUCCESS;
        }
        if ($pending === 0) {
            $this->info('Nothing to change.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Apply {$pending} change(s)? A backup is made first.")) {
            $this->warn('Cancelled. Nothing was changed.');

            return self::FAILURE;
        }

        try {
            $backups = $this->backup();
        } catch (Throwable $e) {
            $this->error('Backup failed, nothing was changed: '.$e->getMessage());

            return self::FAILURE;
        }
        foreach ($backups as $file) {
            $this->line("Backup: {$file}");
        }

        DB::transaction(fn () => $this->applyPlan($plan));
        $this->info("Done: {$pending} change(s) applied. Hidden articles can be restored in the admin under \"Hidden duplicates\".");

        // Refresh the admin's report so it does not keep showing the old warning.
        $after = NewsDuplicates::plan($keep);
        $this->writeReport($after, $this->describe($after, $keep, false));

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function describe($plan, string $keep, bool $apply): array
    {
        $tz = config('news.display_timezone');
        $labels = [
            NewsDuplicates::KEEP => 'KEEP   ',
            NewsDuplicates::HIDE => 'HIDE   ',
            NewsDuplicates::RESTORE => 'RESTORE',
            NewsDuplicates::DONE => 'HIDDEN ',
        ];

        $out = [($apply ? 'APPLY' : 'DRY RUN').": duplicate news articles (same title and language), keeping the {$keep} one", ''];
        if ($plan->isEmpty()) {
            return array_merge($out, ['No duplicate groups found.']);
        }

        foreach ($plan as $i => $group) {
            [$normalized, $lang] = explode('|', $group['key']);
            $out[] = sprintf('Group %d (%s): "%s"%s', $i + 1, $lang, $normalized, $group['ignored'] ? '  -> IGNORED (config: not_duplicates)' : '');
            foreach ($group['members'] as $m) {
                /** @var News $n */
                $n = $m['news'];
                $date = ($n->published_at ?? $n->created_at)?->copy()->setTimezone($tz)->format('Y-m-d H:i');
                $note = match ($m['action']) {
                    NewsDuplicates::HIDE => 'will be hidden; /news/'.$n->slug.' -> 301 -> /news/'.$group['keeper']->slug,
                    NewsDuplicates::RESTORE => 'hidden today; will be shown again (it is the one to keep)',
                    NewsDuplicates::DONE => 'already hidden; redirects to #'.$n->duplicate_of_id,
                    default => 'stays visible',
                };
                $out[] = sprintf('  %s #%-4d %s  %s', $labels[$m['action']], $n->id, $date, $n->title);
                $out[] = sprintf('           /news/%s  (%s, %s) %s', $n->slug, $n->status, $n->category?->label ?? '-', $note);
            }
            $out[] = '';
        }
        $out[] = sprintf('%d group(s); %d change(s) to make.', $plan->count(), NewsDuplicates::pendingChanges($plan));

        return $out;
    }

    private function applyPlan($plan): void
    {
        foreach ($plan->reject(fn ($g) => $g['ignored']) as $group) {
            /** @var News $keeper */
            $keeper = $group['keeper'];

            foreach ($group['members'] as $m) {
                /** @var News $n */
                $n = $m['news'];
                if ($m['action'] === NewsDuplicates::RESTORE) {
                    $n->restore();
                    $n->forceFill(['duplicate_of_id' => null])->save();
                    NewsSlugRedirect::where('old_slug', $n->slug)->delete();
                }
            }

            foreach ($group['members'] as $m) {
                /** @var News $n */
                $n = $m['news'];
                if ($m['action'] !== NewsDuplicates::HIDE) {
                    continue;
                }
                if ($n->trashed()) {
                    $n->restore(); // hidden before, pointing elsewhere: re-point below
                }
                $n->forceFill(['duplicate_of_id' => $keeper->id, 'is_featured' => false])->save();
                $n->delete();

                NewsSlugRedirect::updateOrCreate(['old_slug' => $n->slug], ['news_id' => $keeper->id]);
                // Older addresses of the hidden article now lead to the kept one.
                NewsSlugRedirect::where('news_id', $n->id)->update(['news_id' => $keeper->id]);
            }
        }
    }

    /**
     * Backups in storage/app/private/backups/ (not public):
     *  - a JSON copy of the news and news_slug_redirects tables (any database);
     *  - with SQLite, also a copy of the whole database file.
     *
     * @return list<string>
     */
    private function backup(): array
    {
        $disk = Storage::disk('local');
        $stamp = now()->format('Ymd-His');
        $files = [];

        $json = json_encode([
            'created_at' => now()->toIso8601String(),
            'news' => DB::table('news')->get(),
            'news_slug_redirects' => DB::table('news_slug_redirects')->get(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $path = "backups/news-before-clean-duplicates-{$stamp}.json";
        if (! $disk->put($path, $json) || $disk->size($path) !== strlen($json)) {
            throw new RuntimeException('could not write '.$path);
        }
        $files[] = 'storage/app/private/'.$path;

        if (DB::connection()->getDriverName() === 'sqlite') {
            $database = DB::connection()->getDatabaseName();
            if ($database !== ':memory:' && is_file($database)) {
                $target = $disk->path("backups/database-before-clean-duplicates-{$stamp}.sqlite");
                if (! copy($database, $target) || filesize($target) !== filesize($database)) {
                    throw new RuntimeException('could not copy the SQLite database');
                }
                $files[] = 'storage/app/private/backups/'.basename($target);
            }
        }

        return $files;
    }

    private function writeReport($plan, array $lines): void
    {
        $path = config('news.duplicates.report');
        $header = ['Generated '.now()->setTimezone(config('news.display_timezone'))->format('Y-m-d H:i').' (Addis Ababa time). This report is a dry run: it changed nothing.', ''];
        Storage::disk('local')->put($path, implode("\n", array_merge($header, $lines))."\n");
        Storage::disk('local')->put($path.'.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'groups' => $plan->count(),
            'pending' => NewsDuplicates::pendingChanges($plan),
        ]));
        $this->line('Report written to storage/app/private/'.$path);
    }
}
