<script setup>
import { computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const page = usePage();
const user = computed(() => page.props.auth?.user);

const features = [
    {
        title: "Kasir (POS)",
        description:
            "Transaksi cepat, cetak struk, dan stok berkurang otomatis setiap kali ada penjualan.",
        icon: "M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.99-4.708 2.545-7.163a1.125 1.125 0 00-1.098-1.37H5.25M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z",
    },
    {
        title: "Produk & Stok",
        description:
            "Kelola kategori, harga, dan pergerakan stok (masuk/keluar/koreksi) dalam satu tempat.",
        icon: "M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9",
    },
    {
        title: "Dashboard & Laporan",
        description:
            "Grafik omzet, produk terlaris, dan peringatan stok menipis — dilihat sekali jalan.",
        icon: "M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z",
    },
    {
        title: "Skor Kelayakan Usaha",
        description:
            "Nilai kesehatan bisnis 0-100 dari data transaksi asli — bahan pendukung pengajuan modal.",
        icon: "M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z",
    },
    {
        title: "Etalase Online + QRIS",
        description:
            "Katalog publik yang bisa dibagikan — pelanggan pesan dan bayar langsung lewat QRIS.",
        icon: "M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 15.375c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z",
    },
    {
        title: "Manajemen Karyawan",
        description:
            "Pemilik usaha bisa buat akun admin/kasir dengan akses yang dibatasi sesuai peran.",
        icon: "M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z",
    },
];

const steps = [
    {
        title: "Kelola",
        description: "Atur produk, harga, dan stok sekali di awal.",
    },
    {
        title: "Jual",
        description:
            "Layani pelanggan lewat kasir di toko atau etalase online.",
    },
    {
        title: "Berkembang",
        description:
            "Pantau dashboard dan skor kelayakan usaha untuk ambil keputusan.",
    },
];
</script>

<template>
    <Head title="Lapak Naik — Digitalisasi UMKM" />

    <div class="flex min-h-screen flex-col bg-white dark:bg-gray-900">
        <!-- Nav -->
        <header class="shrink-0 border-b border-gray-100 dark:border-gray-800">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8"
            >
                <ApplicationLogo class="text-lg text-gray-900 dark:text-white" />

                <nav class="flex items-center gap-3">
                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
                        >
                            Daftar
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section
            class="mx-auto flex w-full max-w-6xl flex-1 flex-col items-center justify-center px-4 py-16 text-center sm:px-6 lg:px-8"
        >
            <h1
                class="mx-auto max-w-3xl text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl dark:text-white"
            >
                Digitalisasi &amp; inklusi finansial untuk UMKM naik kelas
            </h1>

            <p
                class="mx-auto mt-5 max-w-2xl text-lg text-gray-500 dark:text-gray-400"
            >
                Kelola stok, layani pelanggan lewat kasir, dan buka etalase
                online dengan pembayaran QRIS — semua dalam satu aplikasi,
                sambil dapat gambaran kelayakan usaha secara otomatis.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <Link
                    :href="route(user ? 'dashboard' : 'register')"
                    class="rounded-md bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    {{ user ? "Buka Dashboard" : "Daftarkan Usaha" }}
                </Link>
                <Link
                    :href="route('storefront.show', 'warung-bu-sari')"
                    class="rounded-md border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:border-gray-400 dark:border-gray-700 dark:text-gray-200 dark:hover:border-gray-600"
                >
                    Lihat Demo Etalase &rarr;
                </Link>
            </div>
        </section>

        <!-- Features -->
        <section
            class="border-t border-gray-100 bg-gray-50 py-16 dark:border-gray-800 dark:bg-gray-800/40"
        >
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2
                    class="text-center text-2xl font-bold text-gray-900 dark:text-white"
                >
                    Semua yang dibutuhkan usaha kecil
                </h2>

                <div
                    class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/40"
                        >
                            <svg
                                class="size-5 text-emerald-600 dark:text-emerald-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :d="feature.icon"
                                />
                            </svg>
                        </div>
                        <h3
                            class="mt-4 font-semibold text-gray-900 dark:text-white"
                        >
                            {{ feature.title }}
                        </h3>
                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2
                    class="text-center text-2xl font-bold text-gray-900 dark:text-white"
                >
                    Cara kerjanya
                </h2>

                <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-3">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="text-center"
                    >
                        <div
                            class="mx-auto flex size-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white"
                        >
                            {{ index + 1 }}
                        </div>
                        <h3
                            class="mt-3 font-semibold text-gray-900 dark:text-white"
                        >
                            {{ step.title }}
                        </h3>
                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ step.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
