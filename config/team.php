<?php

/*
 * About page people (the ONE place for names and titles of the management team).
 *
 * CEO: the name below is used everywhere (About page text, photo alt text, homepage hero photo alt text).
 *
 * HEAD OF MARKETING - TODO before going live:
 *   1. Put the photo at  public/assets/images/about/head-of-marketing.jpg  (JPG, 4:5 portrait, 480 x 600 px or larger
 *      in the same ratio, under 150 KB). Same file name: it replaces the placeholder silhouette.
 *   2. The name is already set below ('Frezer Maru'); change it here only if it must change.
 * Until then `php artisan team:check-placeholder` fails on a live (production) site, and the deploy script warns.
 */

return [
    'ceo' => [
        'name' => 'Mohammedkerem Ahmed',
        'title' => 'CEO',
        'image' => 'assets/images/about/mohammedkerem.JPG',
    ],

    'marketing' => [
        'name' => 'Frezer Maru',
        'title' => 'Head of Marketing',
        'image' => 'assets/images/about/head-of-marketing.jpg', // placeholder portrait until the real photo replaces this ONE file
        // sha1 of the shipped silhouette: while the file still has this content it is the placeholder.
        'placeholder_sha1' => '383dbf885dd19885be7da7ad2cc6f5a3f82826bf',
        // On the real live site the card stays HIDDEN while the placeholder photo is in place. Set TEAM_SHOW_PLACEHOLDER=true
        // in .env to show it anyway (not recommended).
        'show_placeholder_on_live' => (bool) env('TEAM_SHOW_PLACEHOLDER', false),
    ],
];
