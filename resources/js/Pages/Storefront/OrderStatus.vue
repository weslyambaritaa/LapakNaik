<script setup>
import { computed } from 'vue';
import { formatRupiah, formatDateTime } from '@/formatters';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    business: Object,
    bankAccounts: Array,
    transaction: Object,
});

const statusMeta = computed(() => ({
    pending: { label: 'Menunggu Pembayaran', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' },
    completed: { label: 'Pembayaran Berhasil', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' },
    expired: { label: 'Kedaluwarsa / Dibatalkan', class: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' },
}[props.transaction.status] ?? { label: props.transaction.status, class: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }));

const paymentMethod = computed(() => props.transaction.payment?.method);
</script>

<template>
    <Head title="Status Pesanan" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <div class="text-center">
                <span class="inline-block rounded-full px-3 py-1 text-sm font-medium" :class="statusMeta.class">
                    {{ statusMeta.label }}
                </span>
                <h1 class="mt-3 font-mono text-lg font-semibold text-gray-900 dark:text-gray-100">{{ transaction.invoice_number }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(transaction.created_at) }} &middot; {{ business.name }}</p>
            </div>

            <ul class="mt-6 space-y-2 border-t border-gray-200 pt-4 text-sm dark:border-gray-700">
                <li v-for="item in transaction.items" :key="item.id" class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">{{ item.quantity }}x {{ item.product.name }}</span>
                    <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ formatRupiah(item.subtotal) }}</span>
                </li>
            </ul>

            <div class="mt-4 flex justify-between border-t border-gray-200 pt-4 text-base font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100">
                <span>Total</span>
                <span class="tabular-nums">{{ formatRupiah(transaction.total) }}</span>
            </div>

            <template v-if="transaction.status === 'pending'">
                <div v-if="paymentMethod === 'qris_store' && business.qris_image_url" class="mt-4 rounded-md border border-gray-200 p-4 text-center dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Scan QRIS berikut untuk membayar</p>
                    <img :src="business.qris_image_url" alt="QRIS toko" class="mx-auto mt-3 h-48 w-48 object-contain" />
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Setelah membayar, tunjukkan halaman ini ke kasir/penjual untuk dikonfirmasi.
                    </p>
                </div>

                <div v-else-if="paymentMethod === 'transfer' && bankAccounts.length" class="mt-4 rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-center text-sm font-medium text-gray-900 dark:text-gray-100">
                        Transfer <span class="tabular-nums">{{ formatRupiah(transaction.total) }}</span> ke salah satu rekening berikut
                    </p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="account in bankAccounts" :key="account.id" class="rounded-md bg-gray-50 p-2 text-center dark:bg-gray-900">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ account.bank_name }}</span>
                            <span class="block tabular-nums text-gray-700 dark:text-gray-300">{{ account.account_number }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">a.n. {{ account.account_holder_name }}</span>
                        </li>
                    </ul>
                    <p class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
                        Setelah transfer, tunjukkan halaman ini ke kasir/penjual untuk dikonfirmasi.
                    </p>
                </div>

                <div v-else-if="paymentMethod === 'cash'" class="mt-4 rounded-md border border-gray-200 p-4 text-center dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Bayar tunai saat ambil pesanan</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Tunjukkan halaman ini ke kasir dan bayar tunai <strong>{{ formatRupiah(transaction.total) }}</strong> untuk mengambil pesananmu.
                    </p>
                </div>

                <p v-else class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400">
                    Belum menyelesaikan pembayaran? Muat ulang halaman ini setelah membayar untuk melihat status terbaru.
                </p>
            </template>

            <Link :href="route('storefront.show', business.slug)" class="mt-6 block text-center text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                &larr; Kembali ke Etalase
            </Link>
        </div>
    </div>
</template>
