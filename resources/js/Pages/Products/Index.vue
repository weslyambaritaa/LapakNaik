<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatRupiah } from '@/formatters';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    products: Object,
    categories: Array,
    suppliers: Array,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const categoryFilter = ref(props.filters.category_id ?? '');

watch([search, categoryFilter], ([searchValue, categoryId]) => {
    router.get(route('products.index'), { search: searchValue, category_id: categoryId }, {
        preserveState: true,
        replace: true,
    });
});

const showProductModal = ref(false);
const showStockModal = ref(false);
const editing = ref(null);
const adjusting = ref(null);

const form = useForm({
    category_id: '',
    sku: '',
    name: '',
    description: '',
    price: 0,
    cost_price: 0,
    stock: 0,
    unit: 'pcs',
    is_active: true,
    image: null,
});

const imagePreview = ref(null);

function onImageSelected(event) {
    const file = event.target.files[0] ?? null;
    form.image = file;
    imagePreview.value = file ? URL.createObjectURL(file) : null;
}

const stockForm = useForm({
    type: 'in',
    quantity: 1,
    supplier_id: '',
    note: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.unit = 'pcs';
    form.is_active = true;
    form.clearErrors();
    imagePreview.value = null;
    showProductModal.value = true;
}

function openEdit(product) {
    editing.value = product;
    form.category_id = product.category_id ?? '';
    form.sku = product.sku ?? '';
    form.name = product.name;
    form.description = product.description ?? '';
    form.price = product.price;
    form.cost_price = product.cost_price;
    form.stock = product.stock;
    form.unit = product.unit;
    form.is_active = product.is_active;
    form.image = null;
    form.clearErrors();
    imagePreview.value = product.image_url;
    showProductModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('products.update', editing.value.id), {
            onSuccess: () => (showProductModal.value = false),
        });
    } else {
        form.post(route('products.store'), {
            onSuccess: () => (showProductModal.value = false),
        });
    }
}

function destroy(product) {
    if (confirm(`Hapus produk "${product.name}"?`)) {
        router.delete(route('products.destroy', product.id));
    }
}

function openStock(product) {
    adjusting.value = product;
    stockForm.reset();
    stockForm.type = 'in';
    stockForm.quantity = 1;
    stockForm.clearErrors();
    showStockModal.value = true;
}

function submitStock() {
    stockForm.post(route('products.stock', adjusting.value.id), {
        onSuccess: () => (showStockModal.value = false),
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Produk" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Produk &amp; Stok
                </h2>
                <PrimaryButton @click="openCreate">Tambah Produk</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex flex-wrap gap-3">
                    <TextInput v-model="search" type="search" placeholder="Cari produk..." class="w-full max-w-xs" />
                    <SelectInput v-model="categoryFilter">
                        <option value="">Semua Kategori</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </SelectInput>
                </div>

                <div class="overflow-x-auto bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Produk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategori</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Harga</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Stok</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="product in products.data" :key="product.id">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="product.image_url"
                                            :src="product.image_url"
                                            class="size-10 shrink-0 rounded object-cover"
                                            alt=""
                                        />
                                        <div v-else class="flex size-10 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-300 dark:bg-gray-700">
                                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 12V4.5A2.25 2.25 0 014.5 2.25h15A2.25 2.25 0 0121.75 4.5v15A2.25 2.25 0 0119.5 21.75h-15A2.25 2.25 0 012.25 19.5V12z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ product.name }}</div>
                                            <div v-if="product.sku" class="font-mono text-xs text-gray-400">{{ product.sku }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ product.category?.name ?? '—' }}</td>
                                <td class="px-6 py-4 text-right text-sm tabular-nums text-gray-900 dark:text-gray-100">{{ formatRupiah(product.price) }}</td>
                                <td
                                    class="px-6 py-4 text-right text-sm tabular-nums"
                                    :class="product.stock <= 5 ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400'"
                                >
                                    {{ product.stock }} {{ product.unit }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="product.is_active
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                                    >
                                        {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button class="text-emerald-600 hover:underline dark:text-emerald-400" @click="openStock(product)">Atur Stok</button>
                                    <button class="ml-3 text-indigo-600 hover:underline dark:text-indigo-400" @click="openEdit(product)">Ubah</button>
                                    <button class="ml-3 text-red-600 hover:underline dark:text-red-400" @click="destroy(product)">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada produk yang cocok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="products.links" />
                </div>
            </div>
        </div>

        <!-- Create / Edit Product -->
        <Modal :show="showProductModal" @close="showProductModal = false" max-width="lg">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? 'Ubah Produk' : 'Tambah Produk' }}
                </h2>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="image" value="Foto Produk (opsional)" />
                        <div class="mt-1 flex items-center gap-4">
                            <img
                                v-if="imagePreview"
                                :src="imagePreview"
                                class="size-16 rounded-md object-cover"
                                alt=""
                            />
                            <div v-else class="flex size-16 items-center justify-center rounded-md bg-gray-100 text-gray-300 dark:bg-gray-700">
                                <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 12V4.5A2.25 2.25 0 014.5 2.25h15A2.25 2.25 0 0121.75 4.5v15A2.25 2.25 0 0119.5 21.75h-15A2.25 2.25 0 012.25 19.5V12z" />
                                </svg>
                            </div>
                            <input id="image" type="file" accept="image/*" class="block text-sm text-gray-600 dark:text-gray-400" @change="onImageSelected" />
                        </div>
                        <InputError :message="form.errors.image" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="name" value="Nama Produk" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" autofocus />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="category_id" value="Kategori" />
                        <SelectInput id="category_id" v-model="form.category_id" class="mt-1 block w-full">
                            <option value="">Tanpa kategori</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </SelectInput>
                        <InputError :message="form.errors.category_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="sku" value="SKU (opsional)" />
                        <TextInput id="sku" v-model="form.sku" class="mt-1 block w-full" />
                        <InputError :message="form.errors.sku" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="price" value="Harga Jual" />
                        <TextInput id="price" type="number" min="0" v-model.number="form.price" class="mt-1 block w-full" />
                        <InputError :message="form.errors.price" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="cost_price" value="Harga Modal" />
                        <TextInput id="cost_price" type="number" min="0" v-model.number="form.cost_price" class="mt-1 block w-full" />
                        <InputError :message="form.errors.cost_price" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="stock" :value="editing ? 'Stok Saat Ini' : 'Stok Awal'" />
                        <TextInput id="stock" type="number" min="0" v-model.number="form.stock" class="mt-1 block w-full" :disabled="!!editing" />
                        <p v-if="editing" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gunakan tombol "Atur Stok" untuk mengubah stok.</p>
                        <InputError :message="form.errors.stock" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="unit" value="Satuan" />
                        <TextInput id="unit" v-model="form.unit" class="mt-1 block w-full" placeholder="pcs, kg, porsi, ..." />
                        <InputError :message="form.errors.unit" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <label class="flex items-center">
                            <Checkbox v-model:checked="form.is_active" />
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Aktif dijual</span>
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="description" value="Deskripsi (opsional)" />
                        <TextareaInput id="description" v-model="form.description" class="mt-1 block w-full" rows="2" />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showProductModal = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Stock Adjustment -->
        <Modal :show="showStockModal" @close="showStockModal = false">
            <form class="p-6" @submit.prevent="submitStock">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Atur Stok — {{ adjusting?.name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Stok saat ini: {{ adjusting?.stock }} {{ adjusting?.unit }}</p>

                <div class="mt-4">
                    <InputLabel for="type" value="Jenis" />
                    <SelectInput id="type" v-model="stockForm.type" class="mt-1 block w-full">
                        <option value="in">Stok Masuk</option>
                        <option value="out">Stok Keluar</option>
                        <option value="adjustment">Koreksi</option>
                    </SelectInput>
                </div>

                <div class="mt-4">
                    <InputLabel for="quantity" value="Jumlah" />
                    <TextInput id="quantity" type="number" min="1" v-model.number="stockForm.quantity" class="mt-1 block w-full" />
                    <InputError :message="stockForm.errors.quantity" class="mt-2" />
                </div>

                <div v-if="stockForm.type === 'in'" class="mt-4">
                    <InputLabel for="supplier_id" value="Pemasok (opsional)" />
                    <SelectInput id="supplier_id" v-model="stockForm.supplier_id" class="mt-1 block w-full">
                        <option value="">—</option>
                        <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                            {{ supplier.name }}
                        </option>
                    </SelectInput>
                </div>

                <div class="mt-4">
                    <InputLabel for="note" value="Catatan (opsional)" />
                    <TextInput id="note" v-model="stockForm.note" class="mt-1 block w-full" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showStockModal = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="stockForm.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
