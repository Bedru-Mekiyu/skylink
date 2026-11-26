<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A message sent through a website form. Saved first, always; the emails
 * (to the team inbox and to the visitor) are sent afterwards from the queue.
 */
class Inquiry extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'contact' => 'General question',
        'support' => 'Support request',
        'quote' => 'Quote request',
        'service' => 'Service request',
        'subscription' => 'Plan subscription',
        'cart_request' => 'Cart request',
    ];

    public const TYPES_AM = [
        'contact' => 'አጠቃላይ ጥያቄ',
        'support' => 'የድጋፍ ጥያቄ',
        'quote' => 'የዋጋ ጥያቄ',
        'service' => 'የአገልግሎት ጥያቄ',
        'subscription' => 'የጥቅል ምዝገባ',
        'cart_request' => 'የጋሪ ጥያቄ',
    ];

    public const STATUSES = [
        'new' => 'New',
        'in_progress' => 'In progress',
        'done' => 'Done',
        'spam' => 'Spam',
    ];

    protected $fillable = [
        'type', 'name', 'email', 'phone', 'company', 'service', 'item_type', 'item_id',
        'plan', 'billing_term', 'estimated_price', 'message',
        'language', 'source_page', 'ip_hash', 'user_agent',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'team_notified_at' => 'datetime',
        'visitor_confirmed_at' => 'datetime',
        'anonymized_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Inquiry $inquiry) {
            $inquiry->reference ??= self::newReference();
            $inquiry->status ??= 'new';
        });
        // A permanent delete also removes the original row of an imported older message (else it would reappear on the older page).
        static::forceDeleted(function (Inquiry $inquiry) {
            if ($inquiry->legacy_table && $inquiry->legacy_id && in_array($inquiry->legacy_table, ['contact_messages', 'messages'], true)) {
                \Illuminate\Support\Facades\DB::table($inquiry->legacy_table)->where('id', $inquiry->legacy_id)->delete();
            }
        });
    }

    /** Short, unique, easy to read out on the phone: SK-261115-7KQ4D. */
    public static function newReference(): string
    {
        do {
            // Random part without look-alike characters (0/O, 1/I/L).
            $random = strtr(strtoupper(Str::random(5)), ['0' => '7', 'O' => 'Q', 'I' => 'K', 'L' => 'M', '1' => '4']);
            $ref = 'SK-'.now()->format('ymd').'-'.$random;
        } while (self::withTrashed()->where('reference', $ref)->exists());

        return $ref;
    }

    /** The team inbox for this type (config/contact.php; one .env line by default). */
    public static function inboxFor(string $type): ?string
    {
        return config('contact.inboxes.'.$type) ?: config('contact.inbox');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function items()
    {
        return $this->hasMany(InquiryItem::class, 'inquiry_id');
    }
}
