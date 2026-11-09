<?php
// ============================================================
// FILE: admin/tambah-produk.php
// Fungsi : Form tambah produk baru + menyimpan ke database.
//          Gambar diupload ke folder assets/images/.
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role (hanya admin / penjual)
wajib_login('../login.php');
wajib_role(['admin', 'penjual'], '../index.php');

$pesan_form = '';

// Ambil data kategori untuk dropdown
$kategori_result = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");

// Proses saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk  = trim($_POST['nama_produk'] ?? '');
    $id_kategori  = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $harga        = trim($_POST['harga'] ?? '');
    $stok         = trim($_POST['stok'] ?? '');
    $status       = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    // 1. Validasi input wajib
    if ($nama_produk === '' || $id_kategori <= 0 || $deskripsi === '' || $harga === '' || $stok === '') {
        $pesan_form = '<div class="alert alert-danger">Semua field wajib diisi dengan benar.</div>';
    }
    // 2. Validasi harga dan stok harus angka
    elseif (!is_numeric($harga) || (float) $harga < 0 || !ctype_digit($stok)) {
        $pesan_form = '<div class="alert alert-danger">Harga harus angka positif dan stok harus bilangan bulat.</div>';
    } else {
        // 3. Upload gambar
        $folder_upload = __DIR__ . '/../assets/images/';
        $upload = simpan_gambar($_FILES['gambar'] ?? [], $folder_upload);

        if (!$upload['sukses']) {
            $pesan_form = '<div class="alert alert-danger">' . htmlspecialchars($upload['pesan']) . '</div>';
        } else {
            // 4. Simpan produk ke database (prepared statement)
            $id_penjual = $_SESSION['user_id'];
            $sql = "INSERT INTO produk (id_penjual, id_kategori, nama_produk, deskripsi, harga, stok, gambar, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "iissdiss",
                $id_penjual, $id_kategori, $nama_produk, $deskripsi,
                $harga, $stok, $upload['nama'], $status);
            mysqli_stmt_execute($stmt);

            header("Location: produk.php?pesan=tambah-sukses");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk - Marketplace</title>
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
                <li class="nav-item"><span class="nav-link text-white-50">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h4 class="mb-3">Tambah Produk Baru</h4>

    <?= $pesan_form ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- form enctype="multipart/form-data" wajib untuk upload gambar -->
            <form method="post" action="tambah-produk.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nama_produk" class="form-label">Nama Produk</label>
                    <input type="text" class="form-control" id="nama_produk" name="nama_produk"
                           value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="id_kategori" class="form-label">Kategori</label>
                    <select class="form-select" id="id_kategori" name="id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while ($k = mysqli_fetch_assoc($kategori_result)): ?>
                            <option value="<?= $k['id_kategori'] ?>"
                                <?= ((int) ($_POST['id_kategori'] ?? 0) === (int) $k['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="harga" class="form-label">Harga (Rp)</label>
                        <input type="number" class="form-control" id="harga" name="harga"
                               min="0" step="100" value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stok" class="form-label">Stok</label>
                        <input type="number" class="form-control" id="stok" name="stok"
                               min="0" step="1" value="<?= htmlspecialchars($_POST['stok'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="gambar" class="form-label">Gambar Produk (JPG/PNG/WEBP, maks 2 MB)</label>
                    <input type="file" class="form-control" id="gambar" name="gambar"
                           accept=".jpg,.jpeg,.png,.webp" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="aktif">Aktif (tampil di beranda)</option>
                        <option value="nonaktif">Nonaktif (tidak tampil)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">Simpan Produk</button>
                <a href="produk.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
