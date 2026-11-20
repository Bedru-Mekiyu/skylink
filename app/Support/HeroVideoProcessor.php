<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Optional ffmpeg step after an upload: silent, web-optimised (faststart) desktop and mobile
 * MP4s and a poster frame. Without ffmpeg (typical shared hosting) callers store the files as uploaded.
 * Any ffmpeg failure returns null and the caller keeps the original upload: a bad run never loses a video.
 */
final class HeroVideoProcessor
{
    public static function binary(): ?string
    {
        if (config('services.ffmpeg.disabled')) { // tests, or an owner who wants raw uploads only
            return null;
        }
        static $bin = false;
        if ($bin !== false) {
            return $bin;
        }
        $bin = null;
        if (! function_exists('proc_open') || in_array('proc_open', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true)) {
            return null;
        }
        foreach (array_filter([env('FFMPEG_BIN'), 'ffmpeg']) as $candidate) {
            try {
                $p = new Process([$candidate, '-version']);
                $p->setTimeout(5)->run();
                if ($p->isSuccessful()) {
                    return $bin = $candidate;
                }
            } catch (Throwable) {
            }
        }

        return $bin;
    }

    /** Re-encode $src (public-disk path) into $dst: no audio, faststart, max $width px wide. */
    public static function optimise(string $src, string $dst, int $width, int $crf): bool
    {
        return self::run([
            '-i', self::abs($src), '-an', '-vf', "scale='min({$width},iw)':-2",
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', (string) $crf,
            '-pix_fmt', 'yuv420p', '-movflags', '+faststart', self::abs($dst),
        ], $dst);
    }

    /** One JPEG frame from the video, ~1 s in. */
    public static function poster(string $src, string $dst): bool
    {
        return self::run([
            '-ss', '1', '-i', self::abs($src), '-frames:v', '1', '-vf', "scale='min(1920,iw)':-2", '-q:v', '4', self::abs($dst),
        ], $dst);
    }

    private static function abs(string $path): string
    {
        return Storage::disk('public')->path($path);
    }

    private static function run(array $args, string $dst): bool
    {
        $bin = self::binary();
        if (! $bin) {
            return false;
        }
        try {
            @set_time_limit(280);
            $p = new Process([$bin, '-y', '-loglevel', 'error', ...$args]);
            $p->setTimeout(270)->run();
        } catch (Throwable) {
            Storage::disk('public')->delete($dst);

            return false;
        }
        if (! $p->isSuccessful() || ! Storage::disk('public')->exists($dst) || Storage::disk('public')->size($dst) === 0) {
            Storage::disk('public')->delete($dst);

            return false;
        }

        return true;
    }
}
