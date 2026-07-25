<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatRupiah, formatDate } from '@/formatters';
import { Head, useForm, router } from '@inertiajs/vue3';

defineProps({
    cashFlows: Object,
    summary: Object,
});

const showModal = ref(false);

const form = useForm({
    type: 'out',
    category: '',
    amount: 0,
    description: '',
    date: new Date().toISOString().slice(0, 10),
});

function openCreate() {
    form.reset();
    form.type = 'out';
    form.date = new Date().toISOString().slice(0, 10);
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    form.post(route('cash-flows.store'), {
        onSuccess: () => (showModal.value = false),
    });
}

function destroy(entry) {
    if (confirm('Hapus catatan kas ini?')) {
        router.delete(route('cash-flows.destroy', entry.id));
    }
}

const categoryPresets = ['Sewa', 'Listrik & Air', 'Gaji', 'Belanja Bahan', 'Transportasi', 'Lainnya'];
</script>

<template>
    <Head title="Arus Kas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Arus Kas
                </h2>
                <PrimaryButton @click="openCreate">Tambah Catatan</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Pemasukan (bulan ini)</div>
                        <div class="mt-1 text-xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ formatRupiah(summary.income) }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Pengeluaran (bulan ini)</div>
                        <div class="mt-1 text-xl font-semibold tabular-nums text-red-600 dark:text-red-400">{{ formatRupiah(summary.expense) }}</div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Keterangan</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="entry in cashFlows.data" :key="entry.id">
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(entry.date) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ entry.category }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ entry.description || '—' }}</td>
                                <td
                                    class="px-6 py-4 text-right text-sm font-medium tabular-nums"
                                    :class="entry.type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                >
                                    {{ entry.type === 'in' ? '+' : '-' }}{{ formatRupiah(entry.amount) }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button class="text-red-600 hover:underline dark:text-red-400" @click="destroy(entry)">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="cashFlows.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada catatan kas non-penjualan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="cashFlows.links" />
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tambah Catatan Kas</h2>

                <div class="mt-4">
                    <InputLabel for="type" value="Jenis" />
                    <SelectInput id="type" v-model="form.type" class="mt-1 block w-full">
                        <option value="out">Pengeluaran</option>
                        <option value="in">Pemasukan (di luar penjualan)</option>
                    </SelectInput>
                </div>

                <div class="mt-4">
                    <InputLabel for="category" value="Kategori" />
                    <TextInput id="category" v-model="form.category" list="category-presets" class="mt-1 block w-full" />
                    <datalist id="category-presets">
                        <option v-for="preset in categoryPresets" :key="preset" :value="preset" />
                    </datalist>
                    <InputError :message="form.errors.category" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="amount" value="Jumlah" />
                    <TextInput id="amount" type="number" min="1" v-model.number="form.amount" class="mt-1 block w-full" />
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="date" value="Tanggal" />
                    <TextInput id="date" type="date" v-model="form.date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.date" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="description" value="Keterangan (opsional)" />
                    <TextInput id="description" v-model="form.description" class="mt-1 block w-full" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
