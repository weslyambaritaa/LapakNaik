<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpireStalePendingOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusiness(): Business
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Sweep', 'slug' => 'toko-sweep-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return $business;
    }

    private function makeOrder(Business $business, Product $product, string $status, string $channel, $createdAt): Transaction
    {
        $transaction = Transaction::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-SWEEP-'.uniqid(),
            'subtotal' => $product->price * 2,
            'discount' => 0,
            'total' => $product->price * 2,
            'status' => $status,
            'channel' => $channel,
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $transaction->payment()->create([
            'method' => Payment::METHOD_QRIS,
            'amount' => $transaction->total,
            'status' => Payment::STATUS_PENDING,
        ]);

        DB::table('transactions')->where('id', $transaction->id)->update(['created_at' => $createdAt]);

        return $transaction->fresh();
    }

    public function test_it_expires_only_stale_pending_online_orders(): void
    {
        $business = $this->makeBusiness();
        $product = Product::create(['business_id' => $business->id, 'name' => 'Soto', 'price' => 15000, 'stock' => 50, 'unit' => 'porsi']);
        $product->decrement('stock', 6); // 3 orders below each reserve 2

        $stale = $this->makeOrder($business, $product, Transaction::STATUS_PENDING, Transaction::CHANNEL_ONLINE, now()->subMinutes(90));
        $fresh = $this->makeOrder($business, $product, Transaction::STATUS_PENDING, Transaction::CHANNEL_ONLINE, now()->subMinutes(5));
        $stalePos = $this->makeOrder($business, $product, Transaction::STATUS_PENDING, Transaction::CHANNEL_POS, now()->subMinutes(90));

        $stockBefore = $product->fresh()->stock;

        $this->artisan('app:expire-stale-pending-orders')->assertExitCode(0);

        $this->assertSame(Transaction::STATUS_EXPIRED, $stale->fresh()->status);
        $this->assertSame(Transaction::STATUS_PENDING, $fresh->fresh()->status);
        $this->assertSame(Transaction::STATUS_PENDING, $stalePos->fresh()->status);
        $this->assertSame($stockBefore + 2, $product->fresh()->stock);
    }
}
