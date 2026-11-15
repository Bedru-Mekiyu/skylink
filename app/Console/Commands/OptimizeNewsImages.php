<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Support\NewsImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * For news images uploaded before the new admin form.
 *
 * Pass 1, images without a thumbnail:
 *  - every image gets a card thumbnail (news/thumbs/...) and a JPEG share copy
 *    (news/share/..., for og:image);
 *  - oversized images (wider than 1600 px or bigger than 600 KB) also get a web
 *    copy (news/web/...), which the site then uses, and the original is MOVED
 *    (not deleted) to the non-public archive:
 *    storage/app/private/archive/news-originals/<same name>.
 *
 * Pass 2, images that have a thumbnail but no JPEG share copy yet: the share
 * copy is made from the archived original when there is one (best quality),
 * otherwise from the image the site shows.
 *
 * Safe to run more than once: finished articles are skipped. Use --dry-run first.
 */
class OptimizeNewsImages extends Command
{
    protected $signature = 'news:optimize-images {--dry-run : Only report, change nothing}
        {--max-megapixels= : Raise the decode limit for this run (needs memory: about 5 MB per megapixel, e.g. php -d memory_limit=512M)}';

    protected $description = 'Create web copies, thumbnails and JPEG share copies for existing news images; archive oversized originals';

    public function handle(NewsImageProcessor $images): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($mp = (int) $this->option('max-megapixels')) {
            config(['news.images.max_pixels' => $mp * 1_000_000]);
        }

        $rows = array_merge($this->firstPass($images, $dry), $this->sharePass($images, $dry));

        $this->table(['Image', 'Size', 'Width', $dry ? 'Plan' : 'Result'], $rows);
        $this->info(count($rows).' image file(s) '.($dry ? 'checked (dry run, nothing changed).' : 'processed.'));

        return self::SUCCESS;
    }

    /** Images from before the new form: thumbnail + share copy, and a web copy if oversized. */
    private function firstPass(NewsImageProcessor $images, bool $dry): array
    {
        $public = Storage::disk('public');
        $archive = Storage::disk('local');
        $maxWidth = (int) config('news.images.web_max_width');
        $maxBytes = (int) config('news.images.oversized_bytes');

        // One image file can be shared by several articles (imports reused files).
        $paths = News::withTrashed()
            ->whereNotNull('image')->whereNull('image_thumb')
            ->where('image', 'not like', 'news/web/%')
            ->distinct()->pluck('image');

        $rows = [];
        foreach ($paths as $path) {
            if (! $public->exists($path)) {
                $rows[] = [$path, '-', '-', 'skipped: file missing'];
                continue;
            }
            $bytes = $public->get($path);
            $size = strlen($bytes);
            $width = (int) (@getimagesizefromstring($bytes)[0] ?? 0);
            $oversized = $size > $maxBytes || $width > $maxWidth;

            if ($dry) {
                $rows[] = [$path, $this->kb($size), $width ?: '?', $oversized
                    ? 'would make web copy, thumbnail and share JPEG; archive original'
                    : 'would make thumbnail and share JPEG'];
                continue;
            }

            try {
                $update = $images->makeCopies($bytes, $oversized ? ['web', 'thumb', 'share'] : ['thumb', 'share']);

                if ($oversized) {
                    $archived = 'archive/news-originals/'.basename($path);
                    $archive->put($archived, $bytes);
                    if ($archive->size($archived) !== $size) {
                        throw new \RuntimeException('archive copy is incomplete');
                    }
                    $update['image_original'] = $archived;
                }

                DB::transaction(fn () => News::withTrashed()->where('image', $path)->update($update));

                if ($oversized) {
                    $public->delete($path); // the verified copy is in the archive
                    $rows[] = [$path, $this->kb($size), $width, "moved to storage/app/private/{$archived}; web copy {$this->kb($public->size($update['image']))}"];
                } else {
                    $rows[] = [$path, $this->kb($size), $width, 'thumbnail and share JPEG added'];
                }
            } catch (Throwable $e) {
                $rows[] = [$path, $this->kb($size), $width ?: '?', 'FAILED: '.$e->getMessage()];
            }
        }

        return $rows;
    }

    /** Articles that already have a thumbnail but no JPEG share copy. */
    private function sharePass(NewsImageProcessor $images, bool $dry): array
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        $groups = News::withTrashed()
            ->whereNotNull('image')->whereNotNull('image_thumb')->whereNull('image_share')
            ->get(['id', 'image', 'image_original'])
            ->groupBy(fn (News $n) => $n->image);

        $rows = [];
        foreach ($groups as $image => $articles) {
            $original = $articles->first()->image_original;
            [$disk, $source] = $original && $private->exists($original) ? [$private, $original] : [$public, $image];
            $label = $disk === $private ? "storage/app/private/{$source}" : $source;

            if (! $disk->exists($source)) {
                $rows[] = [$image, '-', '-', 'skipped: file missing'];
                continue;
            }
            if ($dry) {
                $rows[] = [$image, $this->kb($disk->size($source)), '', "would make share JPEG from {$label}"];
                continue;
            }
            try {
                $copy = $images->makeCopies($disk->get($source), ['share']);
                News::withTrashed()->whereIn('id', $articles->pluck('id'))->update($copy);
                $rows[] = [$image, $this->kb($disk->size($source)), '', "share JPEG {$copy['image_share']} ({$this->kb($public->size($copy['image_share']))}) from {$label}"];
            } catch (Throwable $e) {
                $rows[] = [$image, '-', '', 'FAILED: '.$e->getMessage()];
            }
        }

        return $rows;
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024).' KB';
    }
}
