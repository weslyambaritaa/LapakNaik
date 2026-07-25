<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    business: Object,
    bankAccounts: Array,
});

const form = useForm({
    qris_image: null,
});

const preview = ref(props.business.qris_image_url);

function onImageSelected(event) {
    const file = event.target.files[0] ?? null;
    form.qris_image = file;
    preview.value = file ? URL.createObjectURL(file) : props.business.qris_image_url;
}

function submit() {
    form.post(route('settings.payment.update'), {
        forceFormData: true,
        onSuccess: () => (form.qris_image = null),
    });
}

function removeQris() {
    if (confirm('Hapus QRIS toko? Pembeli tidak akan bisa memilih metode ini sampai kamu unggah gambar baru.')) {
        router.delete(route('settings.payment.destroy'), {
            onSuccess: () => (preview.value = null),
        });
    }
}

const bankForm = useForm({
    bank_name: '',
    account_number: '',
    account_holder_name: '',
});

function addBankAccount() {
    bankForm.post(route('bank-accounts.store'), {
        onSuccess: () => bankForm.reset(),
    });
}

function removeBankAccount(bankAccount) {
    if (confirm(`Hapus rekening ${bankAccount.bank_name} - ${bankAccount.account_number}?`)) {
        router.delete(route('bank-accounts.destroy', bankAccount.id));
    }
}
</script>

<template>
    <Head title="Pengaturan Pembayaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Pengaturan Pembayaran
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">QRIS Toko</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Unggah gambar QRIS milik tokomu sendiri (dari bank/e-wallet kamu). Pembayaran lewat QRIS ini
                        masuk langsung ke rekening/akunmu, bukan lewat Lapak Naik — jadi setelah pembeli membayar,
                        kamu yang perlu konfirmasi manual di halaman <strong>Pesanan Masuk</strong>.
                    </p>

                    <form class="mt-6" @submit.prevent="submit">
                        <div v-if="preview" class="mb-4">
                            <img :src="preview" alt="QRIS toko" class="h-48 w-48 rounded-lg border border-gray-200 object-contain dark:border-gray-700" />
                        </div>
                        <p v-else class="mb-4 text-sm text-gray-400 dark:text-gray-500">Belum ada QRIS yang diunggah.</p>

                        <InputLabel for="qris_image" value="Gambar QRIS (JPG/PNG, maks 4MB)" />
                        <input
                            id="qris_image"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300"
                            @change="onImageSelected"
                        />
                        <InputError :message="form.errors.qris_image" class="mt-2" />

                        <div class="mt-6 flex items-center gap-3">
                            <PrimaryButton :disabled="form.processing || !form.qris_image">Simpan</PrimaryButton>
                            <SecondaryButton v-if="business.qris_image_url" type="button" @click="removeQris">
                                Hapus QRIS
                            </SecondaryButton>
                        </div>
                    </form>
                </div>

                <div class="mt-6 bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">Transfer Bank</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Tambahkan satu atau lebih rekening bank tokomu. Saat pembeli memilih metode Transfer Bank di
                        etalase online, daftar rekening ini akan ditampilkan supaya mereka bisa transfer langsung —
                        sama seperti QRIS Toko, kamu perlu konfirmasi manual di halaman <strong>Pesanan Masuk</strong>
                        setelah menerima transfernya.
                    </p>

                    <ul v-if="bankAccounts.length" class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="account in bankAccounts" :key="account.id" class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ account.bank_name }} &middot; {{ account.account_number }}</p>
                                <p class="text-gray-500 dark:text-gray-400">a.n. {{ account.account_holder_name }}</p>
                            </div>
                            <button type="button" class="text-red-600 hover:underline dark:text-red-400" @click="removeBankAccount(account)">
                                Hapus
                            </button>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-gray-400 dark:text-gray-500">Belum ada rekening bank yang ditambahkan.</p>

                    <form class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-100 pt-6 dark:border-gray-700 sm:grid-cols-3" @submit.prevent="addBankAccount">
                        <div>
                            <InputLabel for="bank_name" value="Nama Bank" />
                            <TextInput id="bank_name" v-model="bankForm.bank_name" class="mt-1 block w-full" placeholder="BCA" />
                            <InputError :message="bankForm.errors.bank_name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="account_number" value="Nomor Rekening" />
                            <TextInput id="account_number" v-model="bankForm.account_number" class="mt-1 block w-full" />
                            <InputError :message="bankForm.errors.account_number" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="account_holder_name" value="Atas Nama" />
                            <TextInput id="account_holder_name" v-model="bankForm.account_holder_name" class="mt-1 block w-full" />
                            <InputError :message="bankForm.errors.account_holder_name" class="mt-2" />
                        </div>
                        <div class="sm:col-span-3">
                            <PrimaryButton :disabled="bankForm.processing">Tambah Rekening</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
