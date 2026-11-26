<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryItem extends Model
{
    protected $table = 'inquiry_items';

    protected $fillable = [
        'inquiry_id',
        'item_type',
        'item_id',
        'option_id',
        'item_name',
        'option_name',
        'billing_term',
        'currency',
        'quantity',
        'unit_price_amount',
        'unit_price_display',
        'line_total_amount',
        'is_price_visible',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_amount' => 'decimal:2',
        'line_total_amount' => 'decimal:2',
        'is_price_visible' => 'boolean',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ItemOption::class, 'option_id');
    }
}
