<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

defineProps({
    suppliers: Array,
});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    phone: '',
    address: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(supplier) {
    editing.value = supplier;
    form.name = supplier.name;
    form.phone = supplier.phone;
    form.address = supplier.address;
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('suppliers.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('suppliers.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
}

function destroy(supplier) {
    if (confirm(`Hapus pemasok "${supplier.name}"?`)) {
        router.delete(route('suppliers.destroy', supplier.id));
    }
}
</script>

<template>
    <Head title="Pemasok" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Pemasok
                </h2>
                <PrimaryButton @click="openCreate">Tambah Pemasok</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Telepon</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Alamat</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="supplier in suppliers" :key="supplier.id">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ supplier.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ supplier.phone || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ supplier.address || '—' }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button class="text-indigo-600 hover:underline dark:text-indigo-400" @click="openEdit(supplier)">Ubah</button>
                                    <button class="ml-4 text-red-600 hover:underline dark:text-red-400" @click="destroy(supplier)">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="suppliers.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada pemasok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? 'Ubah Pemasok' : 'Tambah Pemasok' }}
                </h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Nama Pemasok" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="phone" value="Telepon" />
                    <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                    <InputError :message="form.errors.phone" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="address" value="Alamat" />
                    <TextInput id="address" v-model="form.address" class="mt-1 block w-full" />
                    <InputError :message="form.errors.address" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
