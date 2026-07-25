<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\BusinessScoreService;
use App\Services\StockForecastService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BusinessScoreService $scoreService, StockForecastService $forecastService): Response
    {
        $business = $request->user()->business;

        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $todayRevenue = (int) $business->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('created_at', '>=', $today)
            ->sum('total');

        $monthRevenue = (int) $business->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('created_at', '>=', $monthStart)
            ->sum('total');

        $monthTransactionCount = $business->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $revenueSeries = $business->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date);

        $chart = collect(range(13, 0))->map(function (int $daysAgo) use ($revenueSeries) {
            $date = now()->subDays($daysAgo)->format('Y-m-d');

            return [
                'date' => $date,
                'total' => (int) ($revenueSeries[$date]->total ?? 0),
            ];
        })->values();

        $topProducts = $business->products()
            ->withSum(['transactionItems as sold_quantity' => function ($query) use ($monthStart) {
                $query->whereHas('transaction', fn ($q) => $q->where('created_at', '>=', $monthStart)
                    ->where('status', Transaction::STATUS_COMPLETED));
            }], 'quantity')
            ->orderByDesc('sold_quantity')
            ->limit(5)
            ->get(['id', 'name', 'unit'])
            ->filter(fn ($product) => $product->sold_quantity > 0)
            ->values();

        $lowStockProducts = $business->products()
            ->where('is_active', true)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->limit(6)
            ->get(['id', 'name', 'stock', 'unit']);

        $recentTransactions = $business->transactions()
            ->with('cashier:id,name')
            ->latest()
            ->limit(6)
            ->get(['id', 'invoice_number', 'total', 'user_id', 'created_at']);

        $onboarding = [
            ['key' => 'category', 'label' => 'Tambah kategori produk pertama', 'route' => 'categories.index', 'done' => $business->categories()->exists()],
            ['key' => 'product', 'label' => 'Tambah produk pertama', 'route' => 'products.index', 'done' => $business->products()->exists()],
            ['key' => 'sale', 'label' => 'Buat transaksi pertama lewat Kasir', 'route' => 'pos.index', 'done' => $business->transactions()->where('status', Transaction::STATUS_COMPLETED)->exists()],
        ];

        $scoreResult = $scoreService->calculate($business);
        $scoreService->calculateAndStore($business);

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'month_transaction_count' => $monthTransactionCount,
            ],
            'chart' => $chart,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'recentTransactions' => $recentTransactions,
            'businessScore' => [
                'score' => $scoreResult['score'],
                'recommendation' => $scoreService->recommendation($scoreResult),
            ],
            'stockForecast' => $forecastService->upcomingStockouts($business),
            'onboarding' => collect($onboarding)->contains('done', false) ? $onboarding : null,
        ]);
    }
}
