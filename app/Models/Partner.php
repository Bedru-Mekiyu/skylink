<?php

namespace App\Models;

use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** One logo in the homepage "Trusted Partners" marquee (Admin > Partners). */
class Partner extends Model
{
    protected $fillable = [
        'name', 'alt', 'website_url', 'description', 'since_year', 'featured', 'active', 'sort_order',
        'logo_path', 'logo_fallback', 'legacy_src', 'logo_w', 'logo_h', 'original_path', 'updated_by',
    ];

    protected $casts = ['featured' => 'boolean', 'active' => 'boolean'];

    protected static function booted(): void
    {
        // Any change rebuilds the cached homepage data (same pattern as the other public content).
        foreach (['saved', 'deleted'] as $event) {
            static::$event(fn () => PublicCache::bust());
        }
    }

    /** Featured first, then the admin's order. */
    public function scopeForHome(Builder $q): Builder
    {
        return $q->where('active', true)->orderByDesc('featured')->orderBy('sort_order')->orderBy('id');
    }

    /** Main image URL: the WebP web copy, or the original static file of an imported row. */
    public function webpUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    /** Fallback / only image URL (what <img src> points to). */
    public function imgUrl(): string
    {
        if ($this->logo_fallback) {
            return asset('storage/'.$this->logo_fallback);
        }

        return $this->legacy_src ? asset($this->legacy_src) : asset('assets/images/skylink-logo.webp');
    }

    /**
     * Plain rows for the homepage marquee (cached), or [] when the table is missing or empty:
     * the homepage then falls back to its static logo list.
     *
     * @return list<array{name:string,alt:string,url:?string,img:string,webp:?string,w:?int,h:?int}>
     */
    public static function homeList(): array
    {
        try {
            return PublicCache::remember('home.partners', 300, fn () => static::query()->forHome()->get()->map(fn (self $p) => [
                'name' => $p->name, 'alt' => $p->altText(), 'url' => $p->website_url,
                'img' => $p->imgUrl(), 'webp' => $p->webpUrl(), 'w' => $p->logo_w, 'h' => $p->logo_h,
            ])->all());
        } catch (\Throwable) {
            return [];
        }
    }

    public function altText(): string
    {
        return $this->alt !== null && $this->alt !== '' ? $this->alt : $this->name;
    }
}
