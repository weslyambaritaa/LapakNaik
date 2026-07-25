<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 90px 60px 70px 60px; }
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }

        h1 { font-size: 20px; color: #0f172a; margin: 0 0 4px; }
        h2 { font-size: 15px; color: #1f7a5c; border-bottom: 2px solid #1f7a5c; padding-bottom: 4px; margin: 26px 0 12px; page-break-after: avoid; }
        h3 { font-size: 12.5px; color: #0f172a; margin: 16px 0 6px; page-break-after: avoid; }
        p { margin: 0 0 10px; text-align: justify; }
        ul, ol { margin: 0 0 10px; padding-left: 20px; }
        li { margin-bottom: 4px; }
        .muted { color: #6b7280; }
        .small { font-size: 9.5px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data th { background: #f0f5f2; border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; color: #374151; }
        table.data td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; font-size: 10px; }
        table.data td.mono { font-family: 'Courier New', monospace; }

        .step-box { border: 1px solid #c7d9d1; background: #f7faf8; border-radius: 4px; padding: 8px 10px; margin-bottom: 8px; }
        .step-num { display: inline-block; width: 18px; height: 18px; background: #1f7a5c; color: #fff; text-align: center; font-size: 10px; font-weight: bold; border-radius: 9px; margin-right: 6px; }

        .cover { text-align: center; padding-top: 160px; }
        .cover .eyebrow { color: #1f7a5c; font-size: 11px; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 14px; }
        .cover h1 { font-size: 32px; }
        .cover .tagline { color: #6b7280; font-size: 13px; margin-top: 6px; }
        .cover table { margin: 60px auto 0; width: 70%; border-collapse: collapse; }
        .cover table td { padding: 5px 10px; text-align: left; font-size: 11px; border-bottom: 1px solid #e5e7eb; }
        .cover table td.label { color: #6b7280; width: 35%; }

        .box-diagram td { border: 1px solid #1f7a5c; background: #f7faf8; padding: 8px; text-align: center; font-size: 9.5px; vertical-align: middle; }
        .box-diagram .arrow { border: none; background: none; font-size: 14px; color: #1f7a5c; }

        .badge { display: inline-block; background: #eef2f6; border-radius: 3px; padding: 1px 6px; font-size: 9px; color: #374151; }
        .disclaimer { border-left: 3px solid #b8641f; background: #fbf3ea; padding: 8px 12px; font-size: 10px; }
        code { font-family: 'Courier New', monospace; background: #f0f0f0; padding: 1px 4px; border-radius: 2px; font-size: 10px; }
        .codeblock { font-family: 'Courier New', monospace; background: #0f172a; color: #e2e8f0; padding: 10px 12px; border-radius: 4px; font-size: 9.5px; margin-bottom: 10px; }
        .codeblock div { margin-bottom: 2px; }
    </style>
</head>
<body>

{{-- ===================== COVER ===================== --}}
<div class="cover">
    <div class="eyebrow">Dokumentasi Teknis &middot; INSEVENT 2026</div>
    <h1>Lapak Naik</h1>
    <div class="tagline">Digitalisasi &amp; Inklusi Finansial untuk UMKM Naik Kelas</div>
    <div class="tagline small">Subtema A &mdash; SDG 8: Digital Innovation for Economic Challenges</div>

    <table>
        <tr><td class="label">Nama Tim</td><td>{{ $team['name'] ?? '[ISI NAMA TIM]' }}</td></tr>
        <tr><td class="label">Asal Institusi</td><td>{{ $team['institution'] ?? '[ISI ASAL KAMPUS / PROGRAM STUDI]' }}</td></tr>
        @foreach (($team['members'] ?? []) as $member)
            <tr><td class="label">{{ $loop->first ? 'Anggota Tim' : '' }}</td><td>{{ $member['name'] }} &mdash; {{ $member['nim'] }}</td></tr>
        @endforeach
        <tr><td class="label">&nbsp;</td><td class="muted small">[Lengkapi hingga 3&ndash;5 anggota sesuai ketentuan lomba]</td></tr>
        <tr><td class="label">Repositori</td><td>{{ $team['repo_url'] ?? '[ISI URL GITHUB PUBLIK]' }}</td></tr>
        <tr><td class="label">Demo Online</td><td>{{ $team['deploy_url'] ?? '[ISI URL DEPLOYMENT]' }}</td></tr>
    </table>
</div>

<div style="page-break-after: always;"></div>

{{-- ===================== 1. LATAR BELAKANG ===================== --}}
<h2>1. Latar Belakang &amp; Rumusan Masalah</h2>

<p>
    Berdasarkan data <span class="bold">SIDT-UMKM (Sistem Informasi Data Tunggal UMKM)</span> yang dikelola
    Kementerian UMKM Republik Indonesia, jumlah UMKM non-pertanian di Indonesia per 31 Desember 2025 mencapai
    <span class="bold">30.209.069 unit usaha</span>. Namun dari jumlah tersebut, baru
    <span class="bold">1.059.072 unit usaha (sekitar 3,51%)</span> yang tercatat memiliki laporan keuangan &mdash;
    artinya <span class="bold">96,49% UMKM terdaftar belum memiliki pembukuan yang tertata</span>
    (Kementerian UMKM RI, SIDT-UMKM 2025, dikutip UKMINDONESIA.ID).
</p>

<p>
    Ketiadaan pembukuan ini berkorelasi langsung dengan akses pembiayaan. Data Otoritas Jasa Keuangan (OJK)
    mencatat porsi kredit UMKM terhadap Produk Domestik Bruto (PDB) justru menurun dari 7,20% (kuartal IV 2021)
    menjadi 6,66% (kuartal I 2025), dan kredit UMKM sempat terkontraksi 0,56% (year-on-year) pada Februari 2026
    &mdash; jauh lebih lambat dibanding pertumbuhan kredit korporasi.
</p>

<p>
    Merespons hal ini, OJK menerbitkan <span class="bold">POJK Nomor 19 Tahun 2025</span> tentang Kemudahan Akses
    Pembiayaan kepada UMKM, yang secara eksplisit mendorong bank dan lembaga keuangan non-bank untuk
    <span class="bold">mengoptimalkan credit scoring berbasis data serta segmentasi dan profiling UMKM</span>
    sebagai alternatif penilaian kelayakan pembiayaan di luar mekanisme perbankan konvensional.
</p>

<p>
    Ketiga fakta ini membentuk satu benang merah: populasi UMKM besar, mayoritas tidak punya pembukuan,
    porsi kredit yang mereka terima justru menyusut, sementara regulator secara aktif mendorong pendekatan
    penilaian kelayakan berbasis data sebagai jalan keluarnya. <span class="bold">Lapak Naik</span> dibangun
    tepat di celah ini: sebuah platform operasional harian (kasir, stok, etalase online) yang, sebagai efek
    samping alami dari penggunaannya, menghasilkan <span class="bold">data transaksi tercatat</span> yang bisa
    diolah menjadi bahan pendukung penilaian kelayakan usaha &mdash; tanpa UMKM perlu belajar akuntansi terlebih
    dahulu.
</p>

<table class="data">
    <thead><tr><th>Sumber Data</th><th>Temuan</th><th>Rujukan</th></tr></thead>
    <tbody>
        <tr><td>SIDT-UMKM, Kementerian UMKM RI (2025)</td><td>30.209.069 unit usaha UMKM non-pertanian; hanya 3,51% tercatat punya laporan keuangan</td><td class="small">databoks.katadata.co.id; ukmindonesia.id</td></tr>
        <tr><td>OJK (2025&ndash;2026)</td><td>Porsi kredit UMKM/PDB turun 7,20% &rarr; 6,66%; kontraksi 0,56% yoy Feb 2026</td><td class="small">ojk.go.id; kompas.id</td></tr>
        <tr><td>POJK Nomor 19 Tahun 2025</td><td>Mewajibkan prinsip mudah-tepat-cepat-murah-inklusif; mendorong credit scoring berbasis data</td><td class="small">ojk.go.id</td></tr>
    </tbody>
</table>

{{-- ===================== 2. SOLUSI ===================== --}}
<h2>2. Deskripsi Solusi</h2>

<p>
    Lapak Naik adalah platform digitalisasi UMKM berbasis web yang menggabungkan tiga lapis solusi dalam satu
    sistem data yang sama:
</p>
<ol>
    <li><span class="bold">Operasional harian</span> &mdash; kasir (POS), manajemen produk &amp; stok, manajemen pelanggan dan pemasok.</li>
    <li><span class="bold">Kelayakan usaha</span> &mdash; dashboard analitik dan Skor Kelayakan Usaha yang dihitung otomatis dari histori transaksi asli, tanpa input manual tambahan dari pemilik usaha.</li>
    <li><span class="bold">Perluasan pasar</span> &mdash; etalase online per-UMKM yang bisa dibagikan sebagai tautan publik, lengkap dengan checkout dan pembayaran QRIS (Midtrans) sungguhan.</li>
</ol>

<p>
    Karena kasir toko fisik dan etalase online berbagi satu basis data produk &amp; stok yang sama, pemilik usaha
    tidak perlu mengelola dua sistem terpisah &mdash; setiap penjualan, baik di tempat maupun online, memperkaya
    data yang sama yang menjadi dasar Skor Kelayakan Usaha.
</p>

<h3>2.1 Kesesuaian dengan Subtema SDG 8</h3>
<p>
    Subtema A (Digital Innovation for Economic Challenges) menekankan penguatan UMKM, optimalisasi manajemen
    bisnis, serta perluasan akses pasar dan <span class="bold">inklusi finansial</span>. Lapak Naik menjawab
    ketiganya secara langsung: manajemen bisnis lewat modul kasir &amp; stok, akses pasar lewat etalase online,
    dan inklusi finansial lewat Skor Kelayakan Usaha yang selaras dengan arah kebijakan credit scoring
    alternatif pada POJK 19/2025.
</p>

<div class="disclaimer">
    <span class="bold">Catatan kejujuran:</span> Skor Kelayakan Usaha adalah bahan pendukung awal yang dihitung
    dari data transaksi internal aplikasi, <span class="bold">bukan skor kredit resmi</span> dan belum terhubung
    ke lembaga pembiayaan mana pun. Disclaimer ini juga ditampilkan langsung di antarmuka dashboard aplikasi.
</div>

<div style="page-break-after: always;"></div>

{{-- ===================== 3. TECH STACK ===================== --}}
<h2>3. Tech Stack</h2>

<table class="data">
    <thead><tr><th>Lapisan</th><th>Teknologi</th><th>Keterangan</th></tr></thead>
    <tbody>
        <tr><td>Backend</td><td class="mono">Laravel 13 (PHP 8.4)</td><td>Eloquent ORM, service layer, queue/scheduler bawaan</td></tr>
        <tr><td>Frontend</td><td class="mono">Inertia.js + Vue 3 + Tailwind CSS</td><td>SPA tanpa perlu REST API terpisah; dark mode penuh</td></tr>
        <tr><td>Basis Data</td><td class="mono">PostgreSQL 18</td><td>Transaksi ACID, row-locking, pencarian ILIKE</td></tr>
        <tr><td>Auth</td><td class="mono">Laravel Breeze + Sanctum</td><td>Role-based access (owner/admin/kasir)</td></tr>
        <tr><td>Pembayaran</td><td class="mono">Midtrans Snap (QRIS)</td><td>Integrasi nyata dengan webhook signature verification</td></tr>
        <tr><td>PDF</td><td class="mono">barryvdh/laravel-dompdf</td><td>Struk transaksi &amp; dokumentasi ini</td></tr>
        <tr><td>Testing</td><td class="mono">PHPUnit</td><td>45 automated test, mencakup alur kritis</td></tr>
        <tr><td>Deployment</td><td class="mono">Railway (App + PostgreSQL)</td><td>{{ $team['deploy_url'] ?? '[ISI URL DEPLOYMENT]' }}</td></tr>
    </tbody>
</table>

{{-- ===================== 4. ARSITEKTUR ===================== --}}
<h2>4. Arsitektur Sistem</h2>
<p>Alur permintaan dari klien hingga layanan eksternal:</p>

<table class="box-diagram" style="width:100%;">
    <tr>
        <td style="width:22%;">Browser<br>(Vue 3 via Inertia)</td>
        <td class="arrow" style="width:6%;">&rarr;</td>
        <td style="width:22%;">Controller +<br>Form Validation</td>
        <td class="arrow" style="width:6%;">&rarr;</td>
        <td style="width:22%;">Service Layer<br>(BusinessScoreService,<br>OrderFulfillmentService)</td>
        <td class="arrow" style="width:6%;">&rarr;</td>
        <td style="width:16%;">PostgreSQL 18</td>
    </tr>
</table>
<table class="box-diagram" style="width:100%; margin-top:6px;">
    <tr>
        <td style="width:30%;">Midtrans Snap API</td>
        <td class="arrow" style="width:8%;">&harr;</td>
        <td style="width:32%;">MidtransPaymentGateway<br>(implements PaymentGateway)</td>
        <td class="arrow" style="width:8%;">&rarr;</td>
        <td style="width:22%;">MidtransWebhookController<br>(signature-verified)</td>
    </tr>
</table>

<p style="margin-top:10px;">
    Logika bisnis sengaja dipisah dari controller ke dalam <span class="bold">service class</span>, dan
    integrasi pembayaran diakses lewat <span class="bold">interface <code>PaymentGateway</code></span> &mdash;
    bukan memanggil SDK Midtrans langsung dari controller. Tujuannya agar alur checkout dan webhook bisa diuji
    otomatis memakai implementasi tiruan (<code>FakePaymentGateway</code>), tanpa perlu memanggil API sungguhan
    setiap kali test dijalankan.
</p>

<h3>4.1 Pertimbangan Keamanan</h3>
<ul>
    <li><span class="bold">Row-locking</span> (<code>lockForUpdate</code>) saat checkout &mdash; mencegah dua transaksi bersamaan menjual stok terakhir yang sama.</li>
    <li><span class="bold">Verifikasi signature webhook</span> Midtrans (<code>sha512(order_id+status_code+gross_amount+server_key)</code>) &mdash; memastikan notifikasi status pembayaran benar berasal dari Midtrans, bukan dipalsukan pihak lain.</li>
    <li><span class="bold">Idempotensi webhook</span> &mdash; notifikasi yang diterima berulang (Midtrans melakukan retry) tidak memproses ulang transaksi yang sudah final.</li>
    <li><span class="bold">Isolasi antar-tenant</span> &mdash; setiap query di-scope ke <code>business_id</code> milik usaha yang login; diverifikasi lewat automated test.</li>
    <li><span class="bold">Rate limiting checkout</span> &mdash; 5 percobaan/menit per alamat IP dan 3 percobaan/menit per usaha, mencegah stok "disandera" lewat pemesanan spam tanpa pembayaran (desain sistem mereservasi stok sejak status pending, sehingga endpoint ini butuh perlindungan tersendiri).</li>
    <li><span class="bold">Validasi harga di server</span> &mdash; harga produk dihitung ulang dari data database saat checkout, tidak pernah dipercaya dari input klien.</li>
</ul>

<div style="page-break-after: always;"></div>

{{-- ===================== 5. ALUR KERJA ===================== --}}
<h2>5. Alur Kerja Aplikasi</h2>

<h3>5.1 Alur Transaksi Kasir (POS)</h3>
<div class="step-box"><span class="step-num">1</span> Kasir memilih produk, jumlah otomatis masuk keranjang dengan validasi stok tersedia di sisi klien untuk UX cepat.</div>
<div class="step-box"><span class="step-num">2</span> Kasir memilih metode pembayaran (tunai/QRIS/transfer) &mdash; label pencatatan, karena pembayaran tatap muka sudah selesai di lokasi.</div>
<div class="step-box"><span class="step-num">3</span> Saat submit, server mengunci baris produk terkait (<code>lockForUpdate</code>) dan menghitung ulang harga dari data asli &mdash; bukan dari input klien.</div>
<div class="step-box"><span class="step-num">4</span> Transaksi, item transaksi, catatan pergerakan stok, dan data pembayaran dibuat dalam satu database transaction (atomik).</div>
<div class="step-box"><span class="step-num">5</span> Struk ditampilkan; bisa dicetak langsung atau diunduh PDF.</div>

<h3>5.2 Alur Checkout Online + Pembayaran QRIS</h3>
<div class="step-box"><span class="step-num">1</span> Pelanggan (tanpa login) memilih produk di etalase publik, mengisi nama dan nomor WhatsApp, lalu checkout.</div>
<div class="step-box"><span class="step-num">2</span> Server membuat transaksi berstatus <code>pending</code> dan <span class="bold">langsung mengurangi stok</span> (stock reservation) &mdash; mencegah stok yang sama dijanjikan ke dua pembeli berbeda saat salah satunya masih proses bayar.</div>
<div class="step-box"><span class="step-num">3</span> Server meminta <code>snap_token</code> dari Midtrans; token ini membuka popup pembayaran QRIS di browser pelanggan.</div>
<div class="step-box"><span class="step-num">4</span> Pelanggan membayar. Midtrans mengonfirmasi status pembayaran lewat <span class="bold">webhook server-to-server</span> ke <code>/midtrans/callback</code> &mdash; bukan lewat browser pelanggan, karena browser tidak bisa dipercaya penuh untuk klaim "sudah bayar".</div>
<div class="step-box"><span class="step-num">5</span> Server memverifikasi signature webhook, lalu menandai transaksi <code>completed</code> (jika lunas) atau mengembalikan stok dan menandai <code>expired</code> (jika gagal/batal/kedaluwarsa).</div>
<div class="step-box"><span class="step-num">6</span> <span class="bold">Jaring pengaman:</span> tugas terjadwal berjalan tiap 15 menit untuk melepas kembali stok pesanan yang sudah <code>pending</code> lebih dari 60 menit, berjaga-jaga jika webhook Midtrans tidak pernah sampai.</div>

<div style="page-break-after: always;"></div>

{{-- ===================== 6. ERD ===================== --}}
<h2>6. Skema Basis Data (ERD)</h2>
<p>
    Satu baris <code>businesses</code> merepresentasikan satu UMKM. Seluruh entitas operasional menggantung ke
    usaha tersebut lewat <code>business_id</code>, sehingga data antar-usaha terisolasi secara struktural.
</p>

<table class="data">
    <thead><tr><th>Tabel</th><th>Kolom Kunci</th><th>Relasi</th></tr></thead>
    <tbody>
        <tr><td class="mono">businesses</td><td>owner_id, name, slug</td><td>1 usaha &harr; banyak users, products, transactions, dst.</td></tr>
        <tr><td class="mono">users</td><td>business_id, role (owner/admin/kasir)</td><td>Milik satu business</td></tr>
        <tr><td class="mono">categories</td><td>business_id, name</td><td>1 kategori &harr; banyak products</td></tr>
        <tr><td class="mono">products</td><td>business_id, category_id, price, stock, image_path</td><td>1 produk &harr; banyak stock_movements, transaction_items</td></tr>
        <tr><td class="mono">suppliers</td><td>business_id, name</td><td>Dikaitkan ke stock_movements (stok masuk)</td></tr>
        <tr><td class="mono">stock_movements</td><td>product_id, type (in/out/adjustment), quantity</td><td>Audit trail seluruh perubahan stok</td></tr>
        <tr><td class="mono">customers</td><td>business_id, phone, loyalty_points</td><td>Dicocokkan otomatis lewat nomor HP</td></tr>
        <tr><td class="mono">transactions</td><td>business_id, status, channel (pos/online), invoice_number</td><td>1 transaksi &harr; banyak transaction_items, 1 payment</td></tr>
        <tr><td class="mono">transaction_items</td><td>transaction_id, product_id, quantity, price</td><td>Rincian per produk dalam satu transaksi</td></tr>
        <tr><td class="mono">payments</td><td>transaction_id, method, status, gateway_reference</td><td>Status pembayaran per transaksi</td></tr>
        <tr><td class="mono">cash_flows</td><td>business_id, type (in/out), category, amount</td><td>Pencatatan kas di luar penjualan</td></tr>
        <tr><td class="mono">business_scores</td><td>business_id, period, score, revenue_growth</td><td>Histori Skor Kelayakan Usaha per periode</td></tr>
    </tbody>
</table>

{{-- ===================== 7. FITUR ===================== --}}
<h2>7. Fitur Unggulan</h2>
<table class="data">
    <thead><tr><th>Modul</th><th>Ringkasan</th></tr></thead>
    <tbody>
        <tr><td>Kasir (POS)</td><td>Transaksi cepat, cetak/unduh struk, refund dengan pengembalian stok otomatis (khusus owner/admin)</td></tr>
        <tr><td>Produk &amp; Stok</td><td>CRUD produk dengan foto, audit trail pergerakan stok</td></tr>
        <tr><td>Etalase Online</td><td>Katalog publik dengan pencarian &amp; filter kategori, checkout + pembayaran QRIS sungguhan</td></tr>
        <tr><td>Dashboard</td><td>Omzet, grafik 14 hari, produk terlaris, peringatan stok menipis</td></tr>
        <tr><td>Skor Kelayakan Usaha</td><td>0&ndash;100, dari pertumbuhan omzet, konsistensi harian, dan volume transaksi &mdash; dihitung dari data asli, bukan input manual</td></tr>
        <tr><td>Arus Kas</td><td>Pencatatan pemasukan/pengeluaran di luar penjualan untuk laporan untung-rugi yang lebih akurat</td></tr>
        <tr><td>Manajemen Karyawan</td><td>Owner mengelola akun Admin/Kasir dengan hak akses berbeda</td></tr>
        <tr><td>Onboarding</td><td>Checklist langkah awal (kategori &rarr; produk &rarr; transaksi pertama) yang otomatis hilang setelah selesai</td></tr>
    </tbody>
</table>

<div style="page-break-after: always;"></div>

{{-- ===================== 8. INSTALASI ===================== --}}
<h2>8. Panduan Instalasi &amp; Deployment Lokal</h2>

<h3>8.1 Kebutuhan Sistem</h3>
<ul>
    <li>PHP 8.4+ dengan ekstensi <code>pdo_pgsql</code></li>
    <li>Composer 2.x</li>
    <li>Node.js 20+ &amp; npm</li>
    <li>PostgreSQL 16+</li>
</ul>

<h3>8.2 Langkah Instalasi</h3>
<div class="codeblock">
    <div>git clone {{ $team['repo_url'] ?? '[URL_REPO_GITHUB]' }}</div>
    <div>cd lapak-naik</div>
    <div>composer install</div>
    <div>npm install</div>
    <div>cp .env.example .env</div>
    <div>php artisan key:generate</div>
    <div># Sesuaikan DB_* di .env dengan kredensial PostgreSQL lokal</div>
    <div>php artisan migrate --seed</div>
    <div>php artisan storage:link</div>
    <div>npm run build</div>
    <div>php artisan serve</div>
</div>

<h3>8.3 Kredensial Demo (dari seeder)</h3>
<table class="data">
    <thead><tr><th>Peran</th><th>Email</th><th>Password</th></tr></thead>
    <tbody>
        <tr><td>Owner</td><td class="mono">owner@lapaknaik.test</td><td class="mono">password</td></tr>
        <tr><td>Kasir</td><td class="mono">kasir@lapaknaik.test</td><td class="mono">password</td></tr>
    </tbody>
</table>
<p class="small muted">Seeder juga menghasilkan &plusmn;230 transaksi selama 60 hari untuk usaha demo "Warung Bu Sari", sehingga dashboard dan Skor Kelayakan Usaha langsung terisi data saat pertama kali dijalankan.</p>

<h3>8.4 Konfigurasi Tambahan (opsional untuk fitur pembayaran)</h3>
<p>Untuk mengaktifkan checkout QRIS, isi <code>.env</code>:</p>
<div class="codeblock">
    <div>MIDTRANS_SERVER_KEY=...</div>
    <div>MIDTRANS_CLIENT_KEY=...</div>
    <div>MIDTRANS_IS_PRODUCTION=false</div>
</div>

<h3>8.5 Menjalankan Test Otomatis</h3>
<div class="codeblock"><div>php artisan test</div></div>
<p class="small muted">45 test mencakup: alur checkout kasir &amp; online, validasi stok, isolasi antar-tenant, refund + pembatasan peran, webhook (settlement/expire/signature palsu/notifikasi ganda), rate limiting, dan perhitungan Skor Kelayakan Usaha.</p>

{{-- ===================== 9. BATASAN & ROADMAP ===================== --}}
<h2>9. Batasan Sistem &amp; Rencana Pengembangan</h2>
<p>Beberapa batasan disadari secara sadar sebagai keputusan lingkup, bukan kelalaian:</p>
<ul>
    <li><span class="bold">Satu akun = satu lokasi usaha</span> &mdash; dukungan multi-cabang belum diimplementasikan.</li>
    <li><span class="bold">Notifikasi WhatsApp</span> ke pelanggan belum aktif (memerlukan akun penyedia layanan pihak ketiga).</li>
    <li><span class="bold">Skor Kelayakan Usaha</span> adalah formula heuristik internal, belum divalidasi terhadap data historis lembaga pembiayaan sungguhan, dan belum terintegrasi ke lembaga keuangan mana pun.</li>
    <li><span class="bold">Belum ada validasi pengguna UMKM riil</span> di luar studi kasus demo &mdash; rencana lanjutan pasca-kompetisi.</li>
    <li>Pengujian di perangkat kasir fisik (tablet/HP) belum dilakukan secara langsung, baru penyesuaian tata letak responsif.</li>
</ul>

<h2>10. Penutup</h2>
<p>
    Lapak Naik dibangun dengan penekanan pada kerapian arsitektur (row-locking, service layer, webhook yang
    aman dan idempoten, automated testing) sekaligus fitur yang secara langsung menjawab kesenjangan pembukuan
    dan akses pembiayaan UMKM di Indonesia. Dokumentasi ini disusun dengan prinsip kejujuran teknis &mdash;
    batasan sistem dicantumkan secara eksplisit, bukan disembunyikan, sebagai bagian dari akuntabilitas tim
    terhadap dewan juri.
</p>

</body>
</html>
