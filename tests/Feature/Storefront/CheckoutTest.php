<?php

namespace Tests\Feature\Storefront;

use App\Contracts\PaymentGateway;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FailingPaymentGateway;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakePaymentGateway());
    }

    private function makeBusiness(): Business
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Online', 'slug' => 'toko-online-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return $business;
    }

    public function test_guest_can_place_an_online_order_and_receives_a_snap_token(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Nasi Goreng', 'price' => 18000, 'stock' => 10, 'unit' => 'porsi']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Andi',
            'customer_phone' => '081234567890',
            'payment_method' => Payment::METHOD_QRIS,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertOk()->assertJsonStructure(['transaction_id', 'snap_token', 'is_production', 'order_status_url']);

        $transaction = Transaction::first();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(Transaction::CHANNEL_ONLINE, $transaction->channel);
        $this->assertNull($transaction->user_id);
        $this->assertSame(36000, $transaction->total);
        $this->assertSame(Payment::STATUS_PENDING, $transaction->payment->status);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_checkout_without_a_phone_number_leaves_the_order_anonymous(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Es Jeruk', 'price' => 8000, 'stock' => 10, 'unit' => 'gelas']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Anonim',
            'payment_method' => Payment::METHOD_QRIS,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertOk();

        $transaction = Transaction::first();
        $this->assertNull($transaction->customer_id);
        $this->assertSame(0, Customer::count());
    }

    public function test_checkout_with_an_empty_phone_string_also_leaves_the_order_anonymous(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Es Kelapa', 'price' => 9000, 'stock' => 10, 'unit' => 'gelas']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Anonim',
            'customer_phone' => '',
            'payment_method' => Payment::METHOD_QRIS,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertOk();

        $transaction = Transaction::first();
        $this->assertNull($transaction->customer_id);
        $this->assertSame(0, Customer::count());
    }

    public function test_checkout_rejects_when_stock_is_insufficient(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Es Teh', 'price' => 5000, 'stock' => 1, 'unit' => 'gelas']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Budi',
            'customer_phone' => '081234567891',
            'payment_method' => Payment::METHOD_QRIS,
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('items');
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, Transaction::count());
    }

    public function test_checkout_reuses_existing_customer_by_phone(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Kopi', 'price' => 6000, 'stock' => 20, 'unit' => 'gelas']);

        $payload = [
            'customer_name' => 'Citra',
            'customer_phone' => '081234567892',
            'payment_method' => Payment::METHOD_QRIS,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $this->postJson(route('storefront.checkout', $business->slug), $payload)->assertOk();
        $this->postJson(route('storefront.checkout', $business->slug), $payload)->assertOk();

        $this->assertSame(1, $business->customers()->count());
        $this->assertSame(2, Transaction::count());
    }

    public function test_checkout_releases_stock_when_the_gateway_fails(): void
    {
        $this->app->instance(PaymentGateway::class, new FailingPaymentGateway());

        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Gado-Gado', 'price' => 13000, 'stock' => 5, 'unit' => 'porsi']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Dinda',
            'customer_phone' => '081234567893',
            'payment_method' => Payment::METHOD_QRIS,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(503);

        $transaction = Transaction::first();
        $this->assertSame(Transaction::STATUS_EXPIRED, $transaction->status);
        $this->assertSame(Payment::STATUS_FAILED, $transaction->payment->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_checkout_is_rate_limited_per_business_to_prevent_stock_lockup_spam(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Kerupuk', 'price' => 2000, 'stock' => 100, 'unit' => 'pcs']);

        $payload = fn () => [
            'customer_name' => 'Penyerang',
            'customer_phone' => '08'.random_int(100000000, 999999999),
            'payment_method' => Payment::METHOD_QRIS,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        // The per-business limit (3/minute) is stricter than the per-IP one
        // (5/minute), so it should be the one that trips first here.
        $this->postJson(route('storefront.checkout', $business->slug), $payload())->assertOk();
        $this->postJson(route('storefront.checkout', $business->slug), $payload())->assertOk();
        $this->postJson(route('storefront.checkout', $business->slug), $payload())->assertOk();

        $response = $this->postJson(route('storefront.checkout', $business->slug), $payload());

        $response->assertStatus(429);
        $this->assertSame(3, Transaction::count());
        $this->assertSame(97, $product->fresh()->stock);
    }

    public function test_cash_checkout_creates_a_pending_order_without_calling_the_gateway(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Bakso', 'price' => 12000, 'stock' => 10, 'unit' => 'porsi']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Eka',
            'customer_phone' => '081234567894',
            'payment_method' => Payment::METHOD_CASH,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertOk()->assertJsonMissingPath('snap_token');
        $response->assertJsonStructure(['transaction_id', 'order_status_url']);

        $transaction = Transaction::first();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(Payment::METHOD_CASH, $transaction->payment->method);
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_store_qris_checkout_is_rejected_when_the_business_has_no_qris_image(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Sate', 'price' => 15000, 'stock' => 10, 'unit' => 'porsi']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Fajar',
            'customer_phone' => '081234567895',
            'payment_method' => Payment::METHOD_QRIS_STORE,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('payment_method');
        $this->assertSame(0, Transaction::count());
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_store_qris_checkout_succeeds_once_the_business_has_uploaded_a_qris_image(): void
    {
        $business = $this->makeBusiness();
        $business->qris_image_path = 'qris/fake.png';
        $business->save();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Mie Ayam', 'price' => 14000, 'stock' => 10, 'unit' => 'porsi']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Gita',
            'customer_phone' => '081234567896',
            'payment_method' => Payment::METHOD_QRIS_STORE,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertOk()->assertJsonMissingPath('snap_token');

        $transaction = Transaction::first();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(Payment::METHOD_QRIS_STORE, $transaction->payment->method);
    }

    public function test_transfer_checkout_is_rejected_when_the_business_has_no_bank_account(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Soto', 'price' => 16000, 'stock' => 10, 'unit' => 'porsi']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Hana',
            'customer_phone' => '081234567897',
            'payment_method' => Payment::METHOD_TRANSFER,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('payment_method');
        $this->assertSame(0, Transaction::count());
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_transfer_checkout_succeeds_once_the_business_has_a_bank_account(): void
    {
        $business = $this->makeBusiness();
        $business->bankAccounts()->create(['bank_name' => 'BCA', 'account_number' => '123', 'account_holder_name' => 'Bu Sari']);
        $product = Product::create(['business_id' => $business->id, 'name' => 'Rawon', 'price' => 17000, 'stock' => 10, 'unit' => 'porsi']);

        $response = $this->postJson(route('storefront.checkout', $business->slug), [
            'customer_name' => 'Indra',
            'customer_phone' => '081234567898',
            'payment_method' => Payment::METHOD_TRANSFER,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertOk()->assertJsonMissingPath('snap_token');

        $transaction = Transaction::first();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(Payment::METHOD_TRANSFER, $transaction->payment->method);
    }
}
