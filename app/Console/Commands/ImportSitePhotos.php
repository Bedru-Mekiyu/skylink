<?php

namespace App\Console\Commands;

use App\Models\GalleryImage;
use App\Support\GalleryImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Fills Admin > Gallery with the site's own photographs (the reviewed list in database/data/gallery-site-photos.php).
 *
 * Source files: storage/app/private/gallery-import-src/ (or --dir=). They are only READ, never changed or deleted.
 * Every photo goes through the same GalleryImageProcessor as an admin upload (random names, thumbnail + display WebP,
 * original archived privately). Oversized sources (over 8 MB or over 7000 px) are first shrunk into a temporary copy
 * (long side 4000 px) with a raised memory limit for this process only.
 *
 * DRY RUN BY DEFAULT. --apply imports (re-runnable: photos already imported are skipped).
 * Every import is recorded in storage/app/private/gallery-import/manifest.json.
 * --rollback removes everything this command imported; --rollback --hide-only just hides it instead.
 */
class ImportSitePhotos extends Command
{
    protected $signature = 'gallery:import-site-photos
        {--apply : Import the photos (without it, only list what would happen)}
        {--rollback : Remove every photo this command imported (add --hide-only to hide instead)}
        {--hide-only : With --rollback: hide the imported photos instead of deleting them}
        {--dir= : Folder with the source files (default storage/app/private/gallery-import-src)}';

    protected $description = 'Import the reviewed site photos into the About gallery (dry run by default; --rollback undoes it)';

    private const MANIFEST = 'gallery-import/manifest.json';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dir = rtrim($this->option('dir') ?: storage_path('app/private/gallery-import-src'), '/\\');
        $list = require base_path('database/data/gallery-site-photos.php');
        $apply = (bool) $this->option('apply');
        $manifest = $this->manifest();
        $done = array_column($manifest, null, 'file');
        $base = (int) GalleryImage::max('sort_order');
        $rows = [];
        $problems = 0;
        $imported = 0;

        foreach ($list as $item) {
            $path = $dir.'/'.$item['file'];
            $state = 'ok';
            if (isset($done[$item['file']]) && GalleryImage::whereKey($done[$item['file']]['id'])->exists()) {
                $rows[] = [$item['sort'], $item['file'], $item['visible'] ? 'visible' : 'hidden', '-', 'already imported'];
                continue;
            }
            if (! is_file($path)) {
                $rows[] = [$item['sort'], $item['file'], $item['visible'] ? 'visible' : 'hidden', '-', 'MISSING source file'];
                $problems++;
                continue;
            }
            [$w, $h] = @getimagesize($path) ?: [0, 0];
            $big = filesize($path) > GalleryImageProcessor::MAX_KB * 1024 || max($w, $h) > GalleryImageProcessor::MAX_SIDE;
            $state = $big ? 'will be shrunk first (oversized)' : 'ok';
            if ($apply) {
                try {
                    $id = $this->importOne($path, $item, $base + $item['sort'], $big);
                    $manifest[] = ['file' => $item['file'], 'id' => $id, 'visible' => $item['visible'], 'at' => now()->toDateTimeString()];
                    $this->saveManifest($manifest);
                    $state = 'IMPORTED (id '.$id.')';
                    $imported++;
                } catch (Throwable $e) {
                    $state = 'FAILED: '.$e->getMessage();
                    $problems++;
                }
            }
            $rows[] = [$item['sort'], $item['file'], $item['visible'] ? 'visible' : 'hidden', $w.'x'.$h.', '.round(filesize($path) / 1048576, 1).' MB', $state];
        }

        $this->table(['#', 'file', 'state', 'source', 'result'], $rows);
        $visible = count(array_filter($list, fn ($i) => $i['visible']));
        $this->line(count($list).' photos in the list ('.$visible.' visible, '.(count($list) - $visible).' hidden).');
        if (! $apply) {
            $this->warn('DRY RUN: nothing was changed. Add --apply to import. Undo later with --rollback.');
        } else {
            $this->info($imported.' imported. Undo with: php artisan gallery:import-site-photos --rollback');
        }

        return $problems ? self::FAILURE : self::SUCCESS;
    }

    private function importOne(string $path, array $item, int $sort, bool $big): int
    {
        $tmp = null;
        $use = $path;
        if ($big) {
            $tmp = $this->shrink($path);
            $use = $tmp;
        }
        try {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($use);
            $file = new UploadedFile($use, $item['file'], $mime, null, true);
            $files = GalleryImageProcessor::process($file);
        } finally {
            if ($tmp && is_file($tmp)) {
                @unlink($tmp); // only our temporary copy
            }
        }
        $image = GalleryImage::create($files + [
            'caption_en' => $item['caption_en'] ?? null,
            'caption_am' => null, // left empty on purpose: the owner fills Amharic in Admin > Gallery
            'sort_order' => $sort,
            'visible' => (bool) $item['visible'],
            'updated_by' => null,
        ]);

        return $image->id;
    }

    /** Smaller JPEG copy (long side 4000 px) of an oversized source, in the temp folder. The source is only read. */
    private function shrink(string $path): string
    {
        @ini_set('memory_limit', '1536M'); // this process only
        $info = @getimagesize($path);
        if (! $info) {
            throw new RuntimeException('not an image');
        }
        $src = match ($info['mime']) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromjpeg($path),
        };
        if (! $src) {
            throw new RuntimeException('could not be read (memory?)');
        }
        if ($info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $o = @exif_read_data($path)['Orientation'] ?? 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($angle && ($rot = imagerotate($src, $angle, 0))) {
                imagedestroy($src);
                $src = $rot;
            }
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, 4000 / max($w, $h));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($tw, $th);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagedestroy($src);
        $out = tempnam(sys_get_temp_dir(), 'galimp').'.jpg';
        imagejpeg($dst, $out, 88);
        imagedestroy($dst);

        return $out;
    }

    private function rollback(): int
    {
        $manifest = $this->manifest();
        if (! $manifest) {
            $this->info('Nothing to roll back (no import manifest).');

            return self::SUCCESS;
        }
        $hideOnly = (bool) $this->option('hide-only');
        $n = 0;
        foreach ($manifest as $m) {
            $g = GalleryImage::find($m['id']);
            if (! $g) {
                continue;
            }
            if ($hideOnly) {
                $g->update(['visible' => false]);
            } else {
                foreach (['thumb_path', 'display_path'] as $k) {
                    Storage::disk('public')->delete($g->$k);
                }
                $g->delete(); // the private archive copy of the original is kept
            }
            $n++;
        }
        if (! $hideOnly) {
            File::delete(storage_path('app/private/'.self::MANIFEST));
        }
        $this->info($n.($hideOnly ? ' imported photos hidden.' : ' imported photos removed.'));

        return self::SUCCESS;
    }

    private function manifest(): array
    {
        $f = storage_path('app/private/'.self::MANIFEST);

        return is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }

    private function saveManifest(array $m): void
    {
        $f = storage_path('app/private/'.self::MANIFEST);
        File::ensureDirectoryExists(dirname($f));
        file_put_contents($f, json_encode($m, JSON_PRETTY_PRINT));
    }
}
