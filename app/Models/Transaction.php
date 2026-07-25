<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'business_id', 'customer_id', 'user_id', 'invoice_number',
    'subtotal', 'discount', 'total', 'status', 'channel',
])]
class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_VOID = 'void';

    public const CHANNEL_POS = 'pos';

    public const CHANNEL_ONLINE = 'online';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Format: INV-YYYYMMDD-#### restarting daily per business, with a
     * uniqueness fallback since invoice_number is globally unique (Midtrans
     * order_id has no business scoping to lean on).
     */
    public static function nextInvoiceNumber(int $businessId): string
    {
        $today = now()->format('Ymd');

        $sequence = static::where('business_id', $businessId)
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        $invoiceNumber = sprintf('INV-%s-%04d', $today, $sequence);

        while (static::where('invoice_number', $invoiceNumber)->exists()) {
            $sequence++;
            $invoiceNumber = sprintf('INV-%s-%04d', $today, $sequence);
        }

        return $invoiceNumber;
    }
}
