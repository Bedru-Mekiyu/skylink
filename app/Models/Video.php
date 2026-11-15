<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    // Allow these fields to be filled from a form
    protected $fillable = [
        'title',
        'slug',
        'description',
        'video_url',
        'thumbnail_image',
        'news_category_id',
    ];

    // Define the relationship to the Category model
    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    /**
     * Newest first. Videos have no separate publish date or status: a video is
     * public from the moment it is saved in the admin, and created_at is that
     * moment. The id breaks ties (ids alone are not in date order: imported
     * videos were numbered newest-first).
     */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /** "youtube", "tiktok" or null (unknown link). */
    public function getPlatformAttribute(): ?string
    {
        return match (true) {
            $this->youtube_id !== null => 'youtube',
            $this->tiktok_id !== null => 'tiktok',
            default => null,
        };
    }

    /** The 11-character YouTube id from watch, youtu.be, embed or shorts links. */
    public function getYoutubeIdAttribute(): ?string
    {
        $url = (string) $this->video_url;
        if (! preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be)/~i', $url)) {
            return null;
        }

        return preg_match('~(?:youtu\.be/|/embed/|/shorts/|/live/|[?&]v=)([A-Za-z0-9_-]{11})~', $url, $m) ? $m[1] : null;
    }

    /** The numeric TikTok video id from a tiktok.com/@user/video/<id> link. */
    public function getTiktokIdAttribute(): ?string
    {
        $url = (string) $this->video_url;
        if (! preg_match('~^https?://(?:www\.|m\.)?tiktok\.com/~i', $url)) {
            return null;
        }

        return preg_match('~/video/(\d{8,25})~', $url, $m) ? $m[1] : null;
    }

    /**
     * Player address for the pop-up, built only from the parsed id (never the
     * raw link). Loaded only after the visitor clicks "Watch video".
     */
    public function getEmbedUrlAttribute(): ?string
    {
        return match ($this->platform) {
            'youtube' => 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id.'?autoplay=1&rel=0&modestbranding=1&playsinline=1',
            'tiktok' => 'https://www.tiktok.com/player/v1/'.$this->tiktok_id.'?autoplay=1&rel=0&description=1&music_info=1',
            default => null,
        };
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_image ? asset('storage/'.$this->thumbnail_image) : null;
    }

    /** The date shown to visitors, in Addis Ababa time. */
    public function getDisplayDateAttribute()
    {
        return $this->created_at?->copy()->setTimezone(config('news.display_timezone', 'Africa/Addis_Ababa'));
    }
}
