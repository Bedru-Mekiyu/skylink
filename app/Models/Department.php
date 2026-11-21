<?php

namespace App\Models;

use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Department extends Model
{
    public const ACCENTS = ['primary', 'info', 'success', 'warning', 'danger', 'secondary'];
    private const SECOND = ['primary' => 'success', 'info' => 'warning', 'success' => 'primary', 'warning' => 'info', 'danger' => 'primary', 'secondary' => 'primary'];

    protected $fillable = ['name', 'tagline', 'icon', 'image_path', 'image_alt', 'image_label', 'original_path', 'accent', 'stats', 'sort_order', 'visible', 'updated_by'];

    protected $casts = ['stats' => 'array', 'visible' => 'boolean'];

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

    /** A site file ("assets/...") or an uploaded copy (storage). */
    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return str_starts_with($this->image_path, 'assets/') ? asset($this->image_path) : asset('storage/'.$this->image_path);
    }

    /** Colour of stat number $i: the accent and its partner colour, alternating (as the original two cards did). */
    public function statColor(int $i): string
    {
        $a = in_array($this->accent, self::ACCENTS, true) ? $this->accent : 'primary';

        return $i % 2 === 0 ? $a : self::SECOND[$a];
    }

    /** Removes uploaded files only (never the site's own assets/ files). */
    public function deleteFiles(): void
    {
        if ($this->image_path && ! str_starts_with($this->image_path, 'assets/')) {
            Storage::disk('public')->delete($this->image_path);
        }
        if ($this->original_path) {
            Storage::disk('local')->delete($this->original_path);
        }
    }

    /**
     * Visible departments for the About page (cached).
     *
     * @return list<array<string,mixed>>
     */
    public static function publicList(): array
    {
        try {
            return PublicCache::remember('departments.public', 300, fn () => static::query()->shown()->get()->map(fn (self $d) => [
                'name' => $d->name, 'tagline' => $d->tagline, 'icon' => $d->icon ?: 'fas fa-users', 'image' => $d->imageUrl(), 'alt' => $d->image_alt ?: $d->name,
                'label' => $d->image_label, 'accent' => in_array($d->accent, self::ACCENTS, true) ? $d->accent : 'primary',
                'stats' => collect($d->stats ?? [])->values()->map(fn ($s, $i) => ['n' => $s['n'] ?? '', 'l' => $s['l'] ?? '', 'color' => $d->statColor($i)])->all(),
            ])->all());
        } catch (\Throwable) {
            return [];
        }
    }
}
