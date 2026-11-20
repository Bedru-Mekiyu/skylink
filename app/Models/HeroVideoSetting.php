<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The single row behind Admin > Homepage Video (see App\Support\HeroVideo). */
class HeroVideoSetting extends Model
{
    public const KEYS = ['desktop_mp4', 'desktop_webm', 'mobile_mp4', 'poster'];

    protected $fillable = ['current', 'previous', 'updated_by'];

    protected $casts = [
        'current' => 'array',
        'previous' => 'array',
    ];
}
