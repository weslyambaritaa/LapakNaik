<?php

namespace Tests\Feature\Midtrans;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingOnlineOrder(bool $withCustomer = false): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Webhook', 'slug' => 'toko-webhook-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        $customer = $withCustomer
            ? Customer::create(['business_id' => $business->id, 'name' => 'Fira', 'phone' => '081234599999'])
            : null;

        $product = Product::create(['business_id' => $business->id, 'name' => 'Ayam Geprek', 'price' => 16000, 'stock' => 10, 'unit' => 'porsi']);

        // Simulate what StorefrontOrderController::store already reserved.
        $product->decrement('stock', 2);

        $transaction = Transaction::create([
            'business_id' => $business->id,
            'customer_id' => $customer?->id,
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

        return [$transaction, $product, $customer];
    }

    private function signedPayload(Transaction $transaction, string $status, ?string $fraudStatus = null): array
    {
        $grossAmount = number_format($transaction->total, 2, '.', '');
        $statusCode = '200';

        $payload = [
            'order_id' => $transaction->invoice_number,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => $status,
        ];

        if ($fraudStatus) {
            $payload['fraud_status'] = $fraudStatus;
        }

        $payload['signature_key'] = hash('sha512', $payload['order_id'].$statusCode.$grossAmount.config('services.midtrans.server_key'));

        return $payload;
    }

    public function test_settlement_notification_marks_transaction_paid(): void
    {
        [$transaction] = $this->makePendingOnlineOrder();

        $response = $this->postJson(route('midtrans.callback'), $this->signedPayload($transaction, 'settlement'));

        $response->assertOk();
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
        $this->assertSame(Payment::STATUS_PAID, $transaction->payment->fresh()->status);
    }

    public function test_expire_notification_marks_transaction_expired_and_restores_stock(): void
    {
        [$transaction, $product] = $this->makePendingOnlineOrder();
        $stockBeforeExpiry = $product->fresh()->stock;

        $response = $this->postJson(route('midtrans.callback'), $this->signedPayload($transaction, 'expire'));

        $response->assertOk();
        $this->assertSame(Transaction::STATUS_EXPIRED, $transaction->fresh()->status);
        $this->assertSame(Payment::STATUS_FAILED, $transaction->payment->fresh()->status);
        $this->assertSame($stockBeforeExpiry + 2, $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('type', StockMovement::TYPE_IN)->count());
    }

    public function test_invalid_signature_is_rejected_and_leaves_transaction_untouched(): void
    {
        [$transaction] = $this->makePendingOnlineOrder();

        $payload = $this->signedPayload($transaction, 'settlement');
        $payload['signature_key'] = 'tampered';

        $response = $this->postJson(route('midtrans.callback'), $payload);

        $response->assertStatus(403);
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }

    public function test_repeat_notification_after_settlement_does_not_double_process(): void
    {
        [$transaction, $product] = $this->makePendingOnlineOrder();

        $this->postJson(route('midtrans.callback'), $this->signedPayload($transaction, 'settlement'))->assertOk();
        $stockAfterFirst = $product->fresh()->stock;

        // A stray late 'expire' notification for an already-settled order must not restore stock.
        $this->postJson(route('midtrans.callback'), $this->signedPayload($transaction, 'expire'))->assertOk();

        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
        $this->assertSame($stockAfterFirst, $product->fresh()->stock);
    }

    public function test_settlement_notification_awards_loyalty_points_to_the_customer(): void
    {
        [$transaction, , $customer] = $this->makePendingOnlineOrder(withCustomer: true);

        $this->postJson(route('midtrans.callback'), $this->signedPayload($transaction, 'settlement'))->assertOk();

        // total 32.000 / 10.000 = 3,2, dibulatkan ke bawah
        $this->assertSame(3, $customer->fresh()->loyalty_points);
    }

    public function test_repeat_settlement_notification_does_not_double_award_points(): void
    {
        [$transaction, , $customer] = $this->makePendingOnlineOrder(withCustomer: true);

        $this->postJson(route('midtrans.callback'), $this->signedPayload($transaction, 'settlement'))->assertOk();
        $this->postJson(route('midtrans.callback'), $this->signedPayload($transaction, 'settlement'))->assertOk();

        $this->assertSame(3, $customer->fresh()->loyalty_points);
    }
}
