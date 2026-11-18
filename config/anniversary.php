<?php

/*
| Sky Link 5-year anniversary campaign. Single source of truth for the dates:
| templates and scripts never repeat them, they only ask Anniversary::isActive().
*/

return [

    'timezone' => 'Africa/Addis_Ababa',

    // Start is inclusive, end is exclusive (local time in the timezone above).
    'start' => '2026-11-23 00:00:00',
    'end' => '2026-12-07 00:00:00',

    // Preview the campaign outside its dates. Honoured only in the
    // environments below; production ignores it whatever the .env says.
    'force' => (bool) env('ANNIVERSARY_FORCE', false),
    'preview_environments' => ['local', 'staging', 'testing'],

];
