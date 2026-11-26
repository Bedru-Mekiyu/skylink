<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'tx_ref',
        'gateway',
        'amount',
        'currency',
        'status',
        'gateway_reference',
        'raw_response',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_response' => 'array',
        'verified_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public static function generateTxRef(): string
    {
        return 'TX-' . time() . '-' . strtoupper(Str::random(6));
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }

    public function markAsSuccessful(string $gatewayRef, array $rawResponse = []): void
    {
        $this->update([
            'status' => 'successful',
            'gateway_reference' => $gatewayRef,
            'raw_response' => $rawResponse,
            'verified_at' => now(),
        ]);
    }
}
