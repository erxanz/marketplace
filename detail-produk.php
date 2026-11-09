<?php
// ============================================================
// FILE: detail-produk.php
// Fungsi : Menampilkan detail satu produk berdasarkan ?id=...
//          Termasuk deskripsi, stok, kategori, dan info penjual.
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Ambil id produk dari URL (pakai (int) supaya aman dari injeksi)
$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil produk + kategori + penjual dengan prepared statement
$sql = "SELECT p.*, k.nama_kategori,
               u.nama AS nama_penjual, u.no_hp AS no_hp_penjual,
               u.alamat AS alamat_penjual
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN users u ON p.id_penjual = u.id_user
        WHERE p.id_produk = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$hasil   = mysqli_stmt_get_result($stmt);
$produk  = mysqli_fetch_assoc($hasil);

$jumlah_keranjang = jumlah_keranjang($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Produk - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ================= NAVBAR ================= -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Marketplace</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                <li class="nav-item">
                    <a class="nav-link" href="keranjang.php">
                        Keranjang
                        <?php if ($jumlah_keranjang > 0): ?>
                            <span class="badge bg-warning text-dark"><?= $jumlah_keranjang ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'penjual'])): ?>
                    <li class="nav-item"><a class="nav-link" href="admin/produk.php">Kelola Produk</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><span class="nav-link text-white-50">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">

    <?= tampilkan_pesan() ?>

    <?php if (!$produk): ?>
        <!-- Pesan jika produk tidak ditemukan -->
        <div class="alert alert-warning">
            Produk tidak ditemukan. Mungkin sudah dihapus atau id salah.
        </div>
        <a href="index.php" class="btn btn-primary">&larr; Kembali ke Beranda</a>

    <?php else:
        $gambar = $produk['gambar'] ? $produk['gambar'] : 'default.png';
    ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($produk['nama_produk']) ?></li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- GAMBAR PRODUK -->
                    <div class="col-md-5">
                        <img src="assets/images/<?= htmlspecialchars($gambar) ?>"
                             class="img-fluid rounded border" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
                    </div>

                    <!-- INFORMASI PRODUK -->
                    <div class="col-md-7">
                        <span class="badge bg-secondary"><?= htmlspecialchars($produk['nama_kategori']) ?></span>
                        <h3 class="mt-2"><?= htmlspecialchars($produk['nama_produk']) ?></h3>
                        <div class="fs-3 fw-bold text-primary mb-3"><?= format_rupiah($produk['harga']) ?></div>

                        <table class="table table-sm w-auto">
                            <tr>
                                <th class="pe-3">Stok</th>
                                <td>
                                    <?php if ($produk['stok'] > 0): ?>
                                        <span class="text-success"><?= $produk['stok'] ?> tersedia</span>
                                    <?php else: ?>
                                        <span class="text-danger">Habis</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="pe-3">Status</th>
                                <td><?= htmlspecialchars(ucfirst($produk['status'])) ?></td>
                            </tr>
                            <tr>
                                <th class="pe-3">Penjual</th>
                                <td><?= htmlspecialchars($produk['nama_penjual']) ?></td>
                            </tr>
                            <tr>
                                <th class="pe-3">No. HP</th>
                                <td><?= htmlspecialchars($produk['no_hp_penjual'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <th class="pe-3">Alamat</th>
                                <td><?= htmlspecialchars($produk['alamat_penjual'] ?: '-') ?></td>
                            </tr>
                        </table>

                        <h6 class="mt-3">Deskripsi</h6>
                        <p><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></p>

                        <!-- Tombol tambah ke keranjang -->
                        <?php if ($produk['status'] === 'aktif' && $produk['stok'] > 0): ?>
                            <form method="post" action="proses/tambah-keranjang.php" class="row g-2 align-items-end">
                                <input type="hidden" name="id_produk" value="<?= $produk['id_produk'] ?>">
                                <input type="hidden" name="redirect" value="detail-produk.php?id=<?= $produk['id_produk'] ?>">
                                <div class="col-auto">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" name="jumlah" class="form-control"
                                           value="1" min="1" max="<?= $produk['stok'] ?>" style="width:110px" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">Tambah ke Keranjang</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-secondary mt-3 mb-0">
                                Produk ini tidak dapat dibeli saat ini.
                            </div>
                        <?php endif; ?>

                        <a href="index.php" class="btn btn-outline-secondary mt-3">&larr; Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer class="bg-dark text-white-50 py-3 mt-4">
    <div class="container small text-center">
        &copy; <?= date('Y') ?> Marketplace - Tugas Praktikum Web (Soal 11)
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
