<script setup>
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatRupiah } from '@/formatters';
import { loadMidtransSnap } from '@/midtrans';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    business: Object,
    products: Array,
    categories: Array,
    bankAccounts: Array,
    filters: Object,
    midtrans: Object,
});

const search = ref(props.filters.search ?? '');
const categoryFilter = ref(props.filters.category_id ?? '');

watch([search, categoryFilter], ([searchValue, categoryId]) => {
    router.get(route('storefront.show', props.business.slug), { search: searchValue, category_id: categoryId }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const cart = ref([]);
const showCheckout = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const customerName = ref('');
const customerPhone = ref('');
const customerFound = ref(false);
const matchedName = ref('');
const paymentMethod = ref('qris');
let lookupTimeout = null;

// Debounced so it fires once typing pauses, not on every keystroke — kinder
// to the rate limit and to the server. Mirrors the "type your member number,
// your name shows up" flow the Kasir screen already has, just backed by a
// lookup request here since the storefront can't ship the full customer
// list to an anonymous visitor's browser.
//
// A registered phone locks the Nama field to the registered name (see
// :readonly in the template) — the buyer can't rename an existing customer
// at checkout. An unrecognized phone leaves it free to type, and checkout
// registers that phone+name as a new customer (see StorefrontOrderController).
watch(customerPhone, (phone) => {
    clearTimeout(lookupTimeout);

    // Leaving a previously-matched phone drops the lock and clears the name
    // — it belonged to the old phone number, not whatever's typed next.
    if (customerFound.value) {
        customerName.value = '';
    }
    customerFound.value = false;
    matchedName.value = '';

    const trimmed = phone.trim();
    if (!trimmed) return;

    lookupTimeout = setTimeout(async () => {
        try {
            const response = await window.axios.post(route('storefront.customer-lookup', props.business.slug), { phone: trimmed });

            if (response.data.name) {
                matchedName.value = response.data.name;
                customerFound.value = true;
                customerName.value = response.data.name;
            }
        } catch {
            // A failed lookup just means no autofill — checkout still works with a manually typed name.
        }
    }, 500);
});

function addToCart(product) {
    const existing = cart.value.find((item) => item.product_id === product.id);

    if (existing) {
        if (existing.quantity < product.stock) existing.quantity++;
        return;
    }

    if (product.stock < 1) return;

    cart.value.push({
        product_id: product.id,
        name: product.name,
        price: product.price,
        stock: product.stock,
        quantity: 1,
    });
}

function changeQuantity(item, delta) {
    const next = item.quantity + delta;

    if (next < 1) {
        cart.value = cart.value.filter((cartItem) => cartItem !== item);
        return;
    }

    if (next > item.stock) return;

    item.quantity = next;
}

const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));
const cartTotal = computed(() => cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0));

function openCheckout() {
    errorMessage.value = '';
    showCheckout.value = true;
}

async function submitOrder() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const response = await window.axios.post(route('storefront.checkout', props.business.slug), {
            customer_name: customerName.value,
            customer_phone: customerPhone.value,
            payment_method: paymentMethod.value,
            items: cart.value.map((item) => ({ product_id: item.product_id, quantity: item.quantity })),
        });

        const { snap_token: snapToken, is_production: isProduction, order_status_url: orderStatusUrl } = response.data;

        showCheckout.value = false;

        // QRIS Toko and Tunai have no gateway session — the order just sits
        // 'pending' until staff confirm it, so there's nothing to open here.
        if (!snapToken) {
            router.visit(orderStatusUrl);
            return;
        }

        const snap = await loadMidtransSnap(props.midtrans.client_key, isProduction);

        snap.pay(snapToken, {
            onSuccess: () => router.visit(orderStatusUrl),
            onPending: () => router.visit(orderStatusUrl),
            onError: () => {
                errorMessage.value = 'Pembayaran gagal diproses. Silakan coba lagi.';
                showCheckout.value = true;
            },
            onClose: () => router.visit(orderStatusUrl),
        });
    } catch (error) {
        const errors = error.response?.data?.errors;
        const message = error.response?.data?.message;
        errorMessage.value = errors ? Object.values(errors)[0][0] : (message ?? 'Gagal membuat pesanan. Silakan coba lagi.');
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Head :title="business.name" />

    <div class="min-h-screen bg-gray-50 pb-24 dark:bg-gray-900">
        <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-800">
            <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <p v-if="business.category" class="text-xs font-medium uppercase tracking-wide text-emerald-600 dark:text-emerald-400">
                    {{ business.category }}
                </p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ business.name }}</h1>
                <p v-if="business.description" class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-400">{{ business.description }}</p>
                <p v-if="business.address || business.phone" class="mt-3 text-sm text-gray-500 dark:text-gray-500">
                    <span v-if="business.address">{{ business.address }}</span>
                    <span v-if="business.address && business.phone"> &middot; </span>
                    <span v-if="business.phone">{{ business.phone }}</span>
                </p>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap gap-3">
                <TextInput v-model="search" type="search" placeholder="Cari produk..." class="w-full max-w-xs" />
                <SelectInput v-model="categoryFilter">
                    <option value="">Semua Kategori</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </SelectInput>
            </div>

            <div v-if="products.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-800"
                >
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        class="h-40 w-full object-cover"
                        alt=""
                    />
                    <div v-else class="flex h-40 w-full items-center justify-center bg-gray-50 text-gray-300 dark:bg-gray-900 dark:text-gray-700">
                        <svg class="size-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 12V4.5A2.25 2.25 0 014.5 2.25h15A2.25 2.25 0 0121.75 4.5v15A2.25 2.25 0 0119.5 21.75h-15A2.25 2.25 0 012.25 19.5V12z" />
                        </svg>
                    </div>

                    <div class="p-4">
                        <p v-if="product.category" class="text-xs uppercase tracking-wide text-gray-400">{{ product.category.name }}</p>
                        <h2 class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ product.name }}</h2>
                        <p v-if="product.description" class="mt-1 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ product.description }}</p>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ formatRupiah(product.price) }}</span>
                            <span
                                class="text-xs font-medium"
                                :class="product.stock > 0 ? 'text-gray-400' : 'text-red-500'"
                            >
                                {{ product.stock > 0 ? `Stok ${product.stock} ${product.unit}` : 'Stok habis' }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="mt-3 w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300 dark:disabled:bg-gray-700"
                            :disabled="product.stock < 1"
                            @click="addToCart(product)"
                        >
                            {{ product.stock < 1 ? 'Stok Habis' : '+ Keranjang' }}
                        </button>
                    </div>
                </div>
            </div>
            <p v-else class="text-center text-sm text-gray-500 dark:text-gray-400">
                Tidak ada produk yang cocok.
            </p>
        </main>

        <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400 dark:border-gray-800">
            Etalase online oleh {{ business.name }} &middot; ditenagai Lapak Naik
        </footer>

        <!-- Floating cart bar -->
        <div
            v-if="cartCount > 0"
            class="fixed inset-x-0 bottom-0 border-t border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">{{ cartCount }}</span> item &middot;
                    <span class="font-semibold tabular-nums">{{ formatRupiah(cartTotal) }}</span>
                </div>
                <PrimaryButton @click="openCheckout">Checkout &amp; Bayar QRIS</PrimaryButton>
            </div>
        </div>

        <Modal :show="showCheckout" @close="showCheckout = false">
            <form class="p-6" @submit.prevent="submitOrder">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Konfirmasi Pesanan</h2>

                <ul class="mt-4 space-y-2 border-b border-gray-200 pb-4 dark:border-gray-700">
                    <li v-for="item in cart" :key="item.product_id" class="flex items-center justify-between text-sm">
                        <span class="text-gray-700 dark:text-gray-300">{{ item.name }}</span>
                        <div class="flex items-center gap-2">
                            <button type="button" class="h-6 w-6 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300" @click="changeQuantity(item, -1)">-</button>
                            <span class="w-6 text-center tabular-nums">{{ item.quantity }}</span>
                            <button type="button" class="h-6 w-6 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300" @click="changeQuantity(item, 1)">+</button>
                            <span class="w-24 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ formatRupiah(item.price * item.quantity) }}</span>
                        </div>
                    </li>
                </ul>

                <div class="flex justify-between py-3 text-base font-semibold text-gray-900 dark:text-gray-100">
                    <span>Total</span>
                    <span class="tabular-nums">{{ formatRupiah(cartTotal) }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="customer_phone" value="No. WhatsApp (opsional)" />
                        <TextInput id="customer_phone" v-model="customerPhone" class="mt-1 block w-full" placeholder="08xxxxxxxxxx" />
                        <p v-if="customerFound" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                            Selamat datang kembali, {{ matchedName }}!
                        </p>
                        <p v-else class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Isi untuk kumpulkan poin & catat sebagai pelanggan tetap.
                        </p>
                    </div>
                    <div>
                        <InputLabel for="customer_name" value="Nama" />
                        <TextInput
                            id="customer_name"
                            v-model="customerName"
                            class="mt-1 block w-full"
                            :class="customerFound ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-900' : ''"
                            required
                            :readonly="customerFound"
                        />
                        <p v-if="customerFound" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Nama mengikuti nomor yang sudah terdaftar.
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Metode Pembayaran" />
                        <div class="mt-2 space-y-2">
                            <label
                                class="flex cursor-pointer items-start gap-2 rounded-md border p-3 text-sm dark:border-gray-700"
                                :class="paymentMethod === 'qris' ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-200'"
                            >
                                <input v-model="paymentMethod" type="radio" value="qris" class="mt-0.5" />
                                <span>
                                    <span class="block font-medium text-gray-900 dark:text-gray-100">QRIS (Otomatis)</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Scan &amp; bayar langsung, pesanan otomatis terkonfirmasi.</span>
                                </span>
                            </label>

                            <label
                                v-if="business.qris_image_url"
                                class="flex cursor-pointer items-start gap-2 rounded-md border p-3 text-sm dark:border-gray-700"
                                :class="paymentMethod === 'qris_store' ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-200'"
                            >
                                <input v-model="paymentMethod" type="radio" value="qris_store" class="mt-0.5" />
                                <span>
                                    <span class="block font-medium text-gray-900 dark:text-gray-100">QRIS Toko</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Scan QR milik toko ini, dikonfirmasi toko setelah menerima pembayaran.</span>
                                </span>
                            </label>

                            <label
                                v-if="bankAccounts.length"
                                class="flex cursor-pointer items-start gap-2 rounded-md border p-3 text-sm dark:border-gray-700"
                                :class="paymentMethod === 'transfer' ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-200'"
                            >
                                <input v-model="paymentMethod" type="radio" value="transfer" class="mt-0.5" />
                                <span class="w-full">
                                    <span class="block font-medium text-gray-900 dark:text-gray-100">Transfer Bank</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Transfer manual ke rekening toko, dikonfirmasi toko setelah menerima.</span>

                                    <span v-if="paymentMethod === 'transfer'" class="mt-2 block space-y-1 border-t border-gray-100 pt-2 dark:border-gray-700">
                                        <span v-for="account in bankAccounts" :key="account.id" class="block text-xs text-gray-600 dark:text-gray-300">
                                            <strong>{{ account.bank_name }}</strong> {{ account.account_number }} a.n. {{ account.account_holder_name }}
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-2 rounded-md border p-3 text-sm dark:border-gray-700"
                                :class="paymentMethod === 'cash' ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-200'"
                            >
                                <input v-model="paymentMethod" type="radio" value="cash" class="mt-0.5" />
                                <span>
                                    <span class="block font-medium text-gray-900 dark:text-gray-100">Tunai (bayar di kasir saat ambil pesanan)</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Tunjukkan halaman pesanan ke kasir dan bayar tunai di tempat.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <p v-if="errorMessage" class="mt-4 text-sm text-red-600 dark:text-red-400">{{ errorMessage }}</p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showCheckout = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="submitting">{{ paymentMethod === 'qris' ? 'Bayar dengan QRIS' : 'Pesan Sekarang' }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </div>
</template>
