<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'company',
        'notes',
        'subtotal',
        'tax',
        'total',
        'currency',
        'status',
        'paid_at',
        'renewal_due_date',
        'payment_method',
        'billing_term',
        'renewal_note',
        'billing_subscription_id',
        'period_start',
        'reminder_sent_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'renewal_due_date' => 'date',
        'period_start' => 'date',
        'reminder_sent_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id')->latestOfMany();
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /** Waiting for money: new, or a renewal that has not been paid yet. */
    public function isPayable(): bool
    {
        return $this->status === 'pending' && $this->payment_method === 'chapa' && (float) $this->total > 0;
    }

    /** Called by PaymentProcessor only, inside its transaction and row lock. */
    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid', 'paid_at' => now()]);

        AdminAuditLog::record('order.paid', 'Order', $this->id, [
            'order_number' => $this->order_number,
            'total' => $this->total,
            'currency' => $this->currency,
        ]);
    }

    public function calculateRenewalDueDate(?\Carbon\CarbonInterface $fromDate = null): ?\Carbon\CarbonInterface
    {
        $from = $fromDate ? \Illuminate\Support\Carbon::parse($fromDate) : ($this->paid_at ? \Illuminate\Support\Carbon::parse($this->paid_at) : now());

        return match ($this->billing_term) {
            'monthly' => $from->copy()->addMonth(),
            'quarterly' => $from->copy()->addMonths(3),
            'semi-annually' => $from->copy()->addMonths(6),
            'annually' => $from->copy()->addYear(),
            default => null,
        };
    }

    public function scopeRenewalsDue($query, int $days = 14)
    {
        return $query->where('status', 'paid')
            ->whereNotNull('billing_term')
            ->whereIn('billing_term', ['monthly', 'quarterly', 'semi-annually', 'annually'])
            ->where(function ($q) use ($days) {
                $cutoff = now()->addDays($days)->toDateString();
                $q->where('renewal_due_date', '<=', $cutoff)
                  ->orWhereNull('renewal_due_date');
            })
            ->orderBy('renewal_due_date', 'asc');
    }
}
