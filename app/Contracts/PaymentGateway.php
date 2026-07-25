<?php

namespace App\Contracts;

use App\Models\Transaction;

interface PaymentGateway
{
    /**
     * How long an online order's QR stays valid before it's swept as expired
     * and its reserved stock released — shared by the gateway (so the QR
     * itself expires on schedule) and ExpireStalePendingOrders (the local
     * safety net for orders Midtrans never sends a final webhook for).
     */
    public const ORDER_EXPIRY_MINUTES = 60;

    /**
     * Create a payment session for the given transaction and return the
     * client-side token (e.g. Midtrans Snap token) used to open the payment UI.
     */
    public function createSnapToken(Transaction $transaction): string;

    /**
     * Whether the gateway is configured against production credentials.
     */
    public function isProduction(): bool;

    /**
     * The public/client-safe key the frontend needs to load the payment widget.
     */
    public function clientKey(): string;

    /**
     * Verify a webhook notification payload's signature.
     */
    public function verifySignature(array $payload): bool;
}
