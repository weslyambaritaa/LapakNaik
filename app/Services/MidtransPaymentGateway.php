<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransPaymentGateway implements PaymentGateway
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapToken(Transaction $transaction): string
    {
        $transaction->loadMissing(['items.product', 'customer']);

        $itemDetails = $transaction->items->map(fn ($item) => [
            'id' => (string) $item->product_id,
            'name' => mb_substr($item->product->name, 0, 50),
            'price' => (int) $item->price,
            'quantity' => $item->quantity,
        ])->all();

        if ($transaction->discount > 0) {
            $itemDetails[] = [
                'id' => 'discount',
                'name' => 'Diskon',
                'price' => -1 * (int) $transaction->discount,
                'quantity' => 1,
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->invoice_number,
                'gross_amount' => (int) $transaction->total,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $transaction->customer?->name ?? 'Pelanggan',
                'phone' => $transaction->customer?->phone,
            ],
            'enabled_payments' => ['gopay', 'other_qris'],
            // Matches ExpireStalePendingOrders's sweep window, so Midtrans's
            // own QR expiry and our local stock-restore agree on timing.
            'expiry' => [
                'unit' => 'minutes',
                'duration' => PaymentGateway::ORDER_EXPIRY_MINUTES,
            ],
        ];

        return Snap::getSnapToken($params);
    }

    public function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production');
    }

    public function clientKey(): string
    {
        return (string) config('services.midtrans.client_key');
    }

    public function verifySignature(array $payload): bool
    {
        $expected = hash('sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            config('services.midtrans.server_key')
        );

        return hash_equals($expected, $payload['signature_key'] ?? '');
    }
}
