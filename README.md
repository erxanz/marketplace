# Marketplace - Aplikasi Web Sederhana (Soal 11)

Aplikasi marketplace sederhana dibuat dengan **PHP Native (tanpa framework)**, **MySQL via MySQLi**, dan **Bootstrap 5**. Dibuat untuk tugas praktikum mata kuliah Web.

Fitur utama:

- Penjual/admin mengelola produk (tambah, ubah, hapus).
- Pembeli melihat daftar dan detail produk.
- Pembeli menambahkan produk ke keranjang, mengubah jumlah, dan menghapus item.
- Pembeli melakukan checkout; pesanan disimpan ke database dan stok produk otomatis berkurang.

---

## 1. Persyaratan

| Kebutuhan | Keterangan |
|---|---|
| XAMPP | Apache + MySQL (MariaDB) + PHP 8.x |
| PHP | Minimal 7.4, direkomendasikan 8.x (ext `mysqli`) |
| Browser | Chrome / Firefox / Edge (tersambung internet untuk CDN Bootstrap) |

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

---

## 3. Akun Login

Data akun sudah tersedia di tabel `users` (password sudah di-hash dengan `password_hash()`).

| Role | Email | Password | Halaman setelah login |
|---|---|---|---|
| **Pembeli** | `siti@marketplace.test` | `pembeli123` | Beranda (`index.php`) |
| **Penjual** | `budi@marketplace.test` | `penjual123` | Kelola Produk (`admin/produk.php`) |
| **Penjual** | `andi@marketplace.test` | `penjual123` | Kelola Produk (`admin/produk.php`) |
| **Admin** | `admin@marketplace.test` | `admin123` | Kelola Produk (`admin/produk.php`) |

Halaman login: `http://localhost/marketplace/login.php`

---

## 4. Struktur Folder

```
marketplace/
├── config/
│   ├── koneksi.php        # Koneksi database MySQLi
│   └── fungsi.php         # Fungsi bantu (login, rupiah, upload, pesan)
├── admin/
│   ├── produk.php         # Daftar produk (CRUD)
│   ├── tambah-produk.php  # Form tambah produk + upload gambar
│   ├── edit-produk.php    # Form ubah produk
│   └── hapus-produk.php   # Konfirmasi & hapus produk
├── assets/
│   ├── css/style.css      # Gaya tambahan
│   ├── js/script.js       # Script kecil (auto-dismiss pesan)
│   └── images/            # Gambar produk & gambar default
├── proses/
│   ├── tambah-keranjang.php  # Proses POST masukkan produk ke keranjang
│   └── checkout.php          # Proses checkout (transaksi database)
├── index.php              # Beranda: daftar produk aktif
├── detail-produk.php      # Detail satu produk
├── keranjang.php          # Isi keranjang + form checkout
├── login.php              # Halaman login
├── logout.php             # Proses logout
└── db_marketplace.sql     # Struktur database + data awal
```

> Catatan: `login.php`, `logout.php`, dan `config/fungsi.php` adalah file tambahan yang dibutuhkan untuk sistem autentikasi dan bersama fungsi bantu; struktur utama sesuai soal tidak diubah.

---

## 5. Database (`marketplace_db`)

7 tabel dengan relasi Foreign Key, ENGINE InnoDB:

| No | Tabel | Fungsi |
|---|---|---|
| 1 | `users` | Admin, penjual, pembeli (role: `admin` / `penjual` / `pembeli`) |
| 2 | `kategori` | Kategori produk |
| 3 | `produk` | Produk → FK ke `users` (penjual) dan `kategori` |
| 4 | `keranjang` | Keranjang tiap user (`status`: `aktif` / `checkout`) |
| 5 | `detail_keranjang` | Isi keranjang → FK ke `keranjang` dan `produk`, unik per (keranjang, produk) |
| 6 | `pesanan` | Transaksi pesanan → FK ke `users` |
| 7 | `detail_pesanan` | Rincian pesanan (jumlah, harga saat pesan, subtotal) |

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
1. Login menggunakan akun pembeli (atau buka beranda langsung untuk melihat produk).
2. Klik **Detail Produk** untuk melihat deskripsi, stok, dan penjual.
3. Klik **Tambah ke Keranjang** (wajib login — jika belum, otomatis diarahkan ke halaman login).
4. Buka **Keranjang**, ubah jumlah atau hapus item sesuai kebutuhan.
5. Isi **Alamat Pengiriman** → klik **Checkout Sekarang**.
6. Halaman "Pesanan Berhasil" menampilkan nomor pesanan, rincian, dan total belanja.

### B. Sebagai Penjual / Admin
1. Login menggunakan akun penjual atau admin.
2. Menu **Kelola Produk** → daftar produk tampil dalam tabel.
   - Penjual hanya melihat/mengelola produk miliknya sendiri.
   - Admin melihat semua produk.
3. **+ Tambah Produk** → isi nama, kategori, deskripsi, harga, stok, gambar (JPG/PNG/WEBP, maks 2 MB), status → **Simpan Produk**.
4. **Edit** → ubah data; kosongkan input gambar jika tidak ingin mengganti gambar.
5. **Hapus** → muncul halaman konfirmasi → **Ya, Hapus**.
   - Produk yang sudah tercatat di `detail_pesanan` / `detail_keranjang` **tidak bisa dihapus** (dilindungi Foreign Key).

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
| Layout tidak rapi (tanpa Bootstrap) | Pastikan perangkat tersambung internet (Bootstrap diambil dari CDN) |
