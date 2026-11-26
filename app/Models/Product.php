<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category',
        'product_category_id',
        'slug',
        'name',
        'image',
        'description',
        'price',
        'price_text',
        'show_price',
        'is_published',
        'is_archived',
        'sort_order',
        'plans_heading', 'quote_only', 'header_title',
        'header_subtitle',
        'header_image',
        'header_cta_text',
        'name_am',
        'description_am',
        'features_am',
        'features',
        'additional_info',
    ];

    protected $casts = [
        'features' => 'array',
        'features_am' => 'array',
        'show_price' => 'boolean',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Keep product_category_id in sync with the legacy category slug set by the admin form/seeders. */
    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->category && ($product->isDirty('category') || ! $product->product_category_id)) {
                $product->product_category_id = ProductCategory::firstOrCreate(
                    ['slug' => $product->category],
                    ['name' => \Illuminate\Support\Str::title(str_replace('-', ' ', $product->category))]
                )->id;
            }
        });
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /** True when the product's category is quote-only (e.g. data center, power): never priced or paid online. */
    public function isQuoteOnly(): bool
    {
        return $this->quote_only || (bool) $this->productCategory?->isQuoteOnly();
    }

    /** The Amharic field when the page is in Amharic and it is filled; otherwise the English one. */
    public function localized(string $field): mixed
    {
        $translated = app()->getLocale() === 'am' ? $this->getAttribute($field.'_am') : null;

        return filled($translated) ? $translated : $this->getAttribute($field);
    }

    /** What visitors may see: published and not archived. */
    public function scopeVisible($query)
    {
        return $query->where('products.is_published', true)->where('products.is_archived', false);
    }

    public function supportUrl(): string
    {
        return route('contact', ['type' => 'support', 'service' => $this->name]).'#contact-form';
    }

    public function options()
    {
        return $this->hasMany(ItemOption::class, 'item_id')->where('item_type', 'product')->orderBy('sort_order');
    }
}
