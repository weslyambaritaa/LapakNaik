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
import { Head, useForm, router } from '@inertiajs/vue3';

defineProps({
    staff: Array,
});

const showModal = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'kasir',
});

function openCreate() {
    form.reset();
    form.role = 'kasir';
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    form.post(route('staff.store'), {
        onSuccess: () => (showModal.value = false),
    });
}

function destroy(member) {
    if (confirm(`Hapus akun "${member.name}"?`)) {
        router.delete(route('staff.destroy', member.id));
    }
}

const roleLabels = { owner: 'Pemilik', admin: 'Admin', kasir: 'Kasir' };
</script>

<template>
    <Head title="Karyawan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Karyawan
                </h2>
                <PrimaryButton @click="openCreate">Tambah Karyawan</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Peran</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="member in staff" :key="member.id">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ member.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ member.email }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ roleLabels[member.role] }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button
                                        v-if="member.role !== 'owner'"
                                        class="text-red-600 hover:underline dark:text-red-400"
                                        @click="destroy(member)"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tambah Karyawan</h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Nama" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="role" value="Peran" />
                    <SelectInput id="role" v-model="form.role" class="mt-1 block w-full">
                        <option value="kasir">Kasir</option>
                        <option value="admin">Admin</option>
                    </SelectInput>
                    <InputError :message="form.errors.role" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password" value="Password" />
                    <TextInput id="password" type="password" v-model="form.password" class="mt-1 block w-full" />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password_confirmation" value="Konfirmasi Password" />
                    <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" class="mt-1 block w-full" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
