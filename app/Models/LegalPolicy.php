<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPolicy extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
        'content_am',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('content');
    }

    public function getLocalizedContentAttribute(): ?string
    {
        $locale = app()->getLocale();
        if ($locale === 'am' && !empty($this->content_am)) {
            return $this->content_am;
        }
        return $this->content;
    }
}
