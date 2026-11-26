<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** One recurring agreement: same lines, same price, every billing term. */
class BillingSubscription extends Model
{
    /** Billing terms that repeat, in months. */
    public const TERM_MONTHS = ['monthly' => 1, 'quarterly' => 3, 'semi-annually' => 6, 'annually' => 12];

    protected $fillable = [
        'reference', 'first_order_id', 'customer_name', 'customer_email', 'customer_phone', 'company',
        'billing_term', 'amount', 'currency', 'status', 'current_period_end', 'cancelled_at',
    ];

    protected $casts = ['amount' => 'decimal:2', 'current_period_end' => 'date', 'cancelled_at' => 'datetime'];

    public static function newReference(): string
    {
        return 'SUB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    public function firstOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'first_order_id');
    }

    public function renewalOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'billing_subscription_id');
    }
}
