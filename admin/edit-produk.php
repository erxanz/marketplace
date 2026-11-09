<?php
// ============================================================
// FILE: admin/edit-produk.php
// Fungsi : Mengubah data produk berdasarkan ?id=id_produk.
//          Data lama ditampilkan kembali pada form.
//          Gambar boleh diganti (gambar lama dihapus).
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role
wajib_login('../login.php');
wajib_role(['admin', 'penjual'], '../index.php');

$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pesan_form = '';

// ------------------------------------------------------------
// Ambil data produk lama (penjual hanya boleh edit produknya)
// ------------------------------------------------------------
$sql = "SELECT p.*, k.nama_kategori
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE p.id_produk = ?";
if ($_SESSION['role'] !== 'admin') {
    $sql .= " AND p.id_penjual = ?";
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

// Produk tidak ditemukan atau bukan milik penjual
if (!$produk) {
    header("Location: produk.php?pesan=bukan-milik");
    exit;
}

// Data kategori untuk dropdown
$kategori_result = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");

// ------------------------------------------------------------
// Proses penyimpanan perubahan
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $harga       = trim($_POST['harga'] ?? '');
    $stok        = trim($_POST['stok'] ?? '');
    $status      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    // 1. Validasi input
    if ($nama_produk === '' || $id_kategori <= 0 || $deskripsi === '' || $harga === '' || $stok === '') {
        $pesan_form = '<div class="alert alert-danger">Semua field wajib diisi dengan benar.</div>';
    } elseif (!is_numeric($harga) || (float) $harga < 0 || !ctype_digit($stok)) {
        $pesan_form = '<div class="alert alert-danger">Harga harus angka positif dan stok harus bilangan bulat.</div>';
    } else {
        $gambar_baru = $produk['gambar']; // default: gambar lama tidak berubah

        // 2. Jika user upload gambar baru, upload lalu hapus gambar lama
        $file = $_FILES['gambar'] ?? [];
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $folder_upload = __DIR__ . '/../assets/images/';
            $upload = simpan_gambar($file, $folder_upload);
            if (!$upload['sukses']) {
                $pesan_form = '<div class="alert alert-danger">' . htmlspecialchars($upload['pesan']) . '</div>';
            } else {
                // Hapus file gambar lama jika ada
                if ($produk['gambar'] && file_exists($folder_upload . $produk['gambar'])) {
                    unlink($folder_upload . $produk['gambar']);
                }
                $gambar_baru = $upload['nama'];
            }
        }

        // 3. Update database (prepared statement)
        if ($pesan_form === '') {
            $sql = "UPDATE produk
                    SET nama_produk = ?, id_kategori = ?, deskripsi = ?,
                        harga = ?, stok = ?, gambar = ?, status = ?
                    WHERE id_produk = ?";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "sisdissi",
                $nama_produk, $id_kategori, $deskripsi,
                $harga, $stok, $gambar_baru, $status, $id_produk);
            mysqli_stmt_execute($stmt);

            header("Location: produk.php?pesan=ubah-sukses");
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
    <title>Edit Produk - Marketplace</title>
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
    <h4 class="mb-3">Edit Produk: <?= htmlspecialchars($produk['nama_produk']) ?></h4>

    <?= $pesan_form ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="edit-produk.php?id=<?= $id_produk ?>" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nama_produk" class="form-label">Nama Produk</label>
                    <input type="text" class="form-control" id="nama_produk" name="nama_produk"
                           value="<?= htmlspecialchars($_POST['nama_produk'] ?? $produk['nama_produk']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="id_kategori" class="form-label">Kategori</label>
                    <select class="form-select" id="id_kategori" name="id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while ($k = mysqli_fetch_assoc($kategori_result)):
                            $terpilih = (int) ($_POST['id_kategori'] ?? $produk['id_kategori']) === (int) $k['id_kategori'];
                        ?>
                            <option value="<?= $k['id_kategori'] ?>" <?= $terpilih ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?= htmlspecialchars($_POST['deskripsi'] ?? $produk['deskripsi']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="harga" class="form-label">Harga (Rp)</label>
                        <input type="number" class="form-control" id="harga" name="harga"
                               min="0" step="100" value="<?= htmlspecialchars($_POST['harga'] ?? $produk['harga']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stok" class="form-label">Stok</label>
                        <input type="number" class="form-control" id="stok" name="stok"
                               min="0" step="1" value="<?= htmlspecialchars($_POST['stok'] ?? $produk['stok']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gambar Saat Ini</label><br>
                    <?php $gambar_lama = $produk['gambar'] ? $produk['gambar'] : 'default.png'; ?>
                    <img src="../assets/images/<?= htmlspecialchars($gambar_lama) ?>" class="table-gambar mb-2 d-block" alt="Gambar produk">
                    <input type="file" class="form-control" id="gambar" name="gambar"
                           accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text">Kosongi jika tidak ingin mengganti gambar.</div>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="aktif" <?= ($_POST['status'] ?? $produk['status']) === 'aktif' ? 'selected' : '' ?>>Aktif (tampil di beranda)</option>
                        <option value="nonaktif" <?= ($_POST['status'] ?? $produk['status']) === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (tidak tampil)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                <a href="produk.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
