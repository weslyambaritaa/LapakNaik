<?php

namespace Tests\Feature\Console;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulatePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingOnlineOrder(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Simulasi', 'slug' => 'toko-simulasi-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        $product = Product::create(['business_id' => $business->id, 'name' => 'Ayam Geprek', 'price' => 16000, 'stock' => 10, 'unit' => 'porsi']);
        $product->decrement('stock', 2);

        $transaction = Transaction::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => 32000,
            'discount' => 0,
            'total' => 32000,
            'status' => Transaction::STATUS_PENDING,
            'channel' => Transaction::CHANNEL_ONLINE,
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 16000,
            'subtotal' => 32000,
        ]);

        $transaction->payment()->create([
            'method' => Payment::METHOD_QRIS,
            'amount' => 32000,
            'status' => Payment::STATUS_PENDING,
            'gateway_reference' => $transaction->invoice_number,
        ]);

        return [$transaction, $product];
    }

    public function test_marks_a_pending_order_as_paid(): void
    {
        [$transaction] = $this->makePendingOnlineOrder();

        $this->artisan('payment:simulate', ['invoice' => $transaction->invoice_number])
            ->assertSuccessful();

        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
        $this->assertSame(Payment::STATUS_PAID, $transaction->payment->fresh()->status);
    }

    public function test_fail_option_marks_the_order_expired_and_restores_stock(): void
    {
        [$transaction, $product] = $this->makePendingOnlineOrder();
        $stockBeforeExpiry = $product->fresh()->stock;

        $this->artisan('payment:simulate', ['invoice' => $transaction->invoice_number, '--fail' => true])
            ->assertSuccessful();

        $this->assertSame(Transaction::STATUS_EXPIRED, $transaction->fresh()->status);
        $this->assertSame($stockBeforeExpiry + 2, $product->fresh()->stock);
    }

    public function test_fails_for_an_unknown_invoice(): void
    {
        $this->artisan('payment:simulate', ['invoice' => 'INV-DOES-NOT-EXIST'])
            ->assertFailed();
    }

    public function test_fails_for_an_already_settled_order(): void
    {
        [$transaction] = $this->makePendingOnlineOrder();
        $transaction->update(['status' => Transaction::STATUS_COMPLETED]);

        $this->artisan('payment:simulate', ['invoice' => $transaction->invoice_number])
            ->assertFailed();
    }

    public function test_is_disabled_in_production(): void
    {
        [$transaction] = $this->makePendingOnlineOrder();

        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('payment:simulate', ['invoice' => $transaction->invoice_number])
            ->assertFailed();

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }
}
