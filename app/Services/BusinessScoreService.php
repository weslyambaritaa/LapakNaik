<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessScore;
use App\Models\Transaction;

class BusinessScoreService
{
    /**
     * Score kelayakan usaha 0-100, dihitung dari histori transaksi 60 hari terakhir.
     *
     * Tiga komponen yang dinilai:
     * - Pertumbuhan omzet (0-40): 30 hari terakhir dibanding 30 hari sebelumnya.
     * - Konsistensi (0-30): proporsi hari dengan transaksi dalam 30 hari terakhir.
     * - Volume (0-30): rata-rata transaksi per hari, dengan asumsi 5 transaksi/hari
     *   sebagai acuan usaha mikro yang sudah cukup ramai.
     *
     * Skor ini dimaksudkan sebagai bahan pendukung awal untuk pengajuan modal,
     * bukan pengganti penilaian kredit formal dari lembaga keuangan.
     */
    public function calculate(Business $business): array
    {
        $now = now();
        $periodStart = $now->copy()->subDays(30);
        $previousStart = $now->copy()->subDays(60);

        $currentQuery = $business->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$periodStart, $now]);

        $revenueCurrent = (int) (clone $currentQuery)->sum('total');
        $transactionsCount = (clone $currentQuery)->count();
        $activeDays = (clone $currentQuery)->selectRaw('COUNT(DISTINCT DATE(created_at)) as days')->value('days') ?? 0;

        $revenuePrevious = (int) $business->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$previousStart, $periodStart])
            ->sum('total');

        $growth = $revenuePrevious > 0
            ? ($revenueCurrent - $revenuePrevious) / $revenuePrevious
            : ($revenueCurrent > 0 ? 1.0 : 0.0);

        if ($transactionsCount === 0) {
            // No sales at all this period means there's nothing to score yet —
            // the growth formula's "neutral" midpoint would otherwise hand out
            // a baseline score to a business with zero activity.
            $growthScore = $consistencyScore = $volumeScore = 0.0;
        } else {
            $growthScore = $this->clamp(($growth + 0.5) * 40, 0, 40);
            $consistencyScore = $this->clamp(($activeDays / 30) * 30, 0, 30);
            $volumeScore = $this->clamp((($transactionsCount / 30) / 5) * 30, 0, 30);
        }

        $score = (int) round($growthScore + $consistencyScore + $volumeScore);
        $avgTransactionValue = $transactionsCount > 0 ? $revenueCurrent / $transactionsCount : 0;

        return [
            'score' => $score,
            'revenue_growth' => round($growth * 100, 2),
            'avg_transaction_value' => round($avgTransactionValue, 2),
            'revenue_current' => $revenueCurrent,
            'revenue_previous' => $revenuePrevious,
            'active_days' => (int) $activeDays,
            'transactions_count' => $transactionsCount,
            'breakdown' => [
                'growth_score' => round($growthScore, 1),
                'consistency_score' => round($consistencyScore, 1),
                'volume_score' => round($volumeScore, 1),
            ],
        ];
    }

    /**
     * Turn the score breakdown into one concrete, data-referencing sentence
     * about whichever component is weakest — so the dashboard reads as
     * advice ("kenapa skormu segini, dan apa yang paling ngefek") rather
     * than just a number with no next step.
     */
    public function recommendation(array $result): string
    {
        if ($result['transactions_count'] === 0) {
            return 'Belum ada transaksi tercatat dalam 30 hari terakhir. Skor akan mulai terhitung begitu ada transaksi lewat Kasir atau Etalase Online.';
        }

        $relative = [
            'growth' => $result['breakdown']['growth_score'] / 40,
            'consistency' => $result['breakdown']['consistency_score'] / 30,
            'volume' => $result['breakdown']['volume_score'] / 30,
        ];

        asort($relative);
        $weakest = array_key_first($relative);

        return match ($weakest) {
            'growth' => sprintf(
                'Omzet %s %s%% dibanding 30 hari sebelumnya. %s',
                $result['revenue_growth'] >= 0 ? 'naik' : 'turun',
                number_format(abs((float) $result['revenue_growth']), 1),
                $result['revenue_growth'] >= 0
                    ? 'Ini komponen terkuat kamu saat ini — pertahankan.'
                    : 'Coba evaluasi harga, promosi, atau produk terlaris untuk mendorong kenaikan omzet.'
            ),
            'volume' => sprintf(
                'Rata-rata %s transaksi per hari dalam 30 hari terakhir. Coba tingkatkan promosi ke pelanggan lama atau bagikan tautan Etalase Online untuk menambah volume transaksi.',
                number_format($result['transactions_count'] / 30, 1)
            ),
            default => sprintf(
                'Usaha kamu tercatat jualan %d dari 30 hari terakhir. Konsistensi harian adalah komponen yang paling bisa ditingkatkan — usahakan buka setiap hari untuk menaikkan skor.',
                $result['active_days']
            ),
        };
    }

    public function calculateAndStore(Business $business): BusinessScore
    {
        $result = $this->calculate($business);

        return BusinessScore::updateOrCreate(
            ['business_id' => $business->id, 'period' => now()->format('Y-m')],
            [
                'score' => $result['score'],
                'revenue_growth' => $result['revenue_growth'],
                'avg_transaction_value' => $result['avg_transaction_value'],
            ]
        );
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
