<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'project_url',
        'slug',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    /** Newest first (id breaks ties). */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function scopeWithVideo(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNotNull('video_mp4')->orWhereNotNull('video_webm'));
    }

    public function getHasVideoAttribute(): bool
    {
        return filled($this->video_mp4) || filled($this->video_webm);
    }

    public function getImageUrlAttribute(): ?string
    {
        return self::mediaUrl($this->image);
    }

    public function getVideoMp4UrlAttribute(): ?string
    {
        return self::mediaUrl($this->video_mp4);
    }

    public function getVideoWebmUrlAttribute(): ?string
    {
        return self::mediaUrl($this->video_webm);
    }

    /** Still frame shown before/instead of the clip: the clip's poster, else the project image. */
    public function getPosterUrlAttribute(): ?string
    {
        return self::mediaUrl($this->video_poster) ?? $this->image_url;
    }

    /** The project link, only when it is a plain http(s) address. */
    public function getSafeUrlAttribute(): ?string
    {
        return preg_match('~^https?://~i', (string) $this->project_url) ? $this->project_url : null;
    }

    /**
     * Library clips live in public/assets/... (versioned by file time);
     * uploads and images on the public disk (storage/...).
     */
    public static function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }
        if (str_starts_with($path, 'assets/')) {
            $file = public_path($path);

            return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
        }

        return asset('storage/'.$path);
    }
}
