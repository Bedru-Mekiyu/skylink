<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Short-lived cache for the public pages' data (home sections, product and service lists).
 *
 * Every key carries a version number. bust() writes a new version, so the next visit rebuilds everything at once;
 * the old entries simply expire. bust() runs automatically after every successful admin save (BustPublicCache
 * middleware), when a model is saved or deleted (AppServiceProvider) and after scheduled publishing, so the admin
 * never waits for a timer. Never put admin pages, forms or the anniversary on/off result in here.
 */
final class PublicCache
{
    private const VERSION_KEY = 'public-data-version';

    public static function remember(string $name, int $seconds, Closure $build): mixed
    {
        if (! config('cache.public_data', true)) {
            return $build();
        }

        return Cache::remember('public:'.self::version().':'.$name, $seconds, $build);
    }

    public static function bust(): void
    {
        Cache::forever(self::VERSION_KEY, (string) microtime(true));
    }

    private static function version(): string
    {
        return (string) Cache::rememberForever(self::VERSION_KEY, fn () => (string) microtime(true));
    }
}
