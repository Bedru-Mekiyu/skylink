<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    public const MODE_SUBSCRIPTION = 'subscription';
    public const MODE_ONE_TIME = 'one_time';
    public const MODE_QUOTE_ONLY = 'quote_only';

    protected $fillable = ['slug', 'name', 'description', 'image', 'pricing_mode', 'sort_order', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    public function isQuoteOnly(): bool
    {
        return $this->pricing_mode === self::MODE_QUOTE_ONLY;
    }

    public function isSubscription(): bool
    {
        return $this->pricing_mode === self::MODE_SUBSCRIPTION;
    }
}
