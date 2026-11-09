<?php
// ============================================================
// FILE: keranjang.php
// Fungsi : Menampilkan isi keranjang belanja pembeli.
//          Bisa mengubah jumlah, menghapus item,
//          dan menyediakan form checkout (alamat pengiriman).
//          (logika PHP tidak berubah dari versi asli)
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
    $aksi      = $_POST['aksi'] ?? '';
    $id_detail = isset($_POST['id_detail_keranjang']) ? (int) $_POST['id_detail_keranjang'] : 0;

    // Pastikan baris keranjang benar milik user yang login
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
$total_jumlah = 0;
while ($baris = mysqli_fetch_assoc($hasil_keranjang)) {
    $daftar_item[] = $baris;
    $total += $baris['jumlah'] * $baris['harga'];
    $total_jumlah += $baris['jumlah'];
}

// Ambil alamat user untuk diisikan otomatis pada form checkout
$stmt = mysqli_prepare($koneksi, "SELECT alamat FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($hasil_user);

$base = '';
$judul = 'Keranjang Belanja';
require_once __DIR__ . '/components/head.php';
require_once __DIR__ . '/components/navbar.php';
?>

<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6">

    <!-- Judul halaman -->
    <div class="mb-5 flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
            <i data-lucide="shopping-cart" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-xl font-extrabold text-slate-800">Keranjang Belanja</h1>
            <p class="text-sm text-slate-500"><?= count($daftar_item) ?> produk di keranjang Anda</p>
        </div>
    </div>

    <?= tampilkan_pesan() ?>

    <?php if (empty($daftar_item)): ?>
        <!-- ============ EMPTY STATE ============ -->
        <div class="card p-10 text-center">
            <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <i data-lucide="shopping-basket" class="h-8 w-8"></i>
            </span>
            <h2 class="text-lg font-bold text-slate-800">Keranjang masih kosong</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                Yuk pilih produk favoritmu dan tambahkan ke keranjang belanja.
            </p>
            <a href="index.php" class="btn-primary mt-5 inline-flex">
                <i data-lucide="store" class="h-4 w-4"></i> Mulai Belanja
            </a>
        </div>

    <?php else: ?>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- ============ KOLOM KIRI: DAFTAR PRODUK ============ -->
            <div class="space-y-4 lg:col-span-2">
                <?php foreach ($daftar_item as $item):
                    $gambar   = $item['gambar'] ? $item['gambar'] : 'default.png';
                    $subtotal = $item['jumlah'] * $item['harga'];
                    $melebihi = $item['jumlah'] > $item['stok'];
                ?>
                    <div class="card p-4 sm:p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                            <!-- Gambar + nama -->
                            <div class="flex flex-1 items-center gap-3">
                                <a href="detail-produk.php?id=<?= $item['id_produk'] ?>" class="shrink-0">
                                    <img src="assets/images/<?= htmlspecialchars($gambar) ?>"
                                         alt="<?= htmlspecialchars($item['nama_produk']) ?>"
                                         class="img-thumb h-16 w-16">
                                </a>
                                <div class="min-w-0">
                                    <a href="detail-produk.php?id=<?= $item['id_produk'] ?>"
                                       class="line-clamp-2 text-sm font-semibold text-slate-800 transition hover:text-primary-600">
                                        <?= htmlspecialchars($item['nama_produk']) ?>
                                    </a>
                                    <p class="mt-1 text-sm font-bold text-primary-700"><?= format_rupiah($item['harga']) ?></p>
                                    <?php if ($melebihi): ?>
                                        <p class="mt-1 flex items-center gap-1 text-xs font-medium text-rose-600">
                                            <i data-lucide="triangle-alert" class="h-3.5 w-3.5"></i>
                                            Stok tidak mencukupi (sisa <?= $item['stok'] ?>)
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Pengaturan jumlah -->
                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <form method="post" action="keranjang.php" class="flex items-center gap-2">
                                    <input type="hidden" name="aksi" value="ubah_jumlah">
                                    <input type="hidden" name="id_detail_keranjang" value="<?= $item['id_detail_keranjang'] ?>">
                                    <div class="flex items-center rounded-xl border border-slate-200 bg-white">
                                        <input type="number" name="jumlah"
                                               value="<?= $item['jumlah'] ?>" min="1" max="<?= $item['stok'] ?>"
                                               required aria-label="Jumlah"
                                               class="w-16 rounded-xl border-0 bg-transparent py-2 text-center text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-500">
                                        <button type="submit" class="border-l border-slate-200 px-2.5 py-2 text-slate-500 transition hover:bg-primary-50 hover:text-primary-700" title="Perbarui jumlah">
                                            <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </form>

                                <!-- Subtotal -->
                                <div class="w-32 text-right">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Subtotal</p>
                                    <p class="text-sm font-extrabold text-slate-800"><?= format_rupiah($subtotal) ?></p>
                                </div>

                                <!-- Hapus -->
                                <form method="post" action="keranjang.php"
                                      onsubmit="return confirm('Hapus produk ini dari keranjang?');">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_detail_keranjang" value="<?= $item['id_detail_keranjang'] ?>">
                                    <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                            title="Hapus dari keranjang" aria-label="Hapus produk">
                                        <i data-lucide="trash-2" class="h-5 w-5"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <a href="index.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 transition hover:text-primary-700">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Lanjut belanja
                </a>
            </div>

            <!-- ============ KOLOM KANAN: ORDER SUMMARY ============ -->
            <div class="lg:col-span-1">
                <div class="card sticky top-24 p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-800">
                        <i data-lucide="receipt-text" class="h-5 w-5 text-primary-600"></i> Ringkasan Belanja
                    </h2>

                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Jumlah produk</dt>
                            <dd class="font-semibold text-slate-700"><?= $total_jumlah ?> barang</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Total belanja</dt>
                            <dd class="text-lg font-extrabold text-primary-700"><?= format_rupiah($total) ?></dd>
                        </div>
                    </dl>

                    <hr class="my-4 border-slate-100">

                    <!-- Form checkout -->
                    <form method="post" action="proses/checkout.php">
                        <label for="alamat_pengiriman" class="label-field">Alamat Pengiriman</label>
                        <textarea id="alamat_pengiriman" name="alamat_pengiriman" rows="3" required
                                  placeholder="Masukkan alamat lengkap pengiriman"
                                  class="input-field mb-4 resize-none"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>

                        <button type="submit" class="btn-primary btn-block btn-lg">
                            <i data-lucide="credit-card" class="h-5 w-5"></i> Checkout Sekarang
                        </button>
                    </form>

                    <a href="index.php" class="btn-outline btn-block mt-2">Lanjut Belanja</a>

                    <p class="mt-4 flex items-start gap-2 text-xs text-slate-400">
                        <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-primary-500"></i>
                        Transaksi aman. Stok produk dicek ulang saat checkout.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/components/footer.php'; ?>
