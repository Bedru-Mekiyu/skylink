<?php

/*
 * Company facts shown on the site, kept in ONE place.
 *
 * years_experience: the "5+ YEARS of EXPERIENCE" headline on the About page reads this value.
 * The homepage "Company overview" counter ("Years Experience") reads the same value.
 *
 * email: THE company email address, the one setting used everywhere: the Contact page card and its mailto link, the
 * inbox where every form is delivered (config/contact.php), and the sender address of all website emails
 * (config/mail.php). Change it with COMPANY_EMAIL in .env (one line).
 */
return [
    'email' => env('COMPANY_EMAIL', 'info@skylinkictet.com'),

    'years_experience' => 5,
];
