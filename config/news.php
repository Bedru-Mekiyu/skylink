<?php

return [
    // The admin types publish times in this time zone; the database stays in
    // the app time zone (UTC). Ethiopia does not use daylight saving time.
    'admin_timezone' => env('NEWS_ADMIN_TIMEZONE', 'Africa/Addis_Ababa'),

    // Dates shown to visitors (lists, cards, article page, meta tags).
    'display_timezone' => env('NEWS_DISPLAY_TIMEZONE', 'Africa/Addis_Ababa'),

    'images' => [
        // Web copy shown on the article page.
        'web_max_width' => 1600,
        'web_quality' => 82,
        // Card thumbnail.
        'thumb_width' => 640,
        'thumb_quality' => 78,
        // Share preview (og:image), always JPEG.
        'share_max_width' => 1200,
        'share_quality' => 82,
        // GD needs about 4-5 bytes per pixel; this keeps a decode under ~120 MB.
        'max_pixels' => 25_000_000,
        // Existing images larger than this (bytes) or wider than web_max_width
        // get a web copy by `php artisan news:optimize-images`.
        'oversized_bytes' => 600 * 1024,
    ],

    // Identical titles (after normalising) created within this many minutes are blocked.
    'duplicate_block_minutes' => 10,

    // `php artisan news:clean-duplicates`: articles with the same normalised
    // title in the same language. Dry run by default; --apply hides duplicates.
    'duplicates' => [
        // Which one of a group stays visible: 'older' or 'newer' (--keep overrides).
        'keep' => 'older',

        // Addresses (slugs) that must stay visible whenever they are in a group,
        // e.g. 'ai-in-ethiopia-1753509260'.
        'always_keep' => [
            // Approved 2026-11-15: keep "Ai in Ethiopia" (Innovation), hide the older all-caps copy.
            'ai-in-ethiopia-1753509260',
        ],

        // Groups that are NOT duplicates even though the titles match. Each entry
        // lists the slugs of one group, e.g. ['weekly-update-1', 'weekly-update-2'].
        'not_duplicates' => [],

        // Report written by the scheduled dry run (local, non-public disk).
        'report' => 'reports/news-duplicates.txt',
    ],
];
