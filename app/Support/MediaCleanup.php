<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Deletes the files of a deleted admin item to free storage. Only files in storage are ever removed:
 * the site's own files ("assets/..." paths) and web addresses (http...) are never touched.
 */
final class MediaCleanup
{
    /** @param  iterable<string|null>  $publicPaths  paths on the public disk  @param  iterable<string|null>  $privatePaths  paths on the private disk (archived originals) */
    public static function remove(iterable $publicPaths, iterable $privatePaths = []): void
    {
        foreach ($publicPaths as $path) {
            if (self::deletable($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        foreach ($privatePaths as $path) {
            if (self::deletable($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private static function deletable(?string $path): bool
    {
        return is_string($path) && $path !== '' && ! str_starts_with($path, 'assets/') && ! preg_match('#^https?://#i', $path) && ! str_contains($path, '..');
    }
}
