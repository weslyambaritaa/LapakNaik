<?php

namespace Tests\Feature\Pos;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusinessWithStaff(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Refund', 'slug' => 'toko-refund-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        $kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'business_id' => $business->id]);

        return [$owner, $kasir, $business];
    }

    private function makeCompletedSale(User $owner, Business $business): array
    {
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Nasi Goreng',
            'price' => 18000,
            'stock' => 10,
            'unit' => 'porsi',
        ]);

        $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        return [$business->transactions()->first(), $product->fresh()];
    }

    public function test_owner_can_refund_a_completed_transaction_and_stock_is_restored(): void
    {
        [$owner, , $business] = $this->makeBusinessWithStaff();
        [$transaction, $product] = $this->makeCompletedSale($owner, $business);

        $this->assertSame(7, $product->stock);

        $response = $this->actingAs($owner)->post(route('pos.refund', $transaction), [
            'reason' => 'Pelanggan salah pesan',
        ]);

        $response->assertRedirect();
        $this->assertSame(Transaction::STATUS_REFUNDED, $transaction->fresh()->status);
        $this->assertSame(Payment::STATUS_REFUNDED, $transaction->payment->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_kasir_cannot_refund_a_transaction(): void
    {
        [$owner, $kasir, $business] = $this->makeBusinessWithStaff();
        [$transaction] = $this->makeCompletedSale($owner, $business);

        $response = $this->actingAs($kasir)->post(route('pos.refund', $transaction), [
            'reason' => 'Coba-coba',
        ]);

        $response->assertForbidden();
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
    }

    public function test_a_transaction_cannot_be_refunded_twice(): void
    {
        [$owner, , $business] = $this->makeBusinessWithStaff();
        [$transaction, $product] = $this->makeCompletedSale($owner, $business);

        $this->actingAs($owner)->post(route('pos.refund', $transaction), ['reason' => 'Pertama']);
        $stockAfterFirstRefund = $product->fresh()->stock;

        $response = $this->actingAs($owner)->post(route('pos.refund', $transaction), ['reason' => 'Kedua']);

        $response->assertSessionHas('error');
        $this->assertSame($stockAfterFirstRefund, $product->fresh()->stock);
    }
}
