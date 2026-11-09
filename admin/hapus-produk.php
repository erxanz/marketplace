<?php
// ============================================================
// FILE: admin/hapus-produk.php
// Fungsi : Menghapus produk berdasarkan ?id=id_produk.
//          1. Menampilkan konfirmasi dulu sebelum menghapus.
//          2. Menolak hapus jika produk sudah dipakai di
//             tabel detail_pesanan / detail_keranjang (FK RESTRICT).
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role
wajib_login('../login.php');
wajib_role(['admin', 'penjual'], '../index.php');

$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ------------------------------------------------------------
// Ambil data produk (penjual hanya boleh hapus produknya)
// ------------------------------------------------------------
$sql = "SELECT id_produk, nama_produk, gambar FROM produk WHERE id_produk = ?";
if ($_SESSION['role'] !== 'admin') {
    $sql .= " AND id_penjual = ?";
}
$stmt = mysqli_prepare($koneksi, $sql);
if ($_SESSION['role'] !== 'admin') {
    mysqli_stmt_bind_param($stmt, "ii", $id_produk, $_SESSION['user_id']);
} else {
    mysqli_stmt_bind_param($stmt, "i", $id_produk);
}
mysqli_stmt_execute($stmt);
$hasil  = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($hasil);

if (!$produk) {
    header("Location: produk.php?pesan=bukan-milik");
    exit;
}

// ------------------------------------------------------------
// Bagian PROSES: dijalankan setelah user menekan "Ya, Hapus"
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {

    // Cek apakah produk sudah dipakai dalam transaksi / keranjang
    $sql = "SELECT
                (SELECT COUNT(*) FROM detail_pesanan WHERE id_produk = ?) +
                (SELECT COUNT(*) FROM detail_keranjang WHERE id_produk = ?) AS dipakai";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id_produk, $id_produk);
    mysqli_stmt_execute($stmt);
    $hasil_cek = mysqli_stmt_get_result($stmt);
    $baris     = mysqli_fetch_assoc($hasil_cek);

    if ($baris['dipakai'] > 0) {
        // Produk tidak dihapus karena masih dipakai relasi Foreign Key
        header("Location: produk.php?pesan=hapus-gagal");
        exit;
    }

    // Produk boleh dihapus: hapus gambar lama dulu (jika ada)
    $folder_upload = __DIR__ . '/../assets/images/';
    if ($produk['gambar'] && file_exists($folder_upload . $produk['gambar'])) {
        unlink($folder_upload . $produk['gambar']);
    }

    // Hapus data produk
    $hapus = mysqli_prepare($koneksi, "DELETE FROM produk WHERE id_produk = ?");
    mysqli_stmt_bind_param($hapus, "i", $id_produk);
    mysqli_stmt_execute($hapus);

    header("Location: produk.php?pesan=hapus-sukses");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hapus Produk - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ================= NAVBAR ================= -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">Marketplace</a>
        <div class="d-flex">
            <a href="produk.php" class="btn btn-outline-light btn-sm my-2">Kelola Produk</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <!-- Halaman konfirmasi sebelum produk dihapus -->
            <div class="card shadow-sm border-danger">
                <div class="card-body p-4 text-center">
                    <h5 class="text-danger">Konfirmasi Hapus Produk</h5>
                    <p class="mb-1">Anda yakin ingin menghapus produk:</p>
                    <p class="fw-bold fs-5">"<?= htmlspecialchars($produk['nama_produk']) ?>"</p>
                    <p class="text-muted small">Produk yang sudah tercatat dalam transaksi tidak dapat dihapus.</p>

                    <form method="post" action="hapus-produk.php?id=<?= $id_produk ?>" class="d-inline">
                        <input type="hidden" name="aksi" value="hapus">
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                        <a href="produk.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
