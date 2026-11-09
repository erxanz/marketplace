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
<?php
// Komponen tampilan halaman sukses
$base  = '../';
$judul = 'Pesanan Berhasil';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<!-- ================= HALAMAN PESANAN BERHASIL ================= -->
<main class="mx-auto w-full max-w-4xl flex-1 px-4 py-8 sm:px-6">

    <!-- Ikon sukses -->
    <div class="mb-6 text-center">
        <span class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-primary-100 text-primary-600">
            <i data-lucide="circle-check-big" class="h-10 w-10"></i>
        </span>
        <h1 class="text-2xl font-extrabold text-slate-800">Pesanan Berhasil Dibuat!</h1>
        <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
            Terima kasih, pesanan Anda sudah kami terima dan stok produk telah diperbarui.
        </p>
    </div>

    <!-- Ringkasan pesanan -->
    <div class="card overflow-hidden">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Nomor Pesanan</p>
                <p class="text-lg font-extrabold text-slate-800">#<?= $id_pesanan ?></p>
            </div>
            <span class="badge-warning text-xs"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Status: Menunggu</span>
        </div>

        <div class="p-5">
            <!-- Alamat -->
            <div class="mb-5 rounded-xl border border-slate-200 p-4">
                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-400">
                    <i data-lucide="map-pin" class="h-3.5 w-3.5 text-primary-600"></i> Alamat Pengiriman
                </p>
                <p class="text-sm leading-relaxed text-slate-700"><?= nl2br(htmlspecialchars($alamat)) ?></p>
            </div>

            <!-- Tabel rincian -->
            <div class="overflow-x-auto">
                <table class="tabel-data min-w-[520px]">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($item as $baris): ?>
                            <tr>
                                <td class="font-medium text-slate-800"><?= htmlspecialchars($baris['nama_produk']) ?></td>
                                <td class="text-center"><?= $baris['jumlah'] ?></td>
                                <td class="text-right"><?= format_rupiah($baris['harga']) ?></td>
                                <td class="text-right font-semibold"><?= format_rupiah($baris['jumlah'] * $baris['harga']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="border-b-0 px-4 py-3 text-right text-sm font-bold uppercase tracking-wide text-slate-500">
                                Total Belanja
                            </th>
                            <td class="border-b-0 px-4 py-3 text-right text-lg font-extrabold text-primary-700">
                                <?= format_rupiah($total) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Aksi -->
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="../index.php" class="btn-primary">
                    <i data-lucide="store" class="h-4 w-4"></i> Kembali ke Beranda
                </a>
                <a href="../keranjang.php" class="btn-outline">
                    <i data-lucide="shopping-cart" class="h-4 w-4"></i> Lihat Keranjang
                </a>
            </div>

            <p class="mt-5 flex items-start justify-center gap-2 text-xs text-slate-400">
                <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-primary-500"></i>
                Data pesanan tersimpan di tabel <span class="font-mono font-semibold text-slate-500">pesanan</span> dan
                <span class="font-mono font-semibold text-slate-500">detail_pesanan</span>.
            </p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
