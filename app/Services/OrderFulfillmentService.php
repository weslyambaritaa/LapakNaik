<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\Transaction;

class OrderFulfillmentService
{
    /**
     * Both entry points below are no-ops unless the order is still pending —
     * a webhook retry or a race with the expiry sweep must never double-credit
     * a sale or double-restore stock.
     */
    public function markPaid(Transaction $transaction): void
    {
        if ($transaction->status !== Transaction::STATUS_PENDING) {
            return;
        }

        $transaction->update(['status' => Transaction::STATUS_COMPLETED]);
        $transaction->payment?->update(['status' => Payment::STATUS_PAID]);

        // Online orders always have a customer (StorefrontOrderController
        // registers one via phone at checkout), but guard anyway rather than
        // assume — mirrors the same check the POS checkout uses.
        if ($transaction->customer_id) {
            $transaction->customer->awardLoyaltyPoints((int) $transaction->total);
        }
    }

    public function markExpired(Transaction $transaction): void
    {
        if ($transaction->status !== Transaction::STATUS_PENDING) {
            return;
        }

        $this->restoreStock($transaction);

        $transaction->update(['status' => Transaction::STATUS_EXPIRED]);
        $transaction->payment?->update(['status' => Payment::STATUS_FAILED]);
    }

    private function restoreStock(Transaction $transaction): void
    {
        $transaction->loadMissing('items.product');

        foreach ($transaction->items as $item) {
            $item->product->increment('stock', $item->quantity);

            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $item->quantity,
                'reference' => $transaction->invoice_number,
                'note' => 'Pesanan online dibatalkan/kadaluarsa — stok dikembalikan',
            ]);
        }
    }
}
