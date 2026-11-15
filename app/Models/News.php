<?php

namespace App\Models;

use App\Support\NewsBody;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    // A soft-deleted article is hidden from every page but kept in the
    // database (e.g. a duplicate; see duplicate_of_id).
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_SCHEDULED];

    public const LANGUAGES = ['am' => 'Amharic', 'en' => 'English'];

    // Slug, status, publish date and image paths are set by the admin
    // controller, never mass-assigned from a request.
    protected $fillable = [
        'title',
        'news_category_id',
        'excerpt',
        'content',
        'language',
        'url_title',
        'is_featured',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Rows created outside the admin form (seeders, imports, tests) that are
        // published without a date get one, so ordering by date always works.
        static::saving(function (News $news) {
            if ($news->status === null) {
                $news->status = self::STATUS_PUBLISHED;
            }
            if ($news->status === self::STATUS_PUBLISHED && $news->published_at === null) {
                $news->published_at = $news->created_at ?? now();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    /** For a hidden duplicate: the article that stayed visible. */
    public function duplicateOf()
    {
        return $this->belongsTo(News::class, 'duplicate_of_id');
    }

    public function slugRedirects()
    {
        return $this->hasMany(NewsSlugRedirect::class);
    }

    /**
     * What visitors may see: published articles, and scheduled ones whose time
     * has come. The time is checked on every request, so a scheduled article
     * appears on time even when the cron job (news:publish-scheduled) is not set up.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', self::STATUS_PUBLISHED)
                ->orWhere(function (Builder $q) {
                    $q->where('status', self::STATUS_SCHEDULED)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now());
                });
        });
    }

    /** Newest first by publish date (id breaks ties). */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    public function isVisible(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            || ($this->status === self::STATUS_SCHEDULED && $this->published_at && $this->published_at->lte(now()));
    }

    /**
     * Server-side search in title, excerpt and body.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }
        $like = '%'.addcslashes($term, '\\%_').'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('content', 'like', $like);
        });
    }

    /**
     * "am" or "en". Uses the language chosen in the admin form; for older
     * articles without one, "am" when the title contains Ethiopic script.
     */
    public function getLanguageAttribute(?string $value): string
    {
        if (isset(self::LANGUAGES[$value])) {
            return $value;
        }

        return preg_match('/\p{Ethiopic}/u', (string) ($this->attributes['title'] ?? '')) ? 'am' : 'en';
    }

    /**
     * The date shown to visitors, in Addis Ababa time (the database stays in
     * UTC). Used by every public page, card and meta tag, so an article
     * published at 00:30 in Addis Ababa shows that day, not the previous one.
     */
    public function getDisplayDateAttribute()
    {
        $date = $this->published_at ?? $this->created_at;

        return $date?->copy()->setTimezone(config('news.display_timezone'));
    }

    /** Web copy (max 1600 px wide) for the article page and featured story. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    /** JPEG for share previews (og:image); falls back to the web copy. */
    public function getShareImageUrlAttribute(): ?string
    {
        return $this->image_share ? asset('storage/'.$this->image_share) : $this->image_url;
    }

    /** Small copy for cards; falls back to the web copy. */
    public function getThumbUrlAttribute(): ?string
    {
        return $this->image_thumb ? asset('storage/'.$this->image_thumb) : $this->image_url;
    }

    /**
     * The article body as safe HTML. Bodies saved by the admin form are HTML
     * (sanitised on save, and again here in case the database was edited by
     * hand); older bodies are plain text and are turned into paragraphs.
     */
    public function getBodyHtmlAttribute(): string
    {
        return $this->content_format === 'html'
            ? NewsBody::sanitize((string) $this->content)
            : NewsBody::fromPlainText((string) $this->content);
    }

    /**
     * Estimated reading time in minutes (about 200 words a minute).
     */
    public function getReadingMinutesAttribute(): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags((string) $this->content)), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words) / 200));
    }
}
