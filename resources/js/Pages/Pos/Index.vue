<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatRupiah } from '@/formatters';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    products: Array,
    customers: Array,
});

const search = ref('');
const cart = ref([]);

const filteredProducts = computed(() => {
    if (!search.value) return props.products;

    const term = search.value.toLowerCase();

    return props.products.filter((product) => product.name.toLowerCase().includes(term));
});

function addToCart(product) {
    const existing = cart.value.find((item) => item.product_id === product.id);

    if (existing) {
        if (existing.quantity < product.stock) {
            existing.quantity++;
        }
        return;
    }

    if (product.stock < 1) return;

    cart.value.push({
        product_id: product.id,
        name: product.name,
        price: product.price,
        unit: product.unit,
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

function removeItem(item) {
    cart.value = cart.value.filter((cartItem) => cartItem !== item);
}

const subtotal = computed(() =>
    cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0),
);

const form = useForm({
    customer_phone: '',
    discount: 0,
    payment_method: 'cash',
});

const total = computed(() => Math.max(subtotal.value - (form.discount || 0), 0));

// Phone is the member lookup key — matched live against the customers
// already loaded for this business, no extra request needed.
const matchedCustomer = computed(() => {
    const phone = form.customer_phone.trim();

    return phone ? props.customers.find((customer) => customer.phone === phone) ?? null : null;
});

const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));

function scrollToCart() {
    document.getElementById('cart-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function checkout() {
    form
        .transform((data) => ({
            ...data,
            customer_phone: data.customer_phone.trim() || null,
            items: cart.value.map((item) => ({
                product_id: item.product_id,
                quantity: item.quantity,
            })),
        }))
        .post(route('pos.store'), {
            onSuccess: () => {
                cart.value = [];
                form.reset();
            },
        });
}
</script>

<template>
    <Head title="Kasir" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Kasir
                </h2>
                <Link :href="route('pos.history')" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                    Riwayat Transaksi &rarr;
                </Link>
            </div>
        </template>

        <div class="py-8 pb-24 lg:pb-8">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
                <!-- Product picker -->
                <div class="lg:col-span-2">
                    <TextInput v-model="search" type="search" placeholder="Cari produk..." class="mb-4 block w-full" />

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <button
                            v-for="product in filteredProducts"
                            :key="product.id"
                            type="button"
                            class="min-h-[88px] rounded-lg border border-gray-200 bg-white p-3 text-left shadow-sm transition hover:border-indigo-400 active:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:active:bg-gray-700"
                            :disabled="product.stock < 1"
                            @click="addToCart(product)"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ product.name }}</div>
                            <div class="mt-1 text-sm tabular-nums text-gray-500 dark:text-gray-400">{{ formatRupiah(product.price) }}</div>
                            <div class="mt-1 text-xs text-gray-400">Stok: {{ product.stock }} {{ product.unit }}</div>
                        </button>
                        <p v-if="filteredProducts.length === 0" class="col-span-full text-sm text-gray-500 dark:text-gray-400">
                            Produk tidak ditemukan.
                        </p>
                    </div>
                </div>

                <!-- Cart -->
                <div id="cart-panel" class="scroll-mt-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Keranjang</h3>

                    <div v-if="cart.length === 0" class="py-8 text-center text-sm text-gray-400">
                        Belum ada produk dipilih.
                    </div>

                    <ul v-else class="space-y-3">
                        <li v-for="item in cart" :key="item.product_id" class="flex items-center justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                                <div class="text-xs tabular-nums text-gray-500 dark:text-gray-400">{{ formatRupiah(item.price) }} / {{ item.unit }}</div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" class="h-6 w-6 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300" @click="changeQuantity(item, -1)">-</button>
                                <span class="w-6 text-center text-sm tabular-nums">{{ item.quantity }}</span>
                                <button type="button" class="h-6 w-6 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300" @click="changeQuantity(item, 1)">+</button>
                            </div>
                            <button type="button" class="text-xs text-red-500 hover:underline" @click="removeItem(item)">Hapus</button>
                        </li>
                    </ul>

                    <div class="mt-4 space-y-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">No. HP Pelanggan (opsional)</label>
                            <TextInput
                                v-model="form.customer_phone"
                                placeholder="08xxxxxxxxxx"
                                class="mt-1 block w-full"
                            />
                            <p v-if="matchedCustomer" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                Pelanggan: {{ matchedCustomer.name }}
                            </p>
                            <p v-else-if="form.customer_phone.trim()" class="mt-1 text-xs text-gray-400">
                                Nomor belum terdaftar. Transaksi tetap bisa lanjut tanpa data pelanggan, atau
                                <Link :href="route('customers.index')" target="_blank" class="text-indigo-600 underline dark:text-indigo-400">
                                    daftarkan dulu di menu Pelanggan
                                </Link>.
                            </p>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">Diskon (Rp)</label>
                            <TextInput type="number" min="0" v-model.number="form.discount" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">Metode Pembayaran</label>
                            <SelectInput v-model="form.payment_method" class="mt-1 block w-full">
                                <option value="cash">Tunai</option>
                                <option value="qris">QRIS</option>
                                <option value="transfer">Transfer</option>
                            </SelectInput>
                        </div>

                        <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span class="tabular-nums">{{ formatRupiah(subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-semibold text-gray-900 dark:text-gray-100">
                            <span>Total</span>
                            <span class="tabular-nums">{{ formatRupiah(total) }}</span>
                        </div>

                        <p v-if="Object.keys(form.errors).length" class="text-sm text-red-600 dark:text-red-400">
                            {{ Object.values(form.errors)[0] }}
                        </p>

                        <PrimaryButton
                            class="w-full justify-center"
                            :disabled="cart.length === 0 || form.processing"
                            @click="checkout"
                        >
                            Bayar
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile-only: floating shortcut down to the cart, since it sits below a potentially long product grid -->
        <button
            v-if="cartCount > 0"
            type="button"
            class="fixed inset-x-4 bottom-4 flex items-center justify-between rounded-lg bg-indigo-600 px-4 py-3 text-sm font-medium text-white shadow-lg lg:hidden"
            @click="scrollToCart"
        >
            <span>{{ cartCount }} item di keranjang</span>
            <span class="tabular-nums">{{ formatRupiah(total) }} &darr;</span>
        </button>
    </AuthenticatedLayout>
</template>
