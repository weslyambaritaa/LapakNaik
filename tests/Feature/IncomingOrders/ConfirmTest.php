<?php

namespace Tests\Feature\IncomingOrders;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmTest extends TestCase
{
    use RefreshDatabase;

    private function makeCashier(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Uji', 'slug' => 'toko-uji-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return [$owner, $business];
    }

    private function makePendingOnlineOrder(Business $business, string $method, int $total = 20000, ?Customer $customer = null, ?string $invoiceNumber = null): Transaction
    {
        $product = Product::create(['business_id' => $business->id, 'name' => 'Produk', 'price' => $total, 'stock' => 10, 'unit' => 'pcs']);
        $product->decrement('stock', 1);

        $transaction = Transaction::create([
            'business_id' => $business->id,
            'customer_id' => $customer?->id,
            'invoice_number' => $invoiceNumber ?? 'INV-TEST-'.uniqid(),
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'status' => Transaction::STATUS_PENDING,
            'channel' => Transaction::CHANNEL_ONLINE,
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $total,
            'subtotal' => $total,
        ]);

        $transaction->payment()->create([
            'method' => $method,
            'amount' => $total,
            'status' => Payment::STATUS_PENDING,
            'gateway_reference' => $transaction->invoice_number,
        ]);

        return $transaction;
    }

    public function test_staff_can_confirm_a_store_qris_order(): void
    {
        [$owner, $business] = $this->makeCashier();
        $transaction = $this->makePendingOnlineOrder($business, Payment::METHOD_QRIS_STORE);

        $response = $this->actingAs($owner)->post(route('incoming-orders.confirm', $transaction));

        $response->assertRedirect();
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
        $this->assertSame(Payment::STATUS_PAID, $transaction->payment->fresh()->status);
    }

    public function test_staff_can_confirm_a_transfer_order(): void
    {
        [$owner, $business] = $this->makeCashier();
        $transaction = $this->makePendingOnlineOrder($business, Payment::METHOD_TRANSFER);

        $response = $this->actingAs($owner)->post(route('incoming-orders.confirm', $transaction));

        $response->assertRedirect();
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
        $this->assertSame(Payment::STATUS_PAID, $transaction->payment->fresh()->status);
    }

    public function test_staff_can_confirm_a_cash_order_with_sufficient_amount(): void
    {
        [$owner, $business] = $this->makeCashier();
        $transaction = $this->makePendingOnlineOrder($business, Payment::METHOD_CASH, 20000);

        $response = $this->actingAs($owner)->post(route('incoming-orders.confirm', $transaction), [
            'amount_received' => 50000,
        ]);

        $response->assertRedirect();
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
    }

    public function test_cash_confirmation_is_rejected_when_amount_received_is_insufficient(): void
    {
        [$owner, $business] = $this->makeCashier();
        $transaction = $this->makePendingOnlineOrder($business, Payment::METHOD_CASH, 20000);

        $response = $this->actingAs($owner)->post(route('incoming-orders.confirm', $transaction), [
            'amount_received' => 10000,
        ]);

        $response->assertSessionHasErrors('amount_received');
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }

    public function test_midtrans_orders_cannot_be_confirmed_manually(): void
    {
        [$owner, $business] = $this->makeCashier();
        $transaction = $this->makePendingOnlineOrder($business, Payment::METHOD_QRIS);

        $response = $this->actingAs($owner)->post(route('incoming-orders.confirm', $transaction));

        $response->assertRedirect();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }

    public function test_confirming_a_cash_order_awards_loyalty_points_to_the_customer(): void
    {
        [$owner, $business] = $this->makeCashier();
        $customer = Customer::create(['business_id' => $business->id, 'name' => 'Gita', 'phone' => '081234500099']);
        $transaction = $this->makePendingOnlineOrder($business, Payment::METHOD_CASH, 20000, $customer);

        $this->actingAs($owner)->post(route('incoming-orders.confirm', $transaction), [
            'amount_received' => 20000,
        ]);

        // total 20.000 / 10.000 = 2
        $this->assertSame(2, $customer->fresh()->loyalty_points);
    }

    public function test_staff_cannot_confirm_another_businesss_order(): void
    {
        [$owner] = $this->makeCashier();
        [, $otherBusiness] = $this->makeCashier();
        $transaction = $this->makePendingOnlineOrder($otherBusiness, Payment::METHOD_QRIS_STORE);

        $this->actingAs($owner)->post(route('incoming-orders.confirm', $transaction))->assertForbidden();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }

    public function test_index_only_lists_pending_online_orders_for_the_current_business(): void
    {
        [$owner, $business] = $this->makeCashier();
        $mine = $this->makePendingOnlineOrder($business, Payment::METHOD_CASH);
        [, $otherBusiness] = $this->makeCashier();
        $this->makePendingOnlineOrder($otherBusiness, Payment::METHOD_CASH);

        $response = $this->actingAs($owner)->get(route('incoming-orders.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('transactions', 1)
            ->where('transactions.0.id', $mine->id)
        );
    }

    public function test_search_filters_by_invoice_number(): void
    {
        [$owner, $business] = $this->makeCashier();
        $match = $this->makePendingOnlineOrder($business, Payment::METHOD_CASH, invoiceNumber: 'INV-20260724-0099');
        $this->makePendingOnlineOrder($business, Payment::METHOD_CASH, invoiceNumber: 'INV-20260724-0001');

        $response = $this->actingAs($owner)->get(route('incoming-orders.index', ['search' => '0099']));

        $response->assertInertia(fn ($page) => $page
            ->has('transactions', 1)
            ->where('transactions.0.id', $match->id)
        );
    }

    public function test_search_filters_by_customer_name_or_phone(): void
    {
        [$owner, $business] = $this->makeCashier();
        $dewi = Customer::create(['business_id' => $business->id, 'name' => 'Dewi', 'phone' => '081234500001']);
        $eko = Customer::create(['business_id' => $business->id, 'name' => 'Eko', 'phone' => '081234500002']);
        $matchByName = $this->makePendingOnlineOrder($business, Payment::METHOD_CASH, customer: $dewi);
        $this->makePendingOnlineOrder($business, Payment::METHOD_CASH, customer: $eko);

        $response = $this->actingAs($owner)->get(route('incoming-orders.index', ['search' => 'Dewi']));

        $response->assertInertia(fn ($page) => $page
            ->has('transactions', 1)
            ->where('transactions.0.id', $matchByName->id)
        );

        $responseByPhone = $this->actingAs($owner)->get(route('incoming-orders.index', ['search' => '500002']));

        $responseByPhone->assertInertia(fn ($page) => $page
            ->has('transactions', 1)
            ->where('transactions.0.customer.name', 'Eko')
        );
    }

    public function test_search_with_no_match_returns_an_empty_list(): void
    {
        [$owner, $business] = $this->makeCashier();
        $this->makePendingOnlineOrder($business, Payment::METHOD_CASH);

        $response = $this->actingAs($owner)->get(route('incoming-orders.index', ['search' => 'tidak-ada-yang-cocok']));

        $response->assertInertia(fn ($page) => $page->has('transactions', 0));
    }
}
