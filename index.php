<?php
// ============================================================
// FILE: index.php
// Fungsi : Halaman utama. Menampilkan semua produk berstatus
//          aktif beserta gambar, harga, kategori, dan tombol
//          Detail + Tambah ke Keranjang.
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Ambil data produk aktif dengan JOIN ke kategori dan users (penjual)
$sql = "SELECT p.id_produk, p.nama_produk, p.harga, p.gambar,
               k.nama_kategori, u.nama AS nama_penjual
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN users u ON p.id_penjual = u.id_user
        WHERE p.status = 'aktif'
        ORDER BY p.id_produk DESC";
$result = mysqli_query($koneksi, $sql);

// Jumlah isi keranjang untuk badge di navbar
$jumlah_keranjang = jumlah_keranjang($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplace - Beranda</title>
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
                <li class="nav-item"><a class="nav-link active" href="index.php">Beranda</a></li>
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

<!-- ================= ISI HALAMAN ================= -->
<div class="container py-4">
    <h4 class="mb-3">Daftar Produk</h4>

    <?= tampilkan_pesan() ?>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="alert alert-info">Belum ada produk yang tersedia.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
            <?php while ($p = mysqli_fetch_assoc($result)):
                // Jika gambar kosong, tampilkan gambar default
                $gambar = $p['gambar'] ? $p['gambar'] : 'default.png';
            ?>
                <div class="col">
                    <div class="card h-100 produk-card">
                        <img src="assets/images/<?= htmlspecialchars($gambar) ?>"
                             class="card-img-top produk-gambar" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary align-self-start mb-2">
                                <?= htmlspecialchars($p['nama_kategori']) ?>
                            </span>
                            <h6 class="card-title"><?= htmlspecialchars($p['nama_produk']) ?></h6>
                            <p class="text-muted small mb-1">Penjual: <?= htmlspecialchars($p['nama_penjual']) ?></p>
                            <div class="harga fw-bold text-primary mb-2"><?= format_rupiah($p['harga']) ?></div>

                            <div class="mt-auto d-grid gap-2">
                                <a href="detail-produk.php?id=<?= $p['id_produk'] ?>" class="btn btn-outline-primary btn-sm">
                                    Detail Produk
                                </a>
                                <!-- Form tambah ke keranjang (POST ke proses/tambah-keranjang.php) -->
                                <form method="post" action="proses/tambah-keranjang.php">
                                    <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                                    <input type="hidden" name="jumlah" value="1">
                                    <input type="hidden" name="redirect" value="index.php">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Tambah ke Keranjang</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
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
