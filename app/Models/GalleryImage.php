<?php

namespace App\Models;

use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** One photo of the About-page gallery (Admin > Gallery). Hidden instead of deleted. */
class GalleryImage extends Model
{
    protected $fillable = [
        'caption_en', 'caption_am', 'taken_on', 'sort_order', 'visible', 'thumb_path', 'display_path',
        'thumb_w', 'thumb_h', 'display_w', 'display_h', 'original_path', 'updated_by',
    ];

    protected $casts = ['visible' => 'boolean', 'taken_on' => 'date'];

    protected static function booted(): void
    {
        foreach (['saved', 'deleted'] as $event) {
            static::$event(fn () => PublicCache::bust());
        }
    }

    public function scopeShown(Builder $q): Builder
    {
        return $q->where('visible', true)->orderBy('sort_order')->orderBy('id');
    }

    public function altText(): string
    {
        return $this->caption_en ?: ($this->caption_am ?: 'Sky Link gallery photo');
    }

    /**
     * Plain rows for the public gallery (cached); [] when the table is missing or has nothing visible.
     *
     * @return list<array{id:int,alt:string,en:?string,am:?string,date:?string,thumb:string,tw:int,th:int,full:string,fw:int,fh:int}>
     */
    public static function publicList(): array
    {
        try {
            return PublicCache::remember('about.gallery', 300, fn () => static::query()->shown()->get()->map(fn (self $g) => [
                'id' => $g->id, 'alt' => $g->altText(), 'en' => $g->caption_en, 'am' => $g->caption_am,
                'date' => $g->taken_on?->format('M Y'),
                'thumb' => asset('storage/'.$g->thumb_path), 'tw' => $g->thumb_w, 'th' => $g->thumb_h,
                'full' => asset('storage/'.$g->display_path), 'fw' => $g->display_w, 'fh' => $g->display_h,
            ])->all());
        } catch (\Throwable) {
            return [];
        }
    }
}
