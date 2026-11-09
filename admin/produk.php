<?php
// ============================================================
// FILE: admin/produk.php
// Fungsi : Menampilkan daftar semua produk (CRUD produk).
//          Hanya admin dan penjual yang boleh membuka halaman ini.
//          Penjual hanya melihat produk miliknya sendiri.
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role
wajib_login('../login.php');
wajib_role(['admin', 'penjual'], '../index.php');

// Ambil semua produk + nama kategori
// Admin melihat semua produk, penjual hanya produknya sendiri
if ($_SESSION['role'] === 'admin') {
    $sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
            FROM produk p
            JOIN kategori k ON p.id_kategori = k.id_kategori
            JOIN users u ON p.id_penjual = u.id_user
            ORDER BY p.id_produk DESC";
    $stmt = mysqli_prepare($koneksi, $sql);
} else {
    $sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
            FROM produk p
            JOIN kategori k ON p.id_kategori = k.id_kategori
            JOIN users u ON p.id_penjual = u.id_user
            WHERE p.id_penjual = ?
            ORDER BY p.id_produk DESC";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
}
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Produk - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ================= NAVBAR ================= -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">Marketplace</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="../index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="../keranjang.php">Keranjang</a></li>
                <li class="nav-item"><a class="nav-link active" href="produk.php">Kelola Produk</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><span class="nav-link text-white-50">Halo, <?= htmlspecialchars($_SESSION['nama']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</span></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">Kelola Produk</h4>
        <a href="tambah-produk.php" class="btn btn-success">+ Tambah Produk</a>
    </div>

    <?= tampilkan_pesan() ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($hasil) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada produk.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($p = mysqli_fetch_assoc($hasil)):
                            $gambar = $p['gambar'] ? $p['gambar'] : 'default.png';
                        ?>
                            <tr>
                                <td>
                                    <img src="../assets/images/<?= htmlspecialchars($gambar) ?>"
                                         class="table-gambar" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                                </td>
                                <td>
                                    <?= htmlspecialchars($p['nama_produk']) ?><br>
                                    <small class="text-muted">Penjual: <?= htmlspecialchars($p['nama_penjual']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($p['nama_kategori']) ?></td>
                                <td><?= format_rupiah($p['harga']) ?></td>
                                <td><?= $p['stok'] ?></td>
                                <td>
                                    <?php if ($p['status'] === 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="edit-produk.php?id=<?= $p['id_produk'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="hapus-produk.php?id=<?= $p['id_produk'] ?>" class="btn btn-sm btn-danger">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
