<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatRupiah, formatDateTime } from '@/formatters';
import { transactionStatusMeta, channelLabels } from '@/transactionStatus';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    transactions: Object,
});
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Riwayat Transaksi
                </h2>
                <Link :href="route('pos.index')" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                    &larr; Kembali ke Kasir
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Invoice</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Waktu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Pelanggan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kasir</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kanal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="transaction in transactions.data" :key="transaction.id" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4 text-sm">
                                    <Link :href="route('pos.receipt', transaction.id)" class="font-mono text-indigo-600 hover:underline dark:text-indigo-400">
                                        {{ transaction.invoice_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(transaction.created_at) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ transaction.customer?.name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ transaction.cashier?.name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ channelLabels[transaction.channel] ?? transaction.channel }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block rounded-full px-2 py-1 text-xs font-medium" :class="transactionStatusMeta(transaction.status).class">
                                        {{ transactionStatusMeta(transaction.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium tabular-nums text-gray-900 dark:text-gray-100">{{ formatRupiah(transaction.total) }}</td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada transaksi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="transactions.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
