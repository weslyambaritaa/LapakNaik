<?php

namespace Tests\Feature\Pos;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeCashier(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Uji', 'slug' => 'toko-uji-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return [$owner, $business];
    }

    public function test_checkout_creates_transaction_and_deducts_stock(): void
    {
        [$owner, $business] = $this->makeCashier();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Es Teh',
            'price' => 5000,
            'stock' => 10,
            'unit' => 'gelas',
        ]);

        $response = $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $transaction = $business->transactions()->first();

        $response->assertRedirect(route('pos.receipt', $transaction));
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame(15000, $transaction->total);
        $this->assertSame('cash', $transaction->payment->method);
        $this->assertSame(-3, $transaction->items->first()->product->stockMovements->first()->quantity);
    }

    public function test_checkout_rejects_when_stock_is_insufficient(): void
    {
        [$owner, $business] = $this->makeCashier();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Kopi',
            'price' => 6000,
            'stock' => 2,
            'unit' => 'gelas',
        ]);

        $response = $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame(0, $business->transactions()->count());
    }

    public function test_checkout_rejects_products_from_another_business(): void
    {
        [$owner] = $this->makeCashier();
        [, $otherBusiness] = $this->makeCashier();

        $foreignProduct = Product::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Produk Toko Lain',
            'price' => 10000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('items.0.product_id');
    }

    public function test_checkout_attaches_the_customer_matching_the_phone_and_awards_loyalty_points(): void
    {
        [$owner, $business] = $this->makeCashier();

        $existing = Customer::create(['business_id' => $business->id, 'name' => 'Budi', 'phone' => '081234567890']);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Nasi Goreng',
            'price' => 18000,
            'stock' => 10,
            'unit' => 'porsi',
        ]);

        $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'customer_phone' => '081234567890',
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $transaction = $business->transactions()->first();

        $this->assertSame($existing->id, $transaction->customer_id);
        $this->assertSame(3, $existing->fresh()->loyalty_points); // 36.000 / 10.000, rounded down
    }

    public function test_checkout_with_an_unregistered_phone_leaves_the_transaction_unassigned_and_does_not_create_a_customer(): void
    {
        [$owner, $business] = $this->makeCashier();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Es Teh',
            'price' => 5000,
            'stock' => 10,
            'unit' => 'gelas',
        ]);

        $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_phone' => '089999999999',
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $transaction = $business->transactions()->first();

        $this->assertNull($transaction->customer_id);
        $this->assertSame(0, $business->customers()->count());
    }

    public function test_checkout_without_a_phone_leaves_the_transaction_unassigned(): void
    {
        [$owner, $business] = $this->makeCashier();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Kerupuk',
            'price' => 2000,
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $transaction = $business->transactions()->first();

        $this->assertNull($transaction->customer_id);
        $this->assertSame(0, $business->customers()->count());
    }
}
