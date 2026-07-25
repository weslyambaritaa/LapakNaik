<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatRupiah, formatDateTime } from '@/formatters';
import { transactionStatusMeta } from '@/transactionStatus';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    transaction: Object,
    business: Object,
});

const methodLabels = { cash: 'Tunai', qris: 'QRIS', transfer: 'Transfer' };

const statusMeta = computed(() => transactionStatusMeta(props.transaction.status));

const role = computed(() => usePage().props.auth.user?.role);
const canRefund = computed(() => (role.value === 'owner' || role.value === 'admin') && props.transaction.status === 'completed');

function printReceipt() {
    window.print();
}

const showRefundModal = ref(false);
const refundForm = useForm({ reason: '' });

function submitRefund() {
    refundForm.post(route('pos.refund', props.transaction.id), {
        onSuccess: () => (showRefundModal.value = false),
    });
}
</script>

<template>
    <Head title="Struk Transaksi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Struk Transaksi
                </h2>
                <Link :href="route('pos.index')" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                    &larr; Kembali ke Kasir
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-md px-4 sm:px-6">
                <div class="mb-4 text-center print:hidden">
                    <span class="inline-block rounded-full px-3 py-1 text-sm font-medium" :class="statusMeta.class">
                        {{ statusMeta.label }}
                    </span>
                </div>

                <div id="receipt" class="rounded-lg bg-white p-6 font-mono text-sm shadow-sm dark:bg-gray-800 dark:text-gray-200">
                    <div class="text-center">
                        <div class="text-base font-bold">{{ business.name }}</div>
                        <div v-if="business.address" class="text-xs text-gray-500 dark:text-gray-400">{{ business.address }}</div>
                        <div v-if="business.phone" class="text-xs text-gray-500 dark:text-gray-400">{{ business.phone }}</div>
                    </div>

                    <div class="my-3 border-t border-dashed border-gray-300 dark:border-gray-600"></div>

                    <div class="flex justify-between"><span>Invoice</span><span>{{ transaction.invoice_number }}</span></div>
                    <div class="flex justify-between"><span>Waktu</span><span>{{ formatDateTime(transaction.created_at) }}</span></div>
                    <div v-if="transaction.cashier" class="flex justify-between"><span>Kasir</span><span>{{ transaction.cashier.name }}</span></div>
                    <div v-if="transaction.customer" class="flex justify-between"><span>Pelanggan</span><span>{{ transaction.customer.name }}</span></div>

                    <div class="my-3 border-t border-dashed border-gray-300 dark:border-gray-600"></div>

                    <div v-for="item in transaction.items" :key="item.id" class="mb-2">
                        <div class="flex justify-between"><span>{{ item.product.name }}</span></div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ item.quantity }} x {{ formatRupiah(item.price) }}</span>
                            <span>{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                    </div>

                    <div class="my-3 border-t border-dashed border-gray-300 dark:border-gray-600"></div>

                    <div class="flex justify-between"><span>Subtotal</span><span>{{ formatRupiah(transaction.subtotal) }}</span></div>
                    <div v-if="transaction.discount > 0" class="flex justify-between"><span>Diskon</span><span>-{{ formatRupiah(transaction.discount) }}</span></div>
                    <div class="flex justify-between text-base font-bold"><span>Total</span><span>{{ formatRupiah(transaction.total) }}</span></div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400"><span>Pembayaran</span><span>{{ methodLabels[transaction.payment?.method] ?? '—' }}</span></div>

                    <div class="my-3 border-t border-dashed border-gray-300 dark:border-gray-600"></div>

                    <p class="text-center text-xs text-gray-400">Terima kasih telah berbelanja!</p>
                </div>

                <div class="mt-4 flex flex-wrap justify-center gap-3 print:hidden">
                    <Link :href="route('pos.index')">
                        <SecondaryButton>Transaksi Baru</SecondaryButton>
                    </Link>
                    <PrimaryButton @click="printReceipt">Cetak Struk</PrimaryButton>
                    <a :href="route('pos.receipt.pdf', transaction.id)" target="_blank">
                        <SecondaryButton>Unduh PDF</SecondaryButton>
                    </a>
                    <DangerButton v-if="canRefund" @click="showRefundModal = true">Refund Transaksi</DangerButton>
                </div>
            </div>
        </div>

        <Modal :show="showRefundModal" @close="showRefundModal = false">
            <form class="p-6" @submit.prevent="submitRefund">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Refund Transaksi</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Stok {{ transaction.items.length }} produk akan dikembalikan dan transaksi ditandai sebagai direfund. Tindakan ini tidak bisa dibatalkan.
                </p>

                <div class="mt-4">
                    <InputLabel for="reason" value="Alasan Refund" />
                    <TextInput id="reason" v-model="refundForm.reason" class="mt-1 block w-full" autofocus placeholder="mis. Pelanggan salah pesan" />
                    <InputError :message="refundForm.errors.reason" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showRefundModal = false">Batal</SecondaryButton>
                    <DangerButton :disabled="refundForm.processing">Konfirmasi Refund</DangerButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
