<?php

/*
 * Company phone numbers and address: the one source for the contact
 * launcher (partials/contact-launcher.blade.php) and the top navbar
 * (layouts/master.blade.php). Where form messages are emailed is a
 * separate setting, in config/contact.php.
 */
return [
    'phones' => ['+251-974-967171', '+251-936-177777'],

    // Full address, as shown (and copied) in the contact launcher.
    'address' => 'A.A, Bole Tropical Mall No. 217',

    // Short address for the navbar.
    'address_short' => 'Bole, Tropical Mall, Addis Ababa',
];
