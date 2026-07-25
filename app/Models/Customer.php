<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['business_id', 'name', 'phone', 'email', 'loyalty_points'])]
class Customer extends Model
{
    use HasFactory;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * 1 poin per Rp 10.000 dari nilai transaksi, dibulatkan ke bawah — dipakai
     * oleh checkout Kasir (langsung, saat transaksi selesai) dan penyelesaian
     * pesanan online (saat pembayaran dikonfirmasi), supaya rumusnya sama
     * persis di kedua jalur.
     */
    public function awardLoyaltyPoints(int $transactionTotal): void
    {
        $this->increment('loyalty_points', intdiv($transactionTotal, 10000));
    }
}
