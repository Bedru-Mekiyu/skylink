<?php

/*
 * Search-engine head data in ONE place (read by App\Support\Seo, printed by partials/seo.blade.php).
 *
 * pages: title and description per route name. Titles stay under 60 characters (the home title is the owner's wording),
 * descriptions are 110-155 characters. Pages that are not listed (news articles, services, videos ...) set their own
 * title/description in their view; the brand is added to the title when it fits.
 * The canonical host is always APP_URL (https://skylinkict.com): never www., never s.
 */
return [
    'brand' => 'Sky Link Technologies',

    'default_description' => 'Sky Link Technologies builds software, networks and security systems for businesses in Addis Ababa and across Ethiopia.',

    // 1200x630 share image used when a page has none of its own (public/assets/images/og-default.jpg).
    'default_image' => 'assets/images/og-default.jpg',

    'pages' => [
        'home' => [
            'title' => 'Sky Link Technologies | IT Solutions in Addis Ababa, Ethiopia',
            'description' => 'Sky Link Technologies builds software, networks and security systems for businesses in Addis Ababa and across Ethiopia. See our services and projects.',
        ],
        'about' => [
            'title' => 'About Us | Sky Link Technologies',
            'description' => 'Meet the team behind Sky Link Technologies and learn how we deliver reliable software, cybersecurity and digital transformation services in Ethiopia.',
        ],
        'services' => [
            'title' => 'Software Development Services | Sky Link Technologies',
            'description' => 'Software development, enterprise network, cybersecurity and IT support services from Sky Link Technologies in Addis Ababa. Ask us for a free quote.',
        ],
        'products' => [
            'title' => 'Software Products | Sky Link Technologies',
            'description' => 'Explore software products from Sky Link Technologies, including school management, learning management and system maintenance solutions.',
        ],
        'portfolio' => [
            'title' => 'Our Projects and Portfolio | Sky Link Technologies',
            'description' => 'See projects delivered by Sky Link Technologies for clients in Ethiopia, including websites and systems for Kirb Gebeya, Elida Ethiopia and more.',
        ],
        'content.index' => [
            'title' => 'News and Events | Sky Link Technologies',
            'description' => 'Latest news, events and updates from Sky Link Technologies: new projects, launches and company announcements from our team in Ethiopia.',
        ],
        'contact' => [
            'title' => 'Contact Us | Sky Link Technologies',
            'description' => 'Contact Sky Link Technologies at Bole, Tropical Mall, Addis Ababa. Call, email or send us a message and our team will get back to you.',
        ],
    ],

    // Shown on the unknown-address page (real 404 status).
    'not_found' => [
        'title' => 'Page Not Found | Sky Link Technologies',
        'description' => 'The page you are looking for does not exist or has moved. Go back to the Sky Link Technologies home page.',
    ],

    // Company facts for the structured data (phones: config/company_contact.php, email: config/company.php).
    'organization' => [
        'name' => 'Sky Link Technologies',
        'logo' => 'assets/images/skylink-logo.webp',
        'street' => 'Bole, Tropical Mall',
        'city' => 'Addis Ababa',
        'country' => 'ET',
        // Real social profiles (the same links as the footer).
        'same_as' => [
            'https://www.facebook.com/profile.php?id=61562181474117',
            'https://www.instagram.com/skylink_technologies',
            'https://www.linkedin.com/company/skylinkict/',
            'https://www.tiktok.com/@skylink_technologies',
            'https://www.youtube.com/@skylink_technologie',
            'https://t.me/Skylinktechnologies',
        ],
        // Opening hours only when listed here, as schema.org strings, e.g. ['Mo-Fr 08:00-17:00', 'Sa 08:00-12:00']. Empty = not published.
        'opening_hours' => [],
    ],

    // Optional webmaster verification codes (empty = no tag).
    'bing_verification' => env('BING_SITE_VERIFICATION'),
    'google_verification' => env('GOOGLE_SITE_VERIFICATION'),

    // IndexNow (Bing and others): a key of 8-128 letters/digits/dashes, served at /<key>.txt. Empty = off.
    'indexnow_key' => env('INDEXNOW_KEY'),
];
