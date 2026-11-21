<?php

namespace App\Support;

/** Detects the "Head of Marketing" placeholder (config/team.php) so it cannot reach the live site unnoticed. */
class TeamPlaceholder
{
    public static function inUse(): bool
    {
        $m = config('team.marketing');
        $file = public_path($m['image']);
        $sha = (string) ($m['placeholder_sha1'] ?? '');

        return $m['name'] === 'Full Name'
            || ! is_file($file)
            || ($sha !== '' && sha1_file($file) === $sha);
    }

    /** Production without the test-site mail guard = the real live site. */
    public static function blocksThisSite(): bool
    {
        return app()->isProduction() && ! config('mail.staging_guard.enabled') && self::inUse();
    }

    /** True when the card must not be shown: the real live site, placeholder photo, and the show flag is off. */
    public static function hideCard(): bool
    {
        return self::blocksThisSite() && ! config('team.marketing.show_placeholder_on_live');
    }
}
