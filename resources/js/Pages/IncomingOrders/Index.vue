<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatRupiah, formatDateTime } from '@/formatters';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    transactions: Array,
    filters: Object,
});

const search = ref(props.filters.search ?? '');

watch(search, (searchValue) => {
    router.get(route('incoming-orders.index'), { search: searchValue }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const methodLabels = {
    qris: 'QRIS Midtrans (otomatis)',
    qris_store: 'QRIS Toko',
    transfer: 'Transfer Bank',
    cash: 'Tunai',
};

const cashModal = ref(null);
const amountReceived = ref(0);

const change = computed(() => {
    if (!cashModal.value) return 0;
    return Math.max(0, (amountReceived.value || 0) - cashModal.value.total);
});

const enoughCash = computed(() => cashModal.value && (amountReceived.value || 0) >= cashModal.value.total);

function openCashConfirm(transaction) {
    cashModal.value = transaction;
    amountReceived.value = transaction.total;
}

function confirmCash() {
    router.post(route('incoming-orders.confirm', cashModal.value.id), {
        amount_received: amountReceived.value,
    }, {
        onSuccess: () => (cashModal.value = null),
    });
}

const simpleConfirmModal = ref(null);

function openSimpleConfirm(transaction) {
    simpleConfirmModal.value = transaction;
}

function confirmSimple() {
    router.post(route('incoming-orders.confirm', simpleConfirmModal.value.id), {}, {
        onSuccess: () => (simpleConfirmModal.value = null),
    });
}
</script>

<template>
    <Head title="Pesanan Masuk" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Pesanan Masuk
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Pesanan online yang menunggu pembayaran QRIS Toko, Transfer Bank, atau Tunai — konfirmasi manual
                    setelah kamu benar-benar menerima uangnya. Pesanan QRIS Midtrans tidak muncul di sini karena
                    otomatis terkonfirmasi lewat webhook.
                </p>

                <TextInput v-model="search" type="search" placeholder="Cari no. invoice, nama, atau no. HP..." class="mb-4 w-full max-w-sm" />

                <div v-if="transactions.length === 0" class="rounded-lg bg-white p-8 text-center text-sm text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-400">
                    {{ search ? 'Tidak ada pesanan yang cocok dengan pencarian.' : 'Tidak ada pesanan yang menunggu konfirmasi.' }}
                </div>

                <div v-for="transaction in transactions" :key="transaction.id" class="mb-4 rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">{{ transaction.invoice_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDateTime(transaction.created_at) }}
                                <span v-if="transaction.customer"> &middot; {{ transaction.customer.name }} ({{ transaction.customer.phone }})</span>
                            </p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                            {{ methodLabels[transaction.payment?.method] ?? transaction.payment?.method }}
                        </span>
                    </div>

                    <ul class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
                        <li v-for="item in transaction.items" :key="item.id" class="flex justify-between text-gray-600 dark:text-gray-300">
                            <span>{{ item.quantity }}x {{ item.product.name }}</span>
                            <span class="tabular-nums">{{ formatRupiah(item.subtotal) }}</span>
                        </li>
                    </ul>

                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">Total {{ formatRupiah(transaction.total) }}</span>

                        <PrimaryButton
                            v-if="transaction.payment?.method === 'cash'"
                            @click="openCashConfirm(transaction)"
                        >
                            Konfirmasi Tunai
                        </PrimaryButton>
                        <PrimaryButton
                            v-else-if="transaction.payment?.method === 'qris_store' || transaction.payment?.method === 'transfer'"
                            @click="openSimpleConfirm(transaction)"
                        >
                            Tandai Lunas
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="cashModal !== null" @close="cashModal = null">
            <div v-if="cashModal" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Konfirmasi Pembayaran Tunai</h2>
                <p class="mt-1 font-mono text-sm text-gray-500 dark:text-gray-400">{{ cashModal.invoice_number }}</p>

                <div class="mt-4 flex justify-between text-base font-semibold text-gray-900 dark:text-gray-100">
                    <span>Total Tagihan</span>
                    <span class="tabular-nums">{{ formatRupiah(cashModal.total) }}</span>
                </div>

                <div class="mt-4">
                    <InputLabel for="amount_received" value="Uang Diterima dari Pembeli" />
                    <TextInput
                        id="amount_received"
                        v-model.number="amountReceived"
                        type="number"
                        min="0"
                        class="mt-1 block w-full"
                    />
                </div>

                <div class="mt-4 flex justify-between text-base font-semibold" :class="enoughCash ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                    <span>Kembalian</span>
                    <span class="tabular-nums">{{ formatRupiah(change) }}</span>
                </div>
                <p v-if="!enoughCash" class="mt-1 text-xs text-red-500">Uang diterima kurang dari total tagihan.</p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="cashModal = null">Batal</SecondaryButton>
                    <PrimaryButton :disabled="!enoughCash" @click="confirmCash">Konfirmasi Lunas</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="simpleConfirmModal !== null" @close="simpleConfirmModal = null">
            <div v-if="simpleConfirmModal" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Konfirmasi Pembayaran</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Konfirmasi sudah menerima pembayaran untuk pesanan
                    <span class="font-mono font-semibold">{{ simpleConfirmModal.invoice_number }}</span>
                    sebesar <span class="font-semibold">{{ formatRupiah(simpleConfirmModal.total) }}</span>?
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="simpleConfirmModal = null">Batal</SecondaryButton>
                    <PrimaryButton @click="confirmSimple">Konfirmasi Lunas</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
