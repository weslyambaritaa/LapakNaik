<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Models\Transaction;

class FakePaymentGateway implements PaymentGateway
{
    public function createSnapToken(Transaction $transaction): string
    {
        return 'fake-snap-token-'.$transaction->id;
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
