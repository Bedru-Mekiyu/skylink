<?php

namespace App\Models;

use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    protected $fillable = ['name', 'role', 'quote_en', 'quote_am', 'photo_path', 'original_path', 'rating', 'sort_order', 'visible', 'updated_by'];

    protected $casts = ['visible' => 'boolean', 'rating' => 'integer'];

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

    public function initials(): string
    {
        return collect(preg_split('/\s+/u', trim($this->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    /** Removes the row's files (web copy and the archived original). */
    public function deleteFiles(): void
    {
        if ($this->photo_path) {
            Storage::disk('public')->delete($this->photo_path);
        }
        if ($this->original_path) {
            Storage::disk('local')->delete($this->original_path);
        }
    }

    /**
     * Visible testimonials for the public strip (cached).
     *
     * @return list<array<string,mixed>>
     */
    public static function publicList(): array
    {
        try {
            return PublicCache::remember('testimonials.public', 300, fn () => static::query()->shown()->get()->map(fn (self $t) => [
                'name' => $t->name, 'role' => $t->role, 'en' => $t->quote_en, 'am' => $t->quote_am,
                'photo' => $t->photoUrl(), 'initials' => $t->initials(), 'rating' => $t->rating,
            ])->all());
        } catch (\Throwable) {
            return [];
        }
    }
}
