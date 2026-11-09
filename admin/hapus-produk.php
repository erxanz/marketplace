<?php
// ============================================================
// FILE: admin/hapus-produk.php
// Fungsi : Konfirmasi + penghapusan produk berdasarkan ?id=.
//          1. Halaman konfirmasi sebelum menghapus.
//          2. Menolak hapus jika produk sudah dipakai di
//             detail_pesanan / detail_keranjang (FK RESTRICT).
//          (logika backend tidak berubah)
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role
wajib_login('../login.php');
// Hanya penjual (pemilik toko); admin dialihkan ke Kelola Toko
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: toko.php");
    exit;
}
wajib_role(['penjual'], '../index.php');

$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ------------------------------------------------------------
// Ambil data produk (hanya produk milik penjual yang login)
// ------------------------------------------------------------
$sql = "SELECT id_produk, nama_produk, gambar
        FROM produk WHERE id_produk = ? AND id_penjual = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_produk, $_SESSION['user_id']);
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

$base       = '../';
$judul      = 'Hapus Produk';
$menu_aktif = 'hapus';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <!-- Halaman konfirmasi sebelum produk dihapus -->
    <div class="mx-auto max-w-lg">
        <div class="card overflow-hidden">
            <!-- Header merah -->
            <div class="bg-gradient-to-br from-rose-500 to-rose-600 px-6 py-7 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur">
                    <i data-lucide="triangle-alert" class="h-7 w-7"></i>
                </span>
                <h1 class="text-lg font-extrabold text-white">Konfirmasi Hapus Produk</h1>
                <p class="mt-1 text-sm text-rose-100">Tindakan ini tidak dapat dibatalkan</p>
            </div>

            <div class="p-6 text-center">
                <!-- Produk yang akan dihapus -->
                <?php $gambar_hapus = $produk['gambar'] ? $produk['gambar'] : 'default.png'; ?>
                <div class="mx-auto mb-4 flex w-fit items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-left">
                    <img src="../assets/images/<?= htmlspecialchars($gambar_hapus) ?>"
                         alt="<?= htmlspecialchars($produk['nama_produk']) ?>" class="img-thumb">
                    <p class="max-w-[220px] text-sm font-semibold text-slate-800"><?= htmlspecialchars($produk['nama_produk']) ?></p>
                </div>

                <p class="text-sm text-slate-500">
                    Produk yang sudah tercatat dalam transaksi <span class="font-semibold text-slate-700">tidak dapat dihapus</span>.
                </p>

                <form method="post" action="hapus-produk.php?id=<?= $id_produk ?>" class="mt-6 flex flex-wrap justify-center gap-3">
                    <input type="hidden" name="aksi" value="hapus">
                    <button type="submit" class="btn-danger">
                        <i data-lucide="trash-2" class="h-4 w-4"></i> Ya, Hapus Produk
                    </button>
                    <a href="produk.php" class="btn-outline">Batal</a>
                </form>
            </div>
        </div>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
