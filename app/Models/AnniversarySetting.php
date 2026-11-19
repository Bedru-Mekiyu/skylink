<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The single row behind Admin > Anniversary Ceremony (see App\Support\Anniversary). */
class AnniversarySetting extends Model
{
    public const MODES = ['auto', 'off', 'on'];

    protected $fillable = ['mode', 'starts_at', 'ends_at', 'admin_preview'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'admin_preview' => 'boolean',
    ];
}
