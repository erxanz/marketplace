<?php
// ============================================================
// FILE: proses/checkout.php
// Fungsi : Memproses pesanan dari isi keranjang:
//          1. Menghitung total HARGA DARI DATABASE (bukan input user)
//          2. Memvalidasi stok setiap produk
//          3. Membuat data pesanan + detail_pesanan
//          4. Mengurangi stok produk
//          5. Menandai keranjang checkout & mengosongkan isinya
//          Semua dikerjakan dalam SATU transaksi MySQLi
//          (begin_transaction / commit / rollback)
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../keranjang.php");
    exit;
}

// Wajib login
wajib_login('../login.php');

$id_user = $_SESSION['user_id'];
$alamat  = trim($_POST['alamat_pengiriman'] ?? '');

// Validasi alamat pengiriman
if ($alamat === '') {
    header("Location: ../keranjang.php?pesan=alamat-kosong");
    exit;
}

// Supaya error MySQL menjadi exception (mudah ditangkap dengan try-catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ------------------------------------------------------------
// 1. Ambil isi keranjang aktif user beserta data produk
//    (harga diambil langsung dari tabel produk, bukan dari user)
// ------------------------------------------------------------
$sql = "SELECT k.id_keranjang,
               d.id_detail_keranjang, d.jumlah,
               p.id_produk, p.nama_produk, p.harga, p.stok, p.status
        FROM keranjang k
        JOIN detail_keranjang d ON k.id_keranjang = d.id_keranjang
        JOIN produk p ON d.id_produk = p.id_produk
        WHERE k.id_user = ? AND k.status = 'aktif'
        ORDER BY d.id_detail_keranjang";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$item = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $item[] = $baris;
}

// Keranjang kosong -> kembali ke halaman keranjang
if (empty($item)) {
    header("Location: ../keranjang.php?pesan=keranjang-kosong");
    exit;
}

$id_keranjang = $item[0]['id_keranjang'];

// ------------------------------------------------------------
// 2. Validasi stok dan hitung total belanja dari database
// ------------------------------------------------------------
$total = 0;
foreach ($item as $baris) {
    if ($baris['status'] !== 'aktif') {
        header("Location: ../keranjang.php?pesan=checkout-gagal");
        exit;
    }
    if ($baris['jumlah'] > $baris['stok']) {
        header("Location: ../keranjang.php?pesan=stok-kurang");
        exit;
    }
    $total += $baris['jumlah'] * $baris['harga'];
}

// ------------------------------------------------------------
// 3. Simpan pesanan dalam SATU transaksi database
// ------------------------------------------------------------
try {
    mysqli_begin_transaction($koneksi);

    // 3a. Masukkan ke tabel pesanan
    $sql = "INSERT INTO pesanan (id_user, alamat_pengiriman, total_harga, status)
            VALUES (?, ?, ?, 'menunggu')";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "isd", $id_user, $alamat, $total);
    mysqli_stmt_execute($stmt);
    $id_pesanan = mysqli_insert_id($koneksi);

    // 3b. Masukkan rincian ke detail_pesanan + kurangi stok produk
    foreach ($item as $baris) {
        $jumlah  = $baris['jumlah'];
        $harga   = $baris['harga'];                 // harga dari database
        $subtotal = $jumlah * $harga;

        // Simpan rincian pesanan (harga dibekukan saat pesan)
        $sql = "INSERT INTO detail_pesanan
                    (id_pesanan, id_produk, jumlah, harga_saat_pesan, subtotal)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "iiddi", $id_pesanan, $baris['id_produk'],
            $jumlah, $harga, $subtotal);
        mysqli_stmt_execute($stmt);

        // Kurangi stok - hanya jika stok masih mencukupi
        $sql = "UPDATE produk SET stok = stok - ?
                WHERE id_produk = ? AND stok >= ? AND status = 'aktif'";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $jumlah, $baris['id_produk'], $jumlah);
        mysqli_stmt_execute($stmt);

        // Jika tidak ada baris yang berarti stok berubah -> batalkan transaksi
        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new Exception("Stok produk " . $baris['nama_produk'] . " tidak mencukupi.");
        }
    }

    // 3c. Tandai keranjang menjadi status checkout
    $sql = "UPDATE keranjang SET status = 'checkout'
            WHERE id_keranjang = ? AND id_user = ? AND status = 'aktif'";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id_keranjang, $id_user);
    mysqli_stmt_execute($stmt);

    // 3d. Kosongkan detail keranjang (keranjang lama tidak dipakai lagi)
    $sql = "DELETE FROM detail_keranjang WHERE id_keranjang = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_keranjang);
    mysqli_stmt_execute($stmt);

    // Semua langkah berhasil -> simpan permanen
    mysqli_commit($koneksi);

} catch (Exception $e) {
    // Ada kesalahan -> batalkan seluruh perubahan (stok & pesanan kembali seperti semula)
    mysqli_rollback($koneksi);
    header("Location: ../keranjang.php?pesan=checkout-gagal");
    exit;
}

// ------------------------------------------------------------
// 4. Tampilkan halaman pesanan berhasil
// ------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Berhasil - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">Marketplace</a>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Pesan keberhasilan pesanan -->
            <div class="alert alert-success text-center">
                <h4 class="alert-heading">Pesanan Berhasil Dibuat!</h4>
                <p class="mb-0">Terima kasih, pesanan Anda sudah kami terima dan stok produk telah diperbarui.</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <span class="text-muted">Nomor Pesanan</span>
                            <div class="fw-bold fs-5">#<?= $id_pesanan ?></div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted">Status</span>
                            <div><span class="badge bg-warning text-dark">Menunggu</span></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted">Alamat Pengiriman</span>
                        <div><?= nl2br(htmlspecialchars($alamat)) ?></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Harga</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($item as $baris): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($baris['nama_produk']) ?></td>
                                        <td class="text-center"><?= $baris['jumlah'] ?></td>
                                        <td><?= format_rupiah($baris['harga']) ?></td>
                                        <td><?= format_rupiah($baris['jumlah'] * $baris['harga']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th colspan="3" class="text-end">Total Belanja</th>
                                    <th><?= format_rupiah($total) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="../index.php" class="btn btn-primary">Kembali ke Beranda</a>
                        <a href="../keranjang.php" class="btn btn-outline-secondary">Lihat Keranjang</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
