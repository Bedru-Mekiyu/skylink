<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Finds uploaded files (storage/app/public, served as /storage/...) that
 * nothing uses, and with --apply MOVES them to the non-public archive:
 * storage/app/private/archive/orphans/<same path>. Nothing is deleted.
 *
 * A file counts as USED when its path or file name appears
 *  - in any text column of any application table (news, videos, portfolios,
 *    services, products, ... including hidden/soft-deleted rows), or
 *  - anywhere in resources/views, app/, config/ or routes/.
 *
 * DRY RUN BY DEFAULT. Each --apply writes a manifest (what moved where) next to
 * the archive, so any file can be put back by copying it to the same path.
 */
class ArchiveOrphanMedia extends Command
{
    protected $signature = 'media:archive-orphans
        {--apply : Move the unused files (without it, only list them)}
        {--force : With --apply, do not ask for confirmation}';

    protected $description = 'List uploaded files nothing uses; with --apply move them to storage/app/private/archive/orphans (dry run by default)';

    /** Framework tables that never hold upload paths. */
    private const SKIP_TABLES = ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $haystack = $this->referenceText();

        $orphans = collect($public->allFiles())
            ->reject(fn (string $path) => str_starts_with(basename($path), '.')) // .gitignore etc.
            ->reject(fn (string $path) => str_contains($haystack, $path) || str_contains($haystack, basename($path)))
            ->values();

        if ($orphans->isEmpty()) {
            $this->info('No unused files found.');

            return self::SUCCESS;
        }

        $total = $orphans->sum(fn ($p) => $public->size($p));
        $this->table(['Unused file (storage/app/public/...)', 'Size', 'Modified'], $orphans->map(fn ($p) => [
            $p, $this->kb($public->size($p)), date('Y-m-d', $public->lastModified($p)),
        ])->all());
        $this->line(sprintf('%d unused file(s), %s in total.', $orphans->count(), $this->kb($total)));

        if (! $this->option('apply')) {
            $this->info('DRY RUN: nothing was moved. Run with --apply to move them to storage/app/private/archive/orphans/.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Move these files to the private archive?')) {
            $this->warn('Cancelled. Nothing was moved.');

            return self::FAILURE;
        }

        $archive = Storage::disk('local');
        $manifest = [];
        $failed = 0;
        foreach ($orphans as $path) {
            $target = 'archive/orphans/'.$path;
            try {
                if ($archive->exists($target)) {
                    $target = 'archive/orphans/'.dirname($path).'/'.now()->format('Ymd-His').'-'.basename($path);
                }
                $archive->put($target, $public->get($path));
                if ($archive->size($target) !== $public->size($path)) {
                    throw new \RuntimeException('archive copy is incomplete');
                }
                $public->delete($path); // the verified copy is in the archive
                $manifest[] = "storage/app/public/{$path}\t->\tstorage/app/private/{$target}";
            } catch (Throwable $e) {
                $failed++;
                $this->error("{$path}: not moved ({$e->getMessage()})");
            }
        }

        $manifestPath = 'archive/orphans/manifest-'.now()->format('Ymd-His').'.txt';
        $archive->put($manifestPath, implode("\n", $manifest)."\n");
        $this->info(count($manifest)." file(s) moved. List: storage/app/private/{$manifestPath}".($failed ? "; {$failed} failed (left in place)." : '.'));

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** Everything that could mention an upload path, as one lower-cost string to search. */
    private function referenceText(): string
    {
        $parts = [];

        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            if (in_array($name, self::SKIP_TABLES, true)) {
                continue;
            }
            $textColumns = collect(Schema::getColumns($name))
                ->filter(fn ($c) => preg_match('/char|text|json|string/i', $c['type_name'] ?? $c['type'] ?? ''))
                ->pluck('name')->all();
            if ($textColumns === []) {
                continue;
            }
            DB::table($name)->select($textColumns)->orderBy($textColumns[0])->chunk(500, function ($rows) use (&$parts) {
                foreach ($rows as $row) {
                    foreach ((array) $row as $value) {
                        if (is_string($value) && $value !== '') {
                            $parts[] = $value;
                        }
                    }
                }
            });
        }

        foreach ([resource_path('views'), app_path(), config_path(), base_path('routes')] as $dir) {
            foreach (File::allFiles($dir) as $file) {
                $parts[] = $file->getContents();
            }
        }

        return implode("\n", $parts);
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024).' KB';
    }
}
