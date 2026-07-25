<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\StockForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockForecastServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusiness(): Business
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Prediksi', 'slug' => 'toko-prediksi-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return $business;
    }

    private function sellUnits(Business $business, Product $product, int $quantity, $date): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'business_id' => $business->id,
            'user_id' => $business->owner_id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => $product->price * $quantity,
            'discount' => 0,
            'total' => $product->price * $quantity,
            'status' => 'completed',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        DB::table('transaction_items')->insert([
            'transaction_id' => $transactionId,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->price,
            'subtotal' => $product->price * $quantity,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    public function test_flags_a_fast_selling_product_with_low_stock(): void
    {
        $business = $this->makeBusiness();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Ayam Geprek',
            'price' => 15000,
            'stock' => 10,
            'unit' => 'porsi',
        ]);

        // Sells 7/day for the last 14 days -> only ~1.4 days of stock left.
        foreach (range(0, 13) as $daysAgo) {
            $this->sellUnits($business, $product, 7, now()->subDays($daysAgo));
        }

        $forecast = app(StockForecastService::class)->upcomingStockouts($business);

        $this->assertCount(1, $forecast);
        $this->assertSame('Ayam Geprek', $forecast->first()['name']);
        $this->assertLessThanOrEqual(2, $forecast->first()['days_remaining']);
    }

    public function test_ignores_a_slow_selling_product_with_plenty_of_stock(): void
    {
        $business = $this->makeBusiness();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Kerupuk',
            'price' => 2000,
            'stock' => 500,
            'unit' => 'pcs',
        ]);

        // Sells only 1/day -> hundreds of days of stock left, shouldn't be flagged.
        foreach (range(0, 13) as $daysAgo) {
            $this->sellUnits($business, $product, 1, now()->subDays($daysAgo));
        }

        $forecast = app(StockForecastService::class)->upcomingStockouts($business);

        $this->assertCount(0, $forecast);
    }

    public function test_ignores_a_product_with_no_recent_sales(): void
    {
        $business = $this->makeBusiness();

        Product::create([
            'business_id' => $business->id,
            'name' => 'Produk Baru',
            'price' => 5000,
            'stock' => 1,
            'unit' => 'pcs',
        ]);

        $forecast = app(StockForecastService::class)->upcomingStockouts($business);

        $this->assertCount(0, $forecast);
    }
}
