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
    categories: Array,
});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(category) {
    editing.value = category;
    form.name = category.name;
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('categories.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('categories.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
}

function destroy(category) {
    if (confirm(`Hapus kategori "${category.name}"?`)) {
        router.delete(route('categories.destroy', category.id));
    }
}
</script>

<template>
    <Head title="Kategori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Kategori Produk
                </h2>
                <PrimaryButton @click="openCreate">Tambah Kategori</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Produk</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="category in categories" :key="category.id">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ category.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ category.products_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button class="text-indigo-600 hover:underline dark:text-indigo-400" @click="openEdit(category)">Ubah</button>
                                    <button class="ml-4 text-red-600 hover:underline dark:text-red-400" @click="destroy(category)">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada kategori. Tambahkan kategori pertama kamu.
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
                    {{ editing ? 'Ubah Kategori' : 'Tambah Kategori' }}
                </h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Nama Kategori" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
