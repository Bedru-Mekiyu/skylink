<?php

namespace App\Support;

use App\Models\AnniversarySetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Gate for the 5-year anniversary campaign.
 *
 * The owner controls it in Admin > Anniversary Ceremony (mode Automatic / Off / On now, start and end
 * time in Africa/Addis_Ababa, admin preview). Until something is saved there, config/anniversary.php
 * (the same dates) decides, so behaviour does not change on deploy.
 */
final class Anniversary
{
    private const CACHE_KEY = 'anniversary.settings';
    private const CACHE_SECONDS = 60;

    public static function isActive(): bool
    {
        return self::isPreviewForced() || self::liveForVisitors() || self::adminPreviewing();
    }

    /** Would an ordinary visitor see the ceremony right now? */
    public static function liveForVisitors(?CarbonImmutable $now = null): bool
    {
        $s = self::settings();
        $now ??= CarbonImmutable::now($s['timezone']);

        return match ($s['mode']) {
            'off' => false,
            'on' => $now->lt($s['end']),
            default => $now->gte($s['start']) && $now->lt($s['end']),
        };
    }

    /** A logged-in admin with "Admin preview" ticked sees the ceremony even when it is hidden for visitors. */
    public static function adminPreviewing(): bool
    {
        if (! self::settings()['admin_preview']) {
            return false;
        }

        try {
            return (bool) auth()->user()?->is_admin;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Effective settings: the saved row (cached briefly) or the config dates.
     *
     * @return array{mode:string,start:CarbonImmutable,end:CarbonImmutable,admin_preview:bool,timezone:string,saved:bool}
     */
    public static function settings(): array
    {
        $tz = config('anniversary.timezone');
        $row = self::savedRow();

        $start = ($row['starts_at'] ?? null)
            ? self::fromUtc($row['starts_at'], $tz)
            : CarbonImmutable::parse(config('anniversary.start'), $tz);
        $end = ($row['ends_at'] ?? null)
            ? self::fromUtc($row['ends_at'], $tz)
            : CarbonImmutable::parse(config('anniversary.end'), $tz);

        return [
            'mode' => in_array($row['mode'] ?? null, AnniversarySetting::MODES, true) ? $row['mode'] : 'auto',
            'start' => $start,
            'end' => $end,
            'admin_preview' => (bool) ($row['admin_preview'] ?? false),
            'timezone' => $tz,
            'saved' => $row !== null,
        ];
    }

    /** A stored UTC datetime as Addis Ababa time (via the timestamp, so a frozen test clock cannot interfere). */
    private static function fromUtc(string $utc, string $tz): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp(strtotime($utc.' UTC'), $tz);
    }

    /** Forget the cached row; the admin page calls this on save so the change shows at once. */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** The one saved row as an array, or null. Cached for a minute; `false` caches "no row". */
    private static function savedRow(): ?array
    {
        try {
            $row = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function () {
                if (! Schema::hasTable('anniversary_settings')) {
                    return false;
                }
                // Raw column values (stored in UTC): no model date casting, so a frozen test clock cannot shift them.
                $m = DB::table('anniversary_settings')->first();

                return $m ? [
                    'mode' => $m->mode,
                    'starts_at' => $m->starts_at,
                    'ends_at' => $m->ends_at,
                    'admin_preview' => (bool) $m->admin_preview,
                ] : false;
            });
        } catch (Throwable) {
            return null; // database or cache not reachable: fall back to the config dates
        }

        return $row ?: null;
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
