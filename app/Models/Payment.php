<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'method', 'amount', 'status', 'gateway_reference'])]
class Payment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_QRIS = 'qris';

    // QRIS milik toko sendiri (gambar statis, dikonfirmasi manual oleh staf) —
    // beda dari METHOD_QRIS di atas yang lewat gateway Midtrans dan
    // terkonfirmasi otomatis lewat webhook.
    public const METHOD_QRIS_STORE = 'qris_store';

    public const METHOD_TRANSFER = 'transfer';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
