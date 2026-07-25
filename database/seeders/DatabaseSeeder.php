<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\CashFlow;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds one demo UMKM ("Warung Bu Sari") with ~60 days of POS history,
     * so the dashboard, low-stock alerts, and business score have real data
     * to show immediately after a fresh install.
     */
    public function run(): void
    {
        $owner = User::create([
            'name' => 'Bu Sari',
            'email' => 'owner@lapaknaik.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_OWNER,
        ]);

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Warung Bu Sari',
            'slug' => 'warung-bu-sari',
            'category' => 'Kuliner',
            'description' => 'Warung makan rumahan dengan menu masakan Indonesia sehari-hari.',
            'address' => 'Jl. Merdeka No. 12, Samarinda',
            'phone' => '0812-3456-7890',
        ]);

        $owner->update(['business_id' => $business->id]);

        $kasir = User::create([
            'name' => 'Dewi (Kasir)',
            'email' => 'kasir@lapaknaik.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_KASIR,
            'business_id' => $business->id,
        ]);

        $categories = collect(['Makanan', 'Minuman', 'Snack'])
            ->mapWithKeys(fn ($name) => [$name => Category::create(['business_id' => $business->id, 'name' => $name])]);

        $supplier = Supplier::create([
            'business_id' => $business->id,
            'name' => 'CV Sumber Pangan',
            'phone' => '0811-2233-4455',
            'address' => 'Pasar Segiri, Samarinda',
        ]);

        $productDefinitions = [
            ['Makanan', 'Nasi Goreng Spesial', 18000, 11000, 'porsi', 80],
            ['Makanan', 'Ayam Geprek', 16000, 9500, 'porsi', 80],
            ['Makanan', 'Soto Ayam', 15000, 9000, 'porsi', 60],
            ['Makanan', 'Mie Ayam Bakso', 14000, 8500, 'porsi', 60],
            ['Makanan', 'Gado-Gado', 13000, 7500, 'porsi', 40],
            ['Minuman', 'Es Teh Manis', 5000, 2000, 'gelas', 150],
            ['Minuman', 'Es Jeruk', 6000, 2500, 'gelas', 120],
            ['Minuman', 'Kopi Hitam', 6000, 2500, 'gelas', 100],
            ['Snack', 'Kerupuk', 2000, 800, 'pcs', 200],
            ['Snack', 'Tahu Isi', 3000, 1200, 'pcs', 150],
        ];

        $products = collect($productDefinitions)->map(function ($definition) use ($business, $categories) {
            [$categoryName, $name, $price, $costPrice, $unit, $stock] = $definition;

            return Product::create([
                'business_id' => $business->id,
                'category_id' => $categories[$categoryName]->id,
                'name' => $name,
                'price' => $price,
                'cost_price' => $costPrice,
                'stock' => $stock,
                'unit' => $unit,
                'is_active' => true,
            ]);
        });

        $customers = collect(['Andi', 'Budi', 'Citra', 'Dinda', 'Eko'])->map(
            fn ($name) => Customer::create([
                'business_id' => $business->id,
                'name' => $name,
                'phone' => '08' . fake()->numerify('##########'),
            ])
        );

        $this->seedTransactionHistory($business, $products, $customers, $kasir);
        $this->seedCashFlows($business);
    }

    private function seedTransactionHistory($business, $products, $customers, User $kasir): void
    {
        $remainingStock = $products->pluck('stock', 'id')->all();
        $paymentMethods = ['cash', 'cash', 'cash', 'qris', 'transfer'];

        for ($daysAgo = 59; $daysAgo >= 0; $daysAgo--) {
            $date = now()->subDays($daysAgo);
            $transactionsToday = fake()->numberBetween(2, 7);

            for ($sequence = 1; $sequence <= $transactionsToday; $sequence++) {
                $itemCount = fake()->numberBetween(1, 3);
                $pickedProducts = $products->shuffle()->take($itemCount);

                $lines = [];
                $subtotal = 0;

                foreach ($pickedProducts as $product) {
                    $quantity = fake()->numberBetween(1, 3);

                    if ($remainingStock[$product->id] < $quantity) {
                        continue;
                    }

                    $remainingStock[$product->id] -= $quantity;
                    $lineSubtotal = $product->price * $quantity;
                    $subtotal += $lineSubtotal;

                    $lines[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                if (empty($lines)) {
                    continue;
                }

                $discount = fake()->boolean(15) ? (int) round($subtotal * 0.1) : 0;
                $total = $subtotal - $discount;
                $customer = fake()->boolean(50) ? $customers->random() : null;
                $timestamp = $date->copy()->setTime(fake()->numberBetween(8, 20), fake()->numberBetween(0, 59));

                $transactionId = DB::table('transactions')->insertGetId([
                    'business_id' => $business->id,
                    'customer_id' => $customer?->id,
                    'user_id' => $kasir->id,
                    'invoice_number' => sprintf('INV-%s-%04d', $date->format('Ymd'), $sequence),
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => 'completed',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                foreach ($lines as $line) {
                    DB::table('transaction_items')->insert([
                        'transaction_id' => $transactionId,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                    DB::table('stock_movements')->insert([
                        'product_id' => $line['product_id'],
                        'user_id' => $kasir->id,
                        'type' => 'out',
                        'quantity' => -$line['quantity'],
                        'reference' => sprintf('INV-%s-%04d', $date->format('Ymd'), $sequence),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }

                DB::table('payments')->insert([
                    'transaction_id' => $transactionId,
                    'method' => fake()->randomElement($paymentMethods),
                    'amount' => $total,
                    'status' => 'paid',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                if ($customer) {
                    $customer->increment('loyalty_points', intdiv($total, 10000));
                }
            }
        }

        foreach ($remainingStock as $productId => $stock) {
            Product::whereKey($productId)->update(['stock' => $stock]);
        }
    }

    private function seedCashFlows(Business $business): void
    {
        foreach ([1, 2] as $monthsAgo) {
            $date = now()->subMonths($monthsAgo)->startOfMonth()->addDays(4);

            CashFlow::create(['business_id' => $business->id, 'type' => 'out', 'category' => 'Sewa', 'amount' => 1_500_000, 'date' => $date]);
            CashFlow::create(['business_id' => $business->id, 'type' => 'out', 'category' => 'Listrik & Air', 'amount' => 350_000, 'date' => $date->copy()->addDays(2)]);
            CashFlow::create(['business_id' => $business->id, 'type' => 'out', 'category' => 'Gaji', 'amount' => 2_000_000, 'date' => $date->copy()->addDays(3)]);
            CashFlow::create(['business_id' => $business->id, 'type' => 'out', 'category' => 'Belanja Bahan', 'amount' => 1_800_000, 'date' => $date->copy()->addDays(5)]);
        }
    }
}
