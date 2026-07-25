<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Services\BusinessScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_is_zero_with_no_transaction_history(): void
    {
        $business = $this->makeBusiness();

        $result = app(BusinessScoreService::class)->calculate($business);

        $this->assertSame(0, $result['score']);
    }

    public function test_score_rewards_revenue_growth_and_consistent_daily_sales(): void
    {
        $business = $this->makeBusiness();

        // Previous 30-day window: modest, sparse revenue.
        $this->insertTransaction($business, now()->subDays(45), 20_000);

        // Last 30-day window: higher revenue, active most days.
        foreach (range(0, 27) as $daysAgo) {
            $this->insertTransaction($business, now()->subDays($daysAgo), 100_000);
        }

        $result = app(BusinessScoreService::class)->calculate($business);

        $this->assertGreaterThan(70, $result['score']);
        $this->assertGreaterThan(0, $result['revenue_growth']);
    }

    public function test_recommendation_prompts_to_start_selling_when_there_is_no_history(): void
    {
        $business = $this->makeBusiness();
        $result = app(BusinessScoreService::class)->calculate($business);

        $recommendation = app(BusinessScoreService::class)->recommendation($result);

        $this->assertStringContainsString('Belum ada transaksi', $recommendation);
    }

    public function test_recommendation_flags_revenue_decline_as_the_weakest_component(): void
    {
        $business = $this->makeBusiness();

        // Previous 30 days: full consistency, decent revenue.
        foreach (range(30, 59) as $daysAgo) {
            $this->insertTransaction($business, now()->subDays($daysAgo), 100_000);
        }

        // Last 30 days: same consistency and transaction count, but revenue collapsed.
        foreach (range(0, 29) as $daysAgo) {
            $this->insertTransaction($business, now()->subDays($daysAgo), 10_000);
        }

        $result = app(BusinessScoreService::class)->calculate($business);
        $recommendation = app(BusinessScoreService::class)->recommendation($result);

        $this->assertStringContainsString('turun', $recommendation);
        $this->assertStringContainsString('evaluasi harga', $recommendation);
    }

    public function test_recommendation_flags_low_consistency_as_the_weakest_component(): void
    {
        $business = $this->makeBusiness();

        $this->insertTransaction($business, now()->subDays(45), 20_000);

        // Only one active day in the last 30, but with a lot of revenue that day.
        for ($i = 0; $i < 20; $i++) {
            $this->insertTransaction($business, now(), 50_000);
        }

        $result = app(BusinessScoreService::class)->calculate($business);
        $recommendation = app(BusinessScoreService::class)->recommendation($result);

        $this->assertStringContainsString('dari 30 hari terakhir', $recommendation);
        $this->assertStringContainsString('buka setiap hari', $recommendation);
    }

    public function test_recommendation_flags_low_volume_as_the_weakest_component(): void
    {
        $business = $this->makeBusiness();

        foreach (range(30, 59) as $daysAgo) {
            $this->insertTransaction($business, now()->subDays($daysAgo), 10_000);
        }

        // Full consistency (one sale every day), but low transaction volume overall.
        foreach (range(0, 29) as $daysAgo) {
            $this->insertTransaction($business, now()->subDays($daysAgo), 20_000);
        }

        $result = app(BusinessScoreService::class)->calculate($business);
        $recommendation = app(BusinessScoreService::class)->recommendation($result);

        $this->assertStringContainsString('transaksi per hari', $recommendation);
        $this->assertStringContainsString('Etalase Online', $recommendation);
    }

    private function makeBusiness(): Business
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Skor', 'slug' => 'toko-skor-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return $business;
    }

    private function insertTransaction(Business $business, $date, int $total): void
    {
        DB::table('transactions')->insert([
            'business_id' => $business->id,
            'user_id' => $business->owner_id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'status' => 'completed',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
