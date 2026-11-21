<?php

/*
 * About page people (the ONE place for names and titles of the management team).
 *
 * CEO: the name below is used everywhere (About page text, photo alt text, homepage hero photo alt text).
 *
 * HEAD OF MARKETING - TODO before going live:
 *   1. Put the photo at  public/assets/images/about/head-of-marketing.jpg  (JPG, 4:5 portrait, 480 x 600 px or larger
 *      in the same ratio, under 150 KB). Same file name: it replaces the placeholder silhouette.
 *   2. Change 'name' of 'marketing' below from "Full Name" to the real name.
 * Until then `php artisan team:check-placeholder` fails on a live (production) site, and the deploy script warns.
 */

return [
    'ceo' => [
        'name' => 'Mohammedkerem Ahmed',
        'title' => 'CEO',
        'image' => 'assets/images/about/mohammedkerem.JPG',
    ],

    'marketing' => [
        'name' => 'Full Name', // TODO replace with the Head of Marketing's real name
        'title' => 'Head of Marketing',
        'image' => 'assets/images/about/head-of-marketing.jpg', // TODO replace this file with the real photo
        // sha1 of the shipped silhouette: while the file still has this content it is the placeholder.
        'placeholder_sha1' => '8fb8d822fada71abce7bd51fcec2538551fea3bd',
    ],
];
