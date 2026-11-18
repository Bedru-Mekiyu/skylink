<?php

// app/Models/ServiceCategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'card_title', 'filter_label', 'description', 'image', 'sort_order', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Cards shown on /services: published ones, in the admin's order. */
    public function scopeForServicesPage(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Card heading (falls back to the full name). */
    public function getCardHeadingAttribute(): string
    {
        return $this->card_title ?: $this->name;
    }

    /** Short label for the filter button (falls back to the card heading). */
    public function getFilterTextAttribute(): string
    {
        return $this->filter_label ?: $this->card_heading;
    }

    /** Card image URL: uploads live under storage/, the original cards under assets/. */
    public function getImageUrlAttribute(): string
    {
        return asset($this->image ?: 'assets/images/placeholder.png');
    }
   


    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
    // Get children (subcategories)
    public function children()
    {
        return $this->hasMany(ServiceCategory::class, 'parent_id');
    }

    // Get parent (optional)
    public function parent()
    {
        return $this->belongsTo(ServiceCategory::class, 'parent_id');
    }
}
