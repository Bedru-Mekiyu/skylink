<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\PriceVisibility;

class ItemOption extends Model
{
    use SoftDeletes;

    protected $table = 'item_options';

    protected $fillable = [
        'item_type',
        'item_id',
        'name',
        'short_features',
        'price_amount',
        'price_display_text',
        'term_prices',
        'currency',
        'billing_term',
        'show_billing_term',
        'show_price',
        'is_active',
        'is_ready',
        'needs_review',
        'is_payable_online',
        'sort_order',
        'tagline',
        'is_recommended',
    ];

    protected $casts = [
        'short_features' => 'array',
        'term_prices' => 'array',
        'price_amount' => 'decimal:2',
        'show_billing_term' => 'boolean',
        'show_price' => 'boolean',
        'is_active' => 'boolean',
        'is_ready' => 'boolean',
        'needs_review' => 'boolean',
        'is_payable_online' => 'boolean',
        'sort_order' => 'integer',
        'is_recommended' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    /**
     * Get parent item (Service or Product)
     */
    public function getItemAttribute(): mixed
    {
        if ($this->relationLoaded('service')) {
            return $this->getRelation('service');
        }
        if ($this->relationLoaded('product')) {
            return $this->getRelation('product');
        }
        return $this->item_type === 'service' ? $this->service : $this->product;
    }

    /**
     * Check if price should be displayed for this option
     * Requires: Global Switch ON, Item Switch ON, Option Switch ON, is_ready ON, and has valid price
     */
    public function isPriceVisible(mixed $parent = null): bool
    {
        if (! $this->is_ready || ! $this->show_price) {
            return false;
        }

        if ($parent) {
            if ($this->item_type === 'service') {
                $this->setRelation('service', $parent);
            } else {
                $this->setRelation('product', $parent);
            }
        }

        // Global, quote-only, item and option switches; the price itself is the option's, not the old item field.
        return PriceVisibility::shouldShowOption($this);
    }

    /**
     * Determine if this option has multiple billing cycle terms
     */
    public function hasMultipleTerms(): bool
    {
        // A one-time or per-project plan is sold once, even if period prices are still stored on it.
        return ! in_array($this->billing_term, ['one-time', 'project-based'], true)
            && ! empty($this->term_prices) && is_array($this->term_prices);
    }

    /**
     * Get price for a specific billing term (monthly, quarterly, semi-annually, annually)
     */
    public function getPriceForTerm(?string $term = null): ?float
    {
        if ($term && $this->hasMultipleTerms() && isset($this->term_prices[$term]) && $this->term_prices[$term] !== null && $this->term_prices[$term] !== '') {
            return (float) $this->term_prices[$term];
        }

        return $this->price_amount !== null ? (float) $this->price_amount : null;
    }

    /**
     * Get structured list of available billing terms with labels and prices
     */
    public function getAvailableTerms(): array
    {
        $termLabels = [
            'annually' => 'Annually',
            'semi-annually' => 'Semi Annually',
            'quarterly' => 'Quarterly',
            'monthly' => 'Monthly',
            'one-time' => 'One-time',
            'project-based' => 'Project-based',
        ];

        $terms = [];
        $currSymbol = $this->currency === 'USD' ? '$' : 'Br';

        if ($this->hasMultipleTerms()) {
            foreach ($termLabels as $key => $label) {
                if (isset($this->term_prices[$key]) && $this->term_prices[$key] !== null && $this->term_prices[$key] !== '') {
                    $amt = (float) $this->term_prices[$key];
                    $terms[$key] = [
                        'key' => $key,
                        'label' => $label,
                        'amount' => $amt,
                        'formatted' => \App\Support\Money::format($amt, $this->currency),
                    ];
                }
            }
        }

        // If no term_prices defined, use default billing_term with price_amount
        if (empty($terms)) {
            $defaultTerm = $this->billing_term ?: 'monthly';
            $defaultLabel = $termLabels[$defaultTerm] ?? ucfirst(str_replace('-', ' ', $defaultTerm));
            $amt = $this->price_amount !== null ? (float) $this->price_amount : null;
            $terms[$defaultTerm] = [
                'key' => $defaultTerm,
                'label' => $defaultLabel,
                'amount' => $amt,
                'formatted' => $amt !== null
                    ? \App\Support\Money::format($amt, $this->currency)
                    : ($this->price_display_text ?: 'Quote Request'),
            ];
        }

        return $terms;
    }

    /**
     * Formatted price string for public display (falls back to default term)
     */
    public function getFormattedPriceAttribute(): string
    {
        if (! $this->isPriceVisible()) {
            return 'Quote Request';
        }

        if (! empty($this->price_display_text)) {
            return \App\Support\Money::display($this->price_display_text, $this->currency);
        }

        if ($this->price_amount !== null) {
            return \App\Support\Money::format($this->price_amount, $this->currency);
        }

        return 'Quote Request';
    }
}
