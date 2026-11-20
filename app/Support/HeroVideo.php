<?php

namespace App\Support;

use App\Models\HeroVideoSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The homepage hero files chosen in Admin > Homepage Video.
 * Nothing chosen (or the files are gone) -> null, and the homepage uses the built-in defaults.
 */
final class HeroVideo
{
    public const DIR = 'hero-video';

    public static function row(): ?HeroVideoSetting
    {
        try {
            return Schema::hasTable('hero_video_settings') ? HeroVideoSetting::query()->latest('id')->first() : null;
        } catch (Throwable) {
            return null; // the homepage must never fail because of this feature
        }
    }

    /** @return array<string,string> key => path on the public disk (only files that still exist) */
    public static function files(?array $set): array
    {
        $disk = Storage::disk('public');
        $out = [];
        foreach (HeroVideoSetting::KEYS as $key) {
            $path = $set[$key] ?? null;
            if (is_string($path) && str_starts_with($path, self::DIR.'/') && $disk->exists($path)) {
                $out[$key] = $path;
            }
        }

        return $out;
    }

    /**
     * Versioned URLs for the current custom set, or null for "use the built-in default".
     * A custom set needs at least one video; a poster alone does not replace the default.
     *
     * @return array<string,string>|null
     */
    public static function current(): ?array
    {
        $files = self::files(self::row()?->current);
        if (! array_intersect_key($files, array_flip(['desktop_mp4', 'desktop_webm', 'mobile_mp4']))) {
            return null;
        }

        $disk = Storage::disk('public');

        return array_map(
            fn (string $path) => asset('storage/'.$path).'?v='.$disk->lastModified($path),
            $files
        );
    }
}
