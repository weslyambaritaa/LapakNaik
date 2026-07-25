<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class StockForecastService
{
    /**
     * How far back to look when estimating daily sales velocity. Long enough
     * to smooth out a single unusually busy/quiet day, short enough to react
     * to a real shift in demand within a couple of weeks.
     */
    private const WINDOW_DAYS = 14;

    /**
     * Products likely to run out within a week, based on their own recent
     * sales pace — not just "stock <= 5" like the dashboard's low-stock list,
     * which doesn't distinguish a slow-moving item with 5 left from a
     * fast-seller about to run out today.
     */
    public function upcomingStockouts(Business $business): Collection
    {
        $windowStart = now()->subDays(self::WINDOW_DAYS);

        return $business->products()
            ->where('is_active', true)
            ->withSum(['transactionItems as sold_quantity' => function ($query) use ($windowStart) {
                $query->whereHas('transaction', fn ($q) => $q->where('created_at', '>=', $windowStart)
                    ->where('status', Transaction::STATUS_COMPLETED));
            }], 'quantity')
            ->get(['id', 'name', 'stock', 'unit'])
            ->filter(fn ($product) => (int) $product->sold_quantity > 0)
            ->map(function ($product) {
                $avgDailySales = $product->sold_quantity / self::WINDOW_DAYS;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'stock' => $product->stock,
                    'avg_daily_sales' => round($avgDailySales, 1),
                    'days_remaining' => (int) floor($product->stock / $avgDailySales),
                ];
            })
            ->filter(fn ($forecast) => $forecast['days_remaining'] <= 7)
            ->sortBy('days_remaining')
            ->values();
    }
}
