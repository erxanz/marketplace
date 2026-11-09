# Marketplace - Aplikasi Web Sederhana (Soal 11)

Aplikasi marketplace sederhana dibuat dengan **PHP Native (tanpa framework)**, **MySQL via MySQLi**, dan **Tailwind CSS** (desain modern ala marketplace e-commerce, menggantikan Bootstrap 5).

Fitur utama:

- **Penjual (pemilik toko)** mengelola produk miliknya sendiri — tambah, ubah, hapus; tabel di desktop, daftar card di mobile.
- **Admin tidak bisa kelola produk** (dialihkan ke Kelola Toko); admin mengelola **toko**: daftar penjual + aktif/nonaktifkan toko. Toko nonaktif → seluruh produknya disembunyikan dari beranda.
- Pembeli melihat daftar dan detail produk.
- Pencarian produk & filter kategori langsung dari beranda.
- Registrasi akun baru (pembeli/penjual) melalui `register.php`.
- Pembeli menambahkan produk ke keranjang, mengubah jumlah, dan menghapus item.
- Pembeli melakukan checkout; pesanan disimpan ke database dan stok produk otomatis berkurang.
- Desain responsif (mobile/tablet/desktop) dengan **Tailwind CSS** — menggantikan Bootstrap 5.

---

## 1. Persyaratan

| Kebutuhan | Keterangan |
|---|---|
| XAMPP | Apache + MySQL (MariaDB) + PHP 8.x |
| PHP | Minimal 7.4, direkomendasikan 8.x (ext `mysqli`) |
| Node.js | **Opsional** — hanya untuk build ulang Tailwind CSS (`npm install` + `npm run build`) |
| Browser | Chrome / Firefox / Edge (tersambung internet untuk CDN Lucide & Google Fonts) |

---

## 2. Cara Instalasi (XAMPP)

1. Salin seluruh folder `marketplace/` ke dalam `C:/xampp/htdocs/` sehingga menjadi `C:/xampp/htdocs/marketplace/`.
2. Buka **XAMPP Control Panel** → klik **Start** pada **Apache** dan **MySQL**.
3. Buka browser → `http://localhost/phpmyadmin`.
4. Klik menu **Import** → pilih file `db_marketplace.sql` → klik **Go/Import**.
   - Database `marketplace_db` beserta 7 tabel, relasi, dan data awal akan terbentuk otomatis.
   - File ini bisa di-import ulang kapan saja karena diawali perintah `DROP TABLE IF EXISTS`.
5. Buka aplikasi: `http://localhost/marketplace/`

> Koneksi database ada di `config/koneksi.php` (host `localhost`, user `root`, password kosong — default XAMPP).

### Build Tailwind CSS (development)

Styling memakai Tailwind CSS yang dikompilasi ke `assets/css/output.css`.
`output.css` sudah ikut tersedia di repo, jadi **langkah ini hanya perlu saat mengubah style**.

```bash
npm install          # sekali saja: mengunduh tailwindcss (dev dependency)
npm run build        # kompilasi assets/css/src/input.css -> assets/css/output.css --minify
npm run dev          # mode watch (auto rebuild saat file PHP/CSS berubah)
```

Ketentuan:

- Konfigurasi ada di `tailwind.config.js`; folder `content` & `safelist` mendeteksi seluruh file PHP.
- Hasil build (`assets/css/output.css`) wajib ikut dideploy (mis. InfinityFree) — **jangan** pakai Tailwind Play CDN.
- Font Inter (Google Fonts) dimuat via `@import` di `input.css`; ikon memakai Lucide (CDN).

---

## 3. Akun Login

Data akun sudah tersedia di tabel `users` (password sudah di-hash dengan `password_hash()`).

| Role | Email | Password | Halaman setelah login |
|---|---|---|---|
| **Pembeli** | `siti@marketplace.test` | `pembeli123` | Beranda (`index.php`) |
| **Penjual** | `budi@marketplace.test` | `penjual123` | Kelola Produk (`admin/produk.php`) |
| **Penjual** | `andi@marketplace.test` | `penjual123` | Kelola Produk (`admin/produk.php`) |
| **Admin** | `admin@marketplace.test` | `admin123` | Kelola Toko (`admin/toko.php`) |

Halaman login: `http://localhost/marketplace/login.php`

Belum punya akun? Buka `http://localhost/marketplace/register.php` — daftar sebagai **pembeli** atau **penjual**.

---

## 4. Struktur Folder

```
marketplace/
├── config/
│   ├── koneksi.php        # Koneksi database MySQLi
│   └── fungsi.php         # Fungsi bantu (login, rupiah, upload, pesan)
├── components/            # Komponen tampilan yang dipakai bersama
│   ├── head.php           # <head> + buka <body>
│   ├── navbar.php         # Navbar depan (logo, search, keranjang, auth)
│   ├── footer.php         # Footer depan + memuat script global
│   ├── sidebar-admin.php  # Sidebar admin/penjual (responsif)
│   ├── topbar-admin.php   # Header atas halaman admin
│   └── scripts.php        # Lucide icons + app.js
├── admin/
│   ├── toko.php            # KELOLA TOKO (khusus admin): daftar toko + aktif/nonaktifkan
│   ├── produk.php         # Dashboard penjual: statistik + daftar produk (CRUD)
│   ├── tambah-produk.php  # Form tambah produk + upload gambar
│   ├── edit-produk.php    # Form ubah produk
│   └── hapus-produk.php   # Konfirmasi & hapus produk
├── assets/
│   ├── css/
│   │   ├── src/input.css  # Sumber Tailwind (diubah saat develop)
│   │   └── output.css     # Hasil build (dipakai halaman) - jangan diedit
│   ├── js/app.js          # Toggle menu, ikon Lucide, auto-dismiss pesan
│   └── images/            # Gambar produk & gambar default
├── proses/
│   ├── tambah-keranjang.php  # Proses POST masukkan produk ke keranjang
│   └── checkout.php          # Proses checkout (transaksi database)
├── index.php              # Beranda: hero, filter kategori, pencarian, produk
├── detail-produk.php      # Detail satu produk (layout 2 kolom)
├── keranjang.php          # Keranjang + order summary + checkout
├── login.php              # Halaman login
├── register.php           # Halaman daftar akun (baru)
├── logout.php             # Proses logout
├── db_marketplace.sql     # Struktur database + data awal
├── package.json           # Script build Tailwind (npm run build)
├── package-lock.json      # Versi dependency terkunci
├── tailwind.config.js     # Konfigurasi Tailwind (content + safelist)
├── .gitignore             # File yang diabaikan git (node_modules, upload runtime, log)
└── README.md
```

> Catatan: `login.php`, `logout.php`, `register.php`, `admin/toko.php`, `config/fungsi.php`, dan folder `components/` adalah file tambahan untuk autentikasi, register, kelola toko, dan komponen UI bersama; struktur utama sesuai soal tidak diubah. `assets/css/style.css` dan `assets/js/script.js` versi lama (Bootstrap) sudah digantikan oleh `input.css`/`output.css` dan `app.js`. Folder `node_modules/` tidak di-track git (lihat `.gitignore`).

---

## 5. Database (`marketplace_db`)

7 tabel dengan relasi Foreign Key, ENGINE InnoDB:

| No | Tabel | Fungsi |
|---|---|---|
| 1 | `users` | Admin, penjual, pembeli (role: `admin` / `penjual` / `pembeli`; kolom `status` = `aktif`/`nonaktif` untuk toko) |
| 2 | `kategori` | Kategori produk |
| 3 | `produk` | Produk → FK ke `users` (penjual) dan `kategori` |
| 4 | `keranjang` | Keranjang tiap user (`status`: `aktif` / `checkout`) |
| 5 | `detail_keranjang` | Isi keranjang → FK ke `keranjang` dan `produk`, unik per (keranjang, produk) |
| 6 | `pesanan` | Transaksi pesanan → FK ke `users` |
| 7 | `detail_pesanan` | Rincian pesanan (jumlah, harga saat pesan, subtotal) |

Aturan peran & toko:

- **Penjual** adalah pemilik toko — hanya bisa melihat/mengubah/menghapus produk miliknya sendiri.
- **Admin** tidak mengelola produk; admin mengelola **toko** (`admin/toko.php`): mengaktifkan/menonaktifkan penjual.
- Toko berstatus `nonaktif` → seluruh produknya tidak tampil di beranda dan halaman detail (baik lewat pencarian maupun filter).

Alur checkout:

1. Harga diambil **langsung dari tabel `produk`** (bukan dari input user).
2. Seluruh proses berjalan dalam **satu transaksi MySQLi** (`begin_transaction` → `commit` / `rollback`).
3. Data masuk `pesanan` + `detail_pesanan`.
4. Stok produk dikurangi (dengan syarat `stok >= jumlah`).
5. Keranjang menjadi `checkout` dan `detail_keranjang` dikosongkan.
6. Jika terjadi kegagalan di tengah jalan (misal stok habis), **semua perubahan dibatalkan** (rollback).

---

## 6. Cara Menggunakan

### A. Sebagai Pembeli
1. **Cari & filter**: ketik kata kunci pada search bar di navbar, atau klik chips **Kategori** di beranda untuk memfilter produk.
2. **Daftar/Login** melalui tombol di navbar (`register.php` / `login.php`).
3. Klik **Detail Produk** untuk melihat deskripsi, stok, dan penjual.
4. Klik **Tambah ke Keranjang** (wajib login — jika belum, otomatis diarahkan ke halaman login).
5. Buka **Keranjang**, ubah jumlah atau hapus item sesuai kebutuhan (ringkasan pesanan ada di sisi kanan).
6. Isi **Alamat Pengiriman** → klik **Checkout Sekarang**.
7. Halaman "Pesanan Berhasil" menampilkan nomor pesanan, rincian, dan total belanja.

### B. Sebagai Penjual (pemilik toko)
1. Login menggunakan akun penjual → langsung ke **Kelola Produk** (`admin/produk.php`).
   - Penjual hanya melihat/mengelola produk miliknya sendiri.
   - Membuka halaman admin lain (mis. Kelola Toko) akan ditolak.
2. **+ Tambah Produk** → isi nama, kategori, deskripsi, harga, stok, gambar (JPG/PNG/WEBP, maks 2 MB), status → **Simpan Produk**.
3. **Edit** → ubah data; kosongkan input gambar jika tidak ingin mengganti gambar.
4. **Hapus** → muncul halaman konfirmasi → **Ya, Hapus**.
   - Produk yang sudah tercatat di `detail_pesanan` / `detail_keranjang` **tidak bisa dihapus** (dilindungi Foreign Key).

### C. Sebagai Admin (kelola toko)
1. Login menggunakan akun admin → langsung ke **Kelola Toko** (`admin/toko.php`).
2. Melihat daftar semua toko (penjual) beserta jumlah produk, total stok, dan status.
3. **Nonaktifkan** toko → seluruh produk tokonya langsung tidak tampil di beranda & detail.
4. **Aktifkan** kembali → produk tokonya tampil kembali.
5. Admin **tidak bisa** membuka halaman kelola produk (dialihkan ke Kelola Toko).

---

## 7. Skenario Pengujian

Skenario berikut sudah diuji dan berjalan tanpa error PHP/MySQL:

1. Penjual login.
2. Penjual menambahkan produk (+ upload gambar).
3. Produk tersimpan ke database dan tampil di beranda.
4. Pembeli membuka halaman utama dan melihat daftar produk aktif (produk nonaktif tidak tampil).
5. Pembeli membuka detail produk.
6. Pembeli menambahkan produk ke keranjang (termasuk penambahan jumlah produk yang sama).
7. Pembeli melihat isi keranjang, mengubah jumlah, dan menghapus item.
8. Pembeli melakukan checkout.
9. Data pesanan masuk ke tabel `pesanan`.
10. Rincian masuk ke `detail_pesanan`.
11. Stok produk berkurang sesuai jumlah pesanan.
12. Pesanan gagal (stok tidak cukup) menyebabkan rollback — data tidak berubah sebagian.
13. Admin login → diarahkan ke Kelola Toko; membuka halaman kelola produk dialihkan (penjual tetap bisa).
14. Admin menonaktifkan toko → produk tokonya hilang dari beranda, diaktifkan lagi → tampil kembali.

Pemeriksaan tampilan responsif (viewport 375 px mobile & 1440 px desktop):

- Navbar depan tidak wrap/terpotong; menu mobile berfungsi.
- Beranda: hero, chips kategori, dan kartu produk menyesuaikan lebar layar.
- Detail produk & keranjang: layout bertumpuk rapi di mobile.
- Halaman admin: sidebar menutup otomatis di mobile (via tombol hamburger) dan tidak menimpa konten di desktop; tabel produk menjadi daftar card di mobile.

Pemeriksaan keamanan yang sudah diterapkan:

- Prepared statement MySQLi di semua query.
- `htmlspecialchars()` untuk seluruh output HTML.
- `password_hash()` / `password_verify()` untuk password.
- Validasi input & validasi upload gambar (mime + ukuran maksimal 2 MB).
- Session untuk autentikasi + pemeriksaan role (pembeli tidak bisa membuka halaman admin).
- Keranjang dan pesanan hanya bisa diakses oleh pemiliknya.
- Validasi stok sebelum checkout dan transaksi database (rollback saat gagal).
- Password database tidak ditampilkan di mana pun.

---

## 8. Troubleshooting

| Masalah | Solusi |
|---|---|
| "Koneksi database gagal" | Pastikan MySQL XAMPP sudah Start; cek `config/koneksi.php` |
| Gambar produk tidak tampil | Pastikan folder `assets/images/` bisa ditulis (writable) |
| Upload gambar gagal | Format harus JPG/PNG/WEBP, ukuran maksimal 2 MB |
| Tabel tidak ditemukan | Import ulang `db_marketplace.sql` lewat phpMyAdmin |
| Layout tidak rapi / class aneh | Jalankan ulang `npm run build` (class baru harus ikut ter-compile ke `output.css`) |
| `npm run build` gagal: module `tailwindcss` tidak ditemukan | Jalankan `npm install` sekali dulu (folder `node_modules` tidak di-track git) |
| File `node_modules` ke-commit tanpa sengaja | Sudah dicegah `.gitignore`; bila terlanjur: `git rm -r --cached node_modules` lalu commit |
