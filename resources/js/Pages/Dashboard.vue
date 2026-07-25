<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RevenueChart from '@/Components/RevenueChart.vue';
import ScoreGauge from '@/Components/ScoreGauge.vue';
import { formatRupiah, formatDateTime } from '@/formatters';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    chart: Array,
    topProducts: Array,
    lowStockProducts: Array,
    recentTransactions: Array,
    businessScore: Object,
    stockForecast: Array,
    onboarding: Array,
});

const business = computed(() => usePage().props.auth.business);
const storefrontUrl = computed(() => business.value ? route('storefront.show', business.value.slug) : '#');
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Onboarding checklist -->
                <div v-if="onboarding" class="rounded-lg border border-indigo-100 bg-indigo-50 p-5 dark:border-indigo-900 dark:bg-indigo-950/40">
                    <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">Langkah Awal Memulai</h3>
                    <ul class="mt-3 space-y-2">
                        <li v-for="step in onboarding" :key="step.key" class="flex items-center gap-3 text-sm">
                            <span
                                class="flex size-5 shrink-0 items-center justify-center rounded-full"
                                :class="step.done ? 'bg-emerald-500 text-white' : 'border-2 border-indigo-300 dark:border-indigo-700'"
                            >
                                <svg v-if="step.done" class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <Link
                                v-if="!step.done"
                                :href="route(step.route)"
                                class="text-indigo-700 hover:underline dark:text-indigo-300"
                            >
                                {{ step.label }}
                            </Link>
                            <span v-else class="text-gray-500 line-through dark:text-gray-400">{{ step.label }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Stat cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Omzet Hari Ini</div>
                        <div class="mt-2 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ formatRupiah(stats.today_revenue) }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Omzet Bulan Ini</div>
                        <div class="mt-2 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ formatRupiah(stats.month_revenue) }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Transaksi Bulan Ini</div>
                        <div class="mt-2 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ stats.month_transaction_count }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Revenue chart -->
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800 lg:col-span-2">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Tren Omzet 14 Hari Terakhir</h3>
                        <RevenueChart :data="chart" />
                    </div>

                    <!-- Business score -->
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">Skor Kelayakan Usaha</h3>
                        <p class="mb-3 text-xs text-gray-400">Bahan pendukung pengajuan modal — bukan skor kredit resmi.</p>
                        <div class="flex items-center justify-center">
                            <ScoreGauge :score="businessScore.score" />
                        </div>
                        <p class="mt-3 rounded-md bg-gray-50 p-3 text-xs leading-relaxed text-gray-600 dark:bg-gray-900/50 dark:text-gray-400">
                            {{ businessScore.recommendation }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Low stock -->
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Stok Menipis</h3>
                        <ul v-if="lowStockProducts.length" class="space-y-2">
                            <li v-for="product in lowStockProducts" :key="product.id" class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ product.name }}</span>
                                <span class="font-medium tabular-nums text-amber-600 dark:text-amber-400">{{ product.stock }} {{ product.unit }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400">Semua stok aman.</p>
                    </div>

                    <!-- Top products -->
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Produk Terlaris (Bulan Ini)</h3>
                        <ul v-if="topProducts.length" class="space-y-2">
                            <li v-for="product in topProducts" :key="product.id" class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ product.name }}</span>
                                <span class="font-medium tabular-nums text-gray-500 dark:text-gray-400">{{ product.sold_quantity }} {{ product.unit }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400">Belum ada penjualan bulan ini.</p>
                    </div>

                    <!-- Recent transactions -->
                    <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Transaksi Terbaru</h3>
                        <ul v-if="recentTransactions.length" class="space-y-2">
                            <li v-for="transaction in recentTransactions" :key="transaction.id" class="flex justify-between text-sm">
                                <Link :href="route('pos.receipt', transaction.id)" class="font-mono text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ transaction.invoice_number }}
                                </Link>
                                <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ formatRupiah(transaction.total) }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400">Belum ada transaksi.</p>
                    </div>
                </div>

                <!-- Stock forecast -->
                <div v-if="stockForecast.length" class="rounded-lg border border-amber-100 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30">
                    <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-200">Prediksi Stok Akan Habis</h3>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                        Diperkirakan dari rata-rata penjualan 14 hari terakhir — bukan sekadar stok tersisa sedikit, tapi produk yang lajunya cepat habis.
                    </p>
                    <ul class="mt-3 space-y-2">
                        <li v-for="item in stockForecast" :key="item.id" class="flex items-center justify-between text-sm">
                            <span class="text-gray-800 dark:text-gray-200">{{ item.name }}</span>
                            <span class="text-right">
                                <span class="font-medium tabular-nums text-amber-700 dark:text-amber-400">
                                    {{ item.days_remaining <= 0 ? 'Diperkirakan sudah habis' : `~${item.days_remaining} hari lagi` }}
                                </span>
                                <span class="ml-2 text-xs tabular-nums text-gray-400">({{ item.stock }} {{ item.unit }} tersisa, laku {{ item.avg_daily_sales }}/hari)</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <div v-if="business" class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Etalase Online</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Bagikan tautan ini ke pelanggan agar mereka bisa melihat katalog produk kamu:
                    </p>
                    <a :href="storefrontUrl" target="_blank" class="mt-2 inline-block text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                        {{ storefrontUrl }}
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
