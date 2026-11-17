<?php

/*
 * 5-year anniversary campaign: the one source for the intro, the navbar badge,
 * its panel and the homepage "Our 5 Years" section (App\Support\Anniversary).
 *
 * Dates come from .env, in Africa/Addis_Ababa time. The end is exclusive:
 * ANNIVERSARY_END=2026-12-07 00:00 means 6 December is the last day shown.
 * Without both dates the campaign never shows.
 *
 * ANNIVERSARY_FORCE=true shows the campaign outside its dates, for a local
 * preview only. It is ignored when APP_ENV=production or APP_DEBUG=false.
 *
 * Never invent facts here. Any part whose values are missing (null or an
 * empty list) is simply not shown.
 */
return [
    'start' => env('ANNIVERSARY_START'),
    'end' => env('ANNIVERSARY_END'),
    'timezone' => 'Africa/Addis_Ababa',
    'force' => (bool) env('ANNIVERSARY_FORCE', false),

    // Founding date of Sky Link ICT Solutions, 'YYYY-MM-DD'. TODO: owner to provide.
    // Shown as "Since <year>" when set.
    'founded' => null,

    // Ceremony-Focused Campaign Copy.
    // Each text: 'en' and 'am'; leave one null to show only the other.
    'text' => [
        'headline' => [
            'en' => 'Celebrating Five Years of Sky Link ICT Solutions',
            'am' => 'የስካይ ሊንክ አይሲቲ ሶሉሽንስ 5ኛ ዓመት የምሥረታ በዓል',
        ],
        'tagline' => [
            'en' => 'Join us as we commemorate five years of dedication, progress, and meaningful contributions to the technology industry. We look forward to celebrating this milestone with our team, clients, partners, and community.',
            'am' => 'በቴክኖሎጂው ዘርፍ ላለፉት አምስት ዓመታት ያሳየነውን ቁርጠኝነት፣ ዕድገት እና አስተዋጽኦ ስናከብር አብረውን ይሁኑ። ይህንን ትልቅ ምዕራፍ ከቡድናችን፣ ደንበኞቻችን፣ አጋሮቻችን እና ማህበረሰባችን ጋር በጋራ ለማክበር በጉጉት እንጠብቃለን።',
        ],
        'thanks' => [
            'en' => 'Thank you for five years of dedication, partnership, and shared progress.',
            'am' => 'ላለፉት አምስት ዓመታት አብረውን ለተጓዙ ደንበኞቻችን፣ አጋሮቻችን እና መላው ቡድናችን የከበረ ምስጋና እናቀርባለን።',
        ],
        // Button under the homepage section; opens the quote form on /services.
        'cta' => [
            'en' => 'Start a project with us',
            'am' => 'አብረውን ይስሩ',
        ],
    ],

    // Milestones, oldest first. TODO: owner to provide. Each item:
    //   ['year' => 2021,
    //    'title' => ['en' => '...', 'am' => null],
    //    'text' => ['en' => '...', 'am' => null],      // optional
    //    'image' => 'assets/images/....webp'],          // optional, a file already in public/
    // An item without a year or an English/Amharic title is skipped.
    'milestones' => [],

    // Counters for the homepage section. TODO: owner to provide. Each item:
    //   ['label' => ['en' => '...', 'am' => null], 'value' => 120, 'suffix' => '+']
    // An item without a label or a whole-number value is skipped.
    'counters' => [],
];
