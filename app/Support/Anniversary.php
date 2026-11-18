<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Date gate for the 5-year anniversary campaign (config/anniversary.php).
 */
final class Anniversary
{
    public static function isActive(): bool
    {
        if (self::isPreviewForced()) {
            return true;
        }

        $tz = config('anniversary.timezone');
        $now = CarbonImmutable::now($tz);

        return $now->gte(CarbonImmutable::parse(config('anniversary.start'), $tz))
            && $now->lt(CarbonImmutable::parse(config('anniversary.end'), $tz));
    }

    /** ANNIVERSARY_FORCE preview: never in production, only in the listed environments. */
    public static function isPreviewForced(): bool
    {
        return (bool) config('anniversary.force')
            && ! app()->isProduction()
            && app()->environment(config('anniversary.preview_environments', []));
    }

    /** Short contextual line key for the current public page. */
    public static function contextKey(): string
    {
        $section = strtok((string) request()->route()?->getName(), '.');

        return match ($section) {
            'about', 'services', 'products', 'portfolio', 'contact' => $section,
            'content', 'news', 'videos' => 'news',
            default => 'default',
        };
    }
}
