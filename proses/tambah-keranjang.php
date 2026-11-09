<?php
// ============================================================
// FILE: proses/tambah-keranjang.php
// Fungsi : Menerima POST dari halaman produk, lalu memasukkan
//          produk ke keranjang aktif user.
//          - Cek produk aktif & stok cukup
//          - Jika produk sudah ada di keranjang, jumlahnya ditambah
//          - Semua query memakai prepared statement
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// Wajib login (jika belum login, arahkan ke login)
wajib_login('../login.php');

$id_user = $_SESSION['user_id'];

// Ambil input dan pastikan berupa angka yang benar
$id_produk = isset($_POST['id_produk']) ? (int) $_POST['id_produk'] : 0;
$jumlah    = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 1;
if ($jumlah < 1) {
    $jumlah = 1;
}

// ------------------------------------------------------------
// Tentukan halaman tujuan setelah proses (dicek supaya aman,
// tidak boleh berupa URL lengkap dari luar aplikasi)
// index.php dibolehkan dengan query pencarian/kategori
// ------------------------------------------------------------
$redirect = $_POST['redirect'] ?? 'index.php';
if (!preg_match('#^(index\.php(\?[A-Za-z0-9=&%_.\-]*)?|keranjang\.php|detail-produk\.php\?id=\d+)$#', $redirect)) {
    $redirect = 'index.php';
}
// Karena file ini berada di folder proses/, path tujuan diberi awalan ../
$tujuan = '../' . $redirect . (strpos($redirect, '?') === false ? '?' : '&');

// ------------------------------------------------------------
// 1. Cek produk: harus ada dan berstatus aktif
// ------------------------------------------------------------
$sql = "SELECT id_produk, stok, status FROM produk WHERE id_produk = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($hasil);

if (!$produk || $produk['status'] !== 'aktif') {
    header("Location: " . $tujuan . "pesan=keranjang-gagal");
    exit;
}

// ------------------------------------------------------------
// 2. Cek stok: jumlah yang diminta tidak boleh melebihi stok
// ------------------------------------------------------------
if ($jumlah > $produk['stok']) {
    header("Location: " . $tujuan . "pesan=keranjang-stok");
    exit;
}

// ------------------------------------------------------------
// 3. Cari keranjang aktif milik user, buat jika belum ada
// ------------------------------------------------------------
$sql = "SELECT id_keranjang FROM keranjang WHERE id_user = ? AND status = 'aktif'";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$keranjang = mysqli_fetch_assoc($hasil);

if ($keranjang) {
    $id_keranjang = $keranjang['id_keranjang'];
} else {
    // Keranjang belum ada -> buat baru
    $sql = "INSERT INTO keranjang (id_user, status) VALUES (?, 'aktif')";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $id_keranjang = mysqli_insert_id($koneksi);
}

// ------------------------------------------------------------
// 4. Cek apakah produk sudah ada di keranjang
// ------------------------------------------------------------
$sql = "SELECT jumlah FROM detail_keranjang WHERE id_keranjang = ? AND id_produk = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_keranjang, $id_produk);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$ada = mysqli_fetch_assoc($hasil);

if ($ada) {
    // Sudah ada -> jumlahnya ditambah, tapi tetap dicek terhadap stok
    $jumlah_baru = $ada['jumlah'] + $jumlah;
    if ($jumlah_baru > $produk['stok']) {
        header("Location: " . $tujuan . "pesan=keranjang-stok");
        exit;
    }

    $sql = "UPDATE detail_keranjang SET jumlah = ? WHERE id_keranjang = ? AND id_produk = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $jumlah_baru, $id_keranjang, $id_produk);
    mysqli_stmt_execute($stmt);
} else {
    // Belum ada -> masukkan sebagai baris baru
    $sql = "INSERT INTO detail_keranjang (id_keranjang, id_produk, jumlah) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $id_keranjang, $id_produk, $jumlah);
    mysqli_stmt_execute($stmt);
}

// ------------------------------------------------------------
// 5. Kembali ke halaman semula dengan pesan sukses
// ------------------------------------------------------------
header("Location: " . $tujuan . "pesan=keranjang-sukses");
exit;
?>
