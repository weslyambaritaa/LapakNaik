<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    customers: Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    router.get(route('customers.index'), { search: value }, { preserveState: true, replace: true });
});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    phone: '',
    email: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(customer) {
    editing.value = customer;
    form.name = customer.name;
    form.phone = customer.phone;
    form.email = customer.email;
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('customers.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
}

function destroy(customer) {
    if (confirm(`Hapus pelanggan "${customer.name}"?`)) {
        router.delete(route('customers.destroy', customer.id));
    }
}
</script>

<template>
    <Head title="Pelanggan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Pelanggan
                </h2>
                <PrimaryButton @click="openCreate">Tambah Pelanggan</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <TextInput v-model="search" type="search" placeholder="Cari pelanggan..." class="mb-4 block w-full max-w-xs" />

                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kontak</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Transaksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Poin</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="customer in customers.data" :key="customer.id">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ customer.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ customer.phone || customer.email || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ customer.transactions_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ customer.loyalty_points }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button class="text-indigo-600 hover:underline dark:text-indigo-400" @click="openEdit(customer)">Ubah</button>
                                    <button class="ml-4 text-red-600 hover:underline dark:text-red-400" @click="destroy(customer)">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="customers.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada pelanggan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? 'Ubah Pelanggan' : 'Tambah Pelanggan' }}
                </h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Nama" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="phone" value="Telepon" />
                    <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                    <InputError :message="form.errors.phone" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
