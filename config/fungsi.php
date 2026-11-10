<?php
// ============================================================
// FILE: config/fungsi.php
// Fungsi : Kumpulan fungsi bantuan untuk login, keranjang,
//          format rupiah, dan upload gambar.
//          Dipanggil dengan require_once setelah koneksi.php
// ============================================================

// Cegah akses langsung ke file ini lewat browser
if (basename($_SERVER['SCRIPT_NAME']) == 'fungsi.php') {
    die('File ini tidak boleh dibuka langsung.');
}

// ------------------------------------------------------------
// Cek login: jika user belum login, arahkan ke halaman login
// $lokasi_login = path menuju login.php ('login.php' atau '../login.php')
// ------------------------------------------------------------
function wajib_login($lokasi_login)
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . $lokasi_login . "?pesan=harus-login");
        exit;
    }
}

// ------------------------------------------------------------
// Cek role: hanya role tertentu yang boleh masuk
// $daftar_role      = contoh: ['admin', 'penjual']
// $lokasi_beranda   = path halaman utama jika akses ditolak
// ------------------------------------------------------------
function wajib_role($daftar_role, $lokasi_beranda)
{
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $daftar_role)) {
        header("Location: " . $lokasi_beranda . "?pesan=role-tolak");
        exit;
    }
}

// ------------------------------------------------------------
// Menghitung total jumlah produk di keranjang aktif user
// Dipakai untuk badge jumlah di navbar
// ------------------------------------------------------------
function jumlah_keranjang($koneksi)
{
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }

    $id_user = $_SESSION['user_id'];
    $sql = "SELECT COALESCE(SUM(d.jumlah), 0) AS total
            FROM keranjang k
            JOIN detail_keranjang d ON k.id_keranjang = d.id_keranjang
            WHERE k.id_user = ? AND k.status = 'aktif'";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $baris = mysqli_fetch_assoc($hasil);

    return (int) $baris['total'];
}

// ------------------------------------------------------------
// Format angka menjadi Rupiah: 7500000 -> Rp 7.500.000
// ------------------------------------------------------------
function format_rupiah($angka)
{
    return "Rp " . number_format((float) $angka, 0, ",", ".");
}

// ------------------------------------------------------------
// Menampilkan pesan (sukses/gagal) berdasarkan ?pesan=...
// Dipanggil di dalam halaman: tampilkan_pesan()
// ------------------------------------------------------------
function tampilkan_pesan()
{
    if (!isset($_GET['pesan'])) {
        return "";
    }

    $daftar_pesan = [
        'harus-login'      => ['warning', 'Silakan login terlebih dahulu.'],
        'role-tolak'       => ['danger', 'Anda tidak memiliki akses ke halaman tersebut.'],
        'login-salah'      => ['danger', 'Email atau password salah.'],
        'logout'           => ['success', 'Anda berhasil logout.'],
        'tambah-sukses'    => ['success', 'Produk baru berhasil disimpan.'],
        'ubah-sukses'      => ['success', 'Data produk berhasil diperbarui.'],
        'hapus-sukses'     => ['success', 'Produk berhasil dihapus.'],
        'hapus-gagal'      => ['danger', 'Produk tidak dapat dihapus karena sudah dipakai dalam transaksi.'],
        'bukan-milik'      => ['danger', 'Data tidak dapat diproses.'],
        'data-tidak-ada'   => ['danger', 'Data tidak ditemukan.'],
        'toko-diubah'      => ['success', 'Status toko berhasil diperbarui.'],
        'toko-invalid'     => ['danger', 'Toko tidak ditemukan.'],
        'keranjang-sukses' => ['success', 'Produk berhasil ditambahkan ke keranjang.'],
        'keranjang-stok'   => ['danger', 'Jumlah produk melebihi stok yang tersedia.'],
        'keranjang-gagal'  => ['danger', 'Produk tidak dapat ditambahkan (produk tidak aktif atau tidak ditemukan).'],
        'jumlah-invalid'   => ['danger', 'Jumlah produk tidak valid.'],
        'jumlah-ubah'      => ['success', 'Jumlah produk berhasil diperbarui.'],
        'keranjang-hapus'  => ['success', 'Produk dihapus dari keranjang.'],
        'keranjang-kosong' => ['warning', 'Keranjang masih kosong.'],
        'stok-kurang'      => ['danger', 'Checkout gagal: stok produk tidak mencukupi.'],
        'alamat-kosong'    => ['danger', 'Alamat pengiriman wajib diisi.'],
        'metode-invalid'   => ['danger', 'Metode pembayaran wajib dipilih.'],
        'status-ubah'      => ['success', 'Status pesanan berhasil diperbarui.'],
        'status-gagal'     => ['danger', 'Status pesanan tidak dapat diperbarui.'],
        'user-diubah'      => ['success', 'Data pengguna berhasil diperbarui.'],
        'user-gagal'       => ['danger', 'Perubahan gagal: email sudah dipakai pengguna lain atau password kurang dari 6 karakter.'],
        'user-hapus'       => ['success', 'Pengguna berhasil dihapus.'],
        'user-digunakan'   => ['danger', 'Pengguna tidak bisa dihapus karena masih memiliki produk, pesanan, atau keranjang.'],
        'user-invalid'     => ['danger', 'Pengguna tidak ditemukan.'],
        'user-status'      => ['success', 'Status pengguna berhasil diperbarui.'],
        'user-sendiri'     => ['danger', 'Anda tidak bisa menonaktifkan atau menghapus akun sendiri.'],
        'checkout-gagal'   => ['danger', 'Checkout gagal, transaksi dibatalkan. Silakan coba lagi.'],
        'upload-gagal'     => ['danger', 'Gagal mengunggah gambar. Pastikan format JPG/PNG/WEBP dan ukuran maksimal 2 MB.'],
        'input-kurang'     => ['danger', 'Semua field wajib diisi dengan benar.'],
        'register-sukses'  => ['success', 'Pendaftaran berhasil. Silakan login menggunakan akun baru Anda.'],
        'password-cocok'   => ['danger', 'Konfirmasi password tidak sama.'],
        'password-pendek'  => ['danger', 'Password minimal 6 karakter.'],
        'email-sipakai'    => ['danger', 'Email sudah terdaftar, silakan gunakan email lain.'],
    ];

    if (!isset($daftar_pesan[$_GET['pesan']])) {
        return "";
    }

    $tipe = $daftar_pesan[$_GET['pesan']][0];
    $teks = $daftar_pesan[$_GET['pesan']][1];

    // Ikon Lucide sesuai jenis pesan
    $ikon = [
        'success' => 'circle-check',
        'danger'  => 'circle-alert',
        'warning' => 'triangle-alert',
        'info'    => 'info',
    ];

    // Alert gaya Tailwind (dibuat dari komponen .alert-* di input.css)
    return '<div class="alert-' . $tipe . ' alert-dismissible relative" role="alert">'
        . '<i data-lucide="' . $ikon[$tipe] . '" class="mt-0.5 h-4 w-4 shrink-0"></i>'
        . '<span class="flex-1 pr-6">' . htmlspecialchars($teks) . '</span>'
        . '<button type="button" data-dismiss-alert aria-label="Tutup pesan"'
        . ' class="absolute right-2 top-2 rounded-lg p-1 opacity-60 transition hover:opacity-100">'
        . '<i data-lucide="x" class="h-4 w-4"></i></button>'
        . '</div>';
}

// ------------------------------------------------------------
// Badge status pesanan (dipakai riwayat & kelola pesanan)
// ------------------------------------------------------------
function badge_status_pesanan($status)
{
    $peta = [
        'menunggu'   => ['badge-warning', 'Menunggu', 'bg-amber-500'],
        'diproses'   => ['badge-info', 'Diproses', 'bg-sky-500'],
        'dikirim'    => ['badge-indigo', 'Dikirim', 'bg-indigo-500'],
        'selesai'    => ['badge-success', 'Selesai', 'bg-primary-500'],
        'dibatalkan' => ['badge-danger', 'Dibatalkan', 'bg-rose-500'],
    ];
    $info = $peta[$status] ?? ['badge-slate', ucfirst($status), 'bg-slate-500'];
    return '<span class="' . $info[0] . '"><span class="h-1.5 w-1.5 rounded-full ' . $info[2] . '"></span> ' . $info[1] . '</span>';
}

// ------------------------------------------------------------
// Label metode pembayaran (transfer / ewallet / cod)
// ------------------------------------------------------------
function label_metode_pembayaran($metode)
{
    $peta = [
        'transfer' => 'Transfer Bank',
        'ewallet'  => 'E-Wallet',
        'cod'      => 'COD (Bayar di Tempat)',
    ];
    return $peta[$metode] ?? '-';
}

// ------------------------------------------------------------
// Upload gambar produk ke folder assets/images/
// Mengembalikan array: ['sukses' => true, 'nama' => 'file.png']
//                      ['sukses' => false, 'pesan' => 'pesan error']
// ------------------------------------------------------------
function simpan_gambar($file, $folder_tujuan)
{
    // 1. Cek apakah file benar-benar diupload
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['sukses' => false, 'pesan' => 'Gambar produk wajib dipilih.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['sukses' => false, 'pesan' => 'Terjadi kesalahan saat upload gambar.'];
    }

    // 2. Batasi ukuran gambar maksimal 2 MB
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['sukses' => false, 'pesan' => 'Ukuran gambar maksimal 2 MB.'];
    }

    // 3. Hanya izinkan JPG, PNG, dan WEBP (dicek dari isi file, bukan nama file)
    $mime_diizinkan = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($mime_diizinkan[$mime])) {
        return ['sukses' => false, 'pesan' => 'Format gambar harus JPG, PNG, atau WEBP.'];
    }

    // 4. Buat nama file baru supaya tidak bentrok
    $nama_baru = "produk-" . uniqid() . "." . $mime_diizinkan[$mime];

    // 5. Pindahkan file dari folder sementara ke assets/images/
    if (!move_uploaded_file($file['tmp_name'], $folder_tujuan . $nama_baru)) {
        return ['sukses' => false, 'pesan' => 'Gagal menyimpan gambar ke folder assets/images.'];
    }

    return ['sukses' => true, 'nama' => $nama_baru];
}
?>
