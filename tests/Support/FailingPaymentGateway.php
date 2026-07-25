<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Models\Transaction;

class FailingPaymentGateway implements PaymentGateway
{
    public function createSnapToken(Transaction $transaction): string
    {
        throw new \RuntimeException('Midtrans unreachable (simulated)');
    }

    public function isProduction(): bool
    {
        return false;
    }

    public function clientKey(): string
    {
        return 'fake-client-key';
    }

    public function verifySignature(array $payload): bool
    {
        return true;
    }
}
