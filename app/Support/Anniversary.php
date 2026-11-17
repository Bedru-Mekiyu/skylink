<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * 5-Year Anniversary Campaign Support
 *
 * Single source of truth for campaign gating, localized copy,
 * milestone timeline entries, and counter metrics.
 */
class Anniversary
{
    /**
     * The canonical timezone for the campaign.
     */
    public static function timezone(): string
    {
        return config('anniversary.timezone', 'Africa/Addis_Ababa');
    }

    /**
     * Get the current time in the campaign timezone.
     */
    public static function now(?CarbonInterface $override = null): CarbonInterface
    {
        if ($override !== null) {
            return $override instanceof Carbon
                ? $override->copy()->setTimezone(self::timezone())
                : Carbon::instance($override)->setTimezone(self::timezone());
        }

        return Carbon::now(self::timezone());
    }

    /**
     * The start time (inclusive) in Africa/Addis_Ababa time.
     */
    public static function start(): ?CarbonInterface
    {
        $val = config('anniversary.start');
        if (! $val) {
            return null;
        }

        try {
            return Carbon::parse($val, self::timezone());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The end time (exclusive) in Africa/Addis_Ababa time.
     */
    public static function end(): ?CarbonInterface
    {
        $val = config('anniversary.end');
        if (! $val) {
            return null;
        }

        try {
            return Carbon::parse($val, self::timezone());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whether local preview is forced.
     * Strictly ignored when APP_ENV=production or APP_DEBUG is false.
     */
    public static function isForced(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        if (! config('app.debug')) {
            return false;
        }

        return (bool) config('anniversary.force', false);
    }

    /**
     * Whether the campaign is currently active.
     */
    public static function isActive(?CarbonInterface $now = null): bool
    {
        if (self::isForced()) {
            return true;
        }

        $start = self::start();
        $end = self::end();

        if (! $start || ! $end) {
            return false;
        }

        $current = self::now($now);

        // Start inclusive, end exclusive
        return $current->gte($start) && $current->lt($end);
    }

    /**
     * Resolved language code ('am' or 'en').
     */
    public static function locale(?string $locale = null): string
    {
        if ($locale !== null) {
            return $locale === 'am' ? 'am' : 'en';
        }

        return app()->getLocale() === 'am' ? 'am' : 'en';
    }

    /**
     * Get localized campaign string from config.
     */
    public static function text(string $key, ?string $locale = null): ?string
    {
        $texts = config("anniversary.text.{$key}");
        if (! is_array($texts)) {
            return null;
        }

        $loc = self::locale($locale);

        $val = $texts[$loc] ?? $texts['en'] ?? null;
        return is_string($val) && trim($val) !== '' ? trim($val) : null;
    }

    public static function headline(?string $locale = null): ?string
    {
        return self::text('headline', $locale);
    }

    public static function tagline(?string $locale = null): ?string
    {
        return self::text('tagline', $locale);
    }

    public static function thanks(?string $locale = null): ?string
    {
        return self::text('thanks', $locale);
    }

    public static function cta(?string $locale = null): ?string
    {
        return self::text('cta', $locale);
    }

    public static function founded(): ?string
    {
        $val = config('anniversary.founded');
        return is_string($val) && trim($val) !== '' ? trim($val) : null;
    }

    public static function foundedYear(): ?int
    {
        $founded = self::founded();
        if (! $founded) {
            return null;
        }

        try {
            return Carbon::parse($founded)->year;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get validated milestones for the given locale.
     * Items missing a valid year or title are skipped.
     *
     * @return array<int, array{year: int, title: string, text: ?string, image: ?string}>
     */
    public static function milestones(?string $locale = null): array
    {
        $items = config('anniversary.milestones', []);
        if (! is_array($items)) {
            return [];
        }

        $loc = self::locale($locale);
        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $year = isset($item['year']) && is_numeric($item['year']) ? (int) $item['year'] : null;
            if (! $year || $year < 2000 || $year > 2100) {
                continue;
            }

            $title = null;
            if (isset($item['title'])) {
                if (is_array($item['title'])) {
                    $title = $item['title'][$loc] ?? $item['title']['en'] ?? null;
                } elseif (is_string($item['title'])) {
                    $title = $item['title'];
                }
            }

            if (! is_string($title) || trim($title) === '') {
                continue;
            }

            $text = null;
            if (isset($item['text'])) {
                if (is_array($item['text'])) {
                    $text = $item['text'][$loc] ?? $item['text']['en'] ?? null;
                } elseif (is_string($item['text'])) {
                    $text = $item['text'];
                }
            }

            $image = isset($item['image']) && is_string($item['image']) && trim($item['image']) !== ''
                ? trim($item['image'])
                : null;

            $result[] = [
                'year' => $year,
                'title' => trim($title),
                'text' => is_string($text) && trim($text) !== '' ? trim($text) : null,
                'image' => $image,
            ];
        }

        return $result;
    }

    public static function hasMilestones(?string $locale = null): bool
    {
        return ! empty(self::milestones($locale));
    }

    /**
     * Get validated counters for the given locale.
     * Items missing a valid label or integer value are skipped.
     *
     * @return array<int, array{label: string, value: int, suffix: string}>
     */
    public static function counters(?string $locale = null): array
    {
        $items = config('anniversary.counters', []);
        if (! is_array($items)) {
            return [];
        }

        $loc = self::locale($locale);
        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! isset($item['value']) || ! is_numeric($item['value'])) {
                continue;
            }

            $label = null;
            if (isset($item['label'])) {
                if (is_array($item['label'])) {
                    $label = $item['label'][$loc] ?? $item['label']['en'] ?? null;
                } elseif (is_string($item['label'])) {
                    $label = $item['label'];
                }
            }

            if (! is_string($label) || trim($label) === '') {
                continue;
            }

            $suffix = isset($item['suffix']) && is_string($item['suffix']) ? trim($item['suffix']) : '+';

            $result[] = [
                'label' => trim($label),
                'value' => (int) $item['value'],
                'suffix' => $suffix,
            ];
        }

        return $result;
    }

    public static function hasCounters(?string $locale = null): bool
    {
        return ! empty(self::counters($locale));
    }

    /**
     * Determine if there is enough content to show the homepage section.
     */
    public static function hasSectionContent(?string $locale = null): bool
    {
        return self::headline($locale) !== null
            || self::tagline($locale) !== null
            || self::hasMilestones($locale)
            || self::hasCounters($locale);
    }

    /**
     * Get share text for meta tags if campaign active and settings filled.
     */
    public static function shareDescription(?string $locale = null): ?string
    {
        if (! self::isActive()) {
            return null;
        }

        $tagline = self::tagline($locale);
        if (! $tagline) {
            return null;
        }

        $prefix = self::locale($locale) === 'am'
            ? 'የስካይሊንክ 5ኛ ዓመት የምሥረታ በዓል - '
            : 'Celebrating 5 Years of Sky Link ICT Solutions. ';

        return $prefix . $tagline;
    }
}
