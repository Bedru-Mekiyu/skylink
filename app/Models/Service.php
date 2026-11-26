<?php

// app/Models/Service.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'desc',
        'image',
        'price',
        'show_price',
        'is_published',
        'is_archived',
        'sort_order',
        'plans_heading', 'header_title',
        'header_subtitle',
        'header_image',
        'header_cta_text',
        'title_am',
        'desc_am',
        'features_am',
        'features',
        'category_id',
        'quote_only',
    ];

    protected $casts = [
        'features' => 'array',
        'features_am' => 'array',
        'show_price' => 'boolean',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
        'sort_order' => 'integer',
        'quote_only' => 'boolean',
    ];

    /** The Amharic field when the page is in Amharic and it is filled; otherwise the English one. */
    public function localized(string $field): mixed
    {
        $translated = app()->getLocale() === 'am' ? $this->getAttribute($field.'_am') : null;

        return filled($translated) ? $translated : $this->getAttribute($field);
    }

    /** What visitors may see: published and not archived. */
    public function scopeVisible($query)
    {
        return $query->where('services.is_published', true)->where('services.is_archived', false);
    }

    /** Priced per project: no price anywhere, no cart; visitors go to the support form. */
    public function isQuoteOnly(): bool
    {
        return $this->quote_only || $this->category?->pricing_mode === 'quote_only';
    }

    public function supportUrl(): string
    {
        return route('contact', ['type' => 'support', 'service' => $this->title]).'#contact-form';
    }
    

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function options()
    {
        return $this->hasMany(ItemOption::class, 'item_id')->where('item_type', 'service')->orderBy('sort_order');
    }



    public function getPriceFormattedAttribute()
{
    return is_numeric($this->price) ? '' . number_format($this->price, 2) : $this->price;
}

}
