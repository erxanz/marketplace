<?php
// ============================================================
// FILE: keranjang.php
// Fungsi : Menampilkan isi keranjang belanja pembeli.
//          Bisa mengubah jumlah, menghapus item,
//          dan menyediakan form checkout (alamat pengiriman).
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Hanya user login yang boleh membuka keranjang
wajib_login('login.php');

$id_user = $_SESSION['user_id'];

// ------------------------------------------------------------
// 1. PROSES ubah jumlah / hapus item (metode POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi       = $_POST['aksi'] ?? '';
    $id_detail  = isset($_POST['id_detail_keranjang']) ? (int) $_POST['id_detail_keranjang'] : 0;

    // Pastikan baris keranjang benar milik user yang login
    // (mencegah user lain mengubah keranjang orang lain)
    $sql = "SELECT d.id_detail_keranjang, d.jumlah, p.stok
            FROM detail_keranjang d
            JOIN keranjang k ON d.id_keranjang = k.id_keranjang
            JOIN produk p ON d.id_produk = p.id_produk
            WHERE d.id_detail_keranjang = ?
              AND k.id_user = ?
              AND k.status = 'aktif'";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id_detail, $id_user);
    mysqli_stmt_execute($stmt);
    $hasil_item = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($hasil_item);

    if (!$item) {
        header("Location: keranjang.php?pesan=bukan-milik");
        exit;
    }

    if ($aksi === 'ubah_jumlah') {
        $jumlah = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 0;

        // Validasi jumlah dan stok
        if ($jumlah < 1) {
            header("Location: keranjang.php?pesan=jumlah-invalid");
            exit;
        }
        if ($jumlah > $item['stok']) {
            header("Location: keranjang.php?pesan=keranjang-stok");
            exit;
        }

        $sql = "UPDATE detail_keranjang SET jumlah = ? WHERE id_detail_keranjang = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $jumlah, $id_detail);
        mysqli_stmt_execute($stmt);

        header("Location: keranjang.php?pesan=jumlah-ubah");
        exit;
    }

    if ($aksi === 'hapus') {
        $sql = "DELETE FROM detail_keranjang WHERE id_detail_keranjang = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_detail);
        mysqli_stmt_execute($stmt);

        header("Location: keranjang.php?pesan=keranjang-hapus");
        exit;
    }

    header("Location: keranjang.php");
    exit;
}

// ------------------------------------------------------------
// 2. AMBIL ISI KERANJANG AKTIF user untuk ditampilkan
// ------------------------------------------------------------
$sql = "SELECT d.id_detail_keranjang, d.jumlah,
               p.id_produk, p.nama_produk, p.harga, p.gambar, p.stok
        FROM keranjang k
        JOIN detail_keranjang d ON k.id_keranjang = d.id_keranjang
        JOIN produk p ON d.id_produk = p.id_produk
        WHERE k.id_user = ? AND k.status = 'aktif'
        ORDER BY d.id_detail_keranjang";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil_keranjang = mysqli_stmt_get_result($stmt);

$daftar_item = [];
$total = 0;
while ($baris = mysqli_fetch_assoc($hasil_keranjang)) {
    $daftar_item[] = $baris;
    $total += $baris['jumlah'] * $baris['harga'];
}

// Ambil alamat user untuk diisikan otomatis pada form checkout
$stmt = mysqli_prepare($koneksi, "SELECT alamat FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($hasil_user);

$jumlah_keranjang = jumlah_keranjang($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang Belanja - Marketplace</title>
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
                    <a class="nav-link active" href="keranjang.php">
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
                <li class="nav-item"><span class="nav-link text-white-50">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h4 class="mb-3">Keranjang Belanja</h4>

    <?= tampilkan_pesan() ?>

    <?php if (empty($daftar_item)): ?>
        <!-- Keranjang kosong -->
        <div class="alert alert-info">
            Keranjang Anda masih kosong.
            <a href="index.php">Belanja sekarang</a>
        </div>

    <?php else: ?>
        <div class="row g-4">
            <!-- ================= DAFTAR ITEM ================= -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftar_item as $item):
                                    $gambar = $item['gambar'] ? $item['gambar'] : 'default.png';
                                    $subtotal = $item['jumlah'] * $item['harga'];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="assets/images/<?= htmlspecialchars($gambar) ?>"
                                                     class="table-gambar" alt="<?= htmlspecialchars($item['nama_produk']) ?>">
                                                <div>
                                                    <?= htmlspecialchars($item['nama_produk']) ?>
                                                    <?php if ($item['jumlah'] > $item['stok']): ?>
                                                        <br><small class="text-danger">Stok tidak mencukupi (sisa <?= $item['stok'] ?>)</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= format_rupiah($item['harga']) ?></td>
                                        <td>
                                            <!-- Form ubah jumlah -->
                                            <form method="post" action="keranjang.php" class="d-flex gap-1">
                                                <input type="hidden" name="aksi" value="ubah_jumlah">
                                                <input type="hidden" name="id_detail_keranjang" value="<?= $item['id_detail_keranjang'] ?>">
                                                <input type="number" name="jumlah" class="form-control form-control-sm input-jumlah"
                                                       value="<?= $item['jumlah'] ?>" min="1" max="<?= $item['stok'] ?>" required>
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Ubah jumlah">OK</button>
                                            </form>
                                        </td>
                                        <td class="fw-semibold"><?= format_rupiah($subtotal) ?></td>
                                        <td class="text-center">
                                            <!-- Tombol hapus item -->
                                            <form method="post" action="keranjang.php"
                                                  onsubmit="return confirm('Hapus produk ini dari keranjang?');">
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="id_detail_keranjang" value="<?= $item['id_detail_keranjang'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= RINGKASAN + CHECKOUT ================= -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">Ringkasan Belanja</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Jumlah produk</span>
                            <span><?= $jumlah_keranjang ?> barang</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                            <span>Total</span>
                            <span class="text-primary"><?= format_rupiah($total) ?></span>
                        </div>

                        <hr>

                        <!-- Form checkout: alamat pengiriman dikirim ke proses/checkout.php -->
                        <form method="post" action="proses/checkout.php">
                            <div class="mb-3">
                                <label for="alamat_pengiriman" class="form-label">Alamat Pengiriman</label>
                                <textarea class="form-control" id="alamat_pengiriman" name="alamat_pengiriman"
                                          rows="3" required placeholder="Masukkan alamat lengkap pengiriman"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Checkout Sekarang</button>
                            </div>
                        </form>

                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Lanjut Belanja</a>
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
