<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_type',
        'item_id',
        'option_id',
        'title',
        'option_name',
        'billing_term',
        'unit_price',
        'quantity',
        'line_total',
        'currency',
        'billing_subscription_id',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ItemOption::class, 'option_id');
    }
}
