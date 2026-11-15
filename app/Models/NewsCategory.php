<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\News;

class NewsCategory extends Model
{
    protected $fillable = ['name'];

    /**
     * Labels shown on the site for names stored in the database.
     * The database keeps "Innovations" (it is shared with the admin and the
     * videos); the site shows "Innovation" as agreed.
     */
    private const LABELS = [
        'Innovations' => 'Innovation',
    ];

    /** Order of the filter chips after "All Content". */
    public const DISPLAY_ORDER = ['News', 'Innovations', 'Events'];

    public function news()
    {
        return $this->hasMany(News::class, 'news_category_id');
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->name] ?? $this->name;
    }

    /** URL value for ?category= (e.g. "news", "innovation", "events"). */
    public function getSlugAttribute(): string
    {
        return Str::slug($this->label);
    }
}
