<?php
// ============================================================
// FILE: pesanan.php
// Fungsi : Riwayat pesanan milik user yang login (semua role).
//          Tampilan baca-saja: nomor pesanan, rincian, metode
//          pembayaran, dan status terkini.
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

wajib_login('login.php');

// ------------------------------------------------------------
// Ambil semua pesanan milik user + ringkasan item & metode
// ------------------------------------------------------------
$sql = "SELECT pe.id_pesanan, pe.tanggal_pesanan, pe.alamat_pengiriman,
               pe.total_harga, pe.metode_pembayaran, pe.status,
               (SELECT COUNT(*) FROM detail_pesanan dp
                 WHERE dp.id_pesanan = pe.id_pesanan) AS jumlah_item,
               (SELECT GROUP_CONCAT(CONCAT(dp2.jumlah, 'x ', pr.nama_produk) SEPARATOR ' | ')
                  FROM detail_pesanan dp2
                  JOIN produk pr ON pr.id_produk = dp2.id_produk
                 WHERE dp2.id_pesanan = pe.id_pesanan) AS ringkasan
        FROM pesanan pe
        WHERE pe.id_user = ?
        ORDER BY pe.tanggal_pesanan DESC";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$daftar_pesanan = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $daftar_pesanan[] = $baris;
}

$base  = '';
$judul = 'Pesanan Saya';
require_once __DIR__ . '/components/head.php';
require_once __DIR__ . '/components/navbar.php';
?>

<main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6">

    <?= tampilkan_pesan() ?>

    <!-- Judul halaman -->
    <div class="mb-5 flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
            <i data-lucide="receipt-text" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl">Pesanan Saya</h1>
            <p class="text-xs text-slate-500"><?= count($daftar_pesanan) ?> pesanan dibuat</p>
        </div>
    </div>

    <?php if (empty($daftar_pesanan)): ?>
        <!-- Empty state -->
        <div class="card p-10 text-center">
            <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <i data-lucide="shopping-bag" class="h-7 w-7"></i>
            </span>
            <p class="font-semibold text-slate-700">Belum ada pesanan</p>
            <p class="mt-1 text-sm text-slate-500">Belanja dulu dan pesanan akan tampil di sini.</p>
            <a href="index.php" class="btn-primary mt-4 inline-flex">
                <i data-lucide="store" class="h-4 w-4"></i> Mulai Belanja
            </a>
        </div>
    <?php else: ?>
        <!-- Daftar pesanan -->
        <div class="space-y-4">
            <?php foreach ($daftar_pesanan as $p): ?>
                <div class="card overflow-hidden">
                    <!-- Header kartu -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
                        <div>
                            <p class="font-extrabold text-slate-800">#<?= $p['id_pesanan'] ?></p>
                            <p class="text-xs text-slate-400">
                                <?= date('d M Y H:i', strtotime($p['tanggal_pesanan'])) ?>
                                &middot; <?= (int) $p['jumlah_item'] ?> produk
                            </p>
                        </div>
                        <?= badge_status_pesanan($p['status']) ?>
                    </div>

                    <!-- Isi kartu -->
                    <div class="px-5 py-4">
                        <p class="text-sm leading-relaxed text-slate-600">
                            <?= htmlspecialchars($p['ringkasan'] ?? '-') ?>
                        </p>

                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Total</p>
                                <p class="mt-0.5 font-extrabold text-primary-700"><?= format_rupiah($p['total_harga']) ?></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Metode Pembayaran</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-700">
                                    <?= htmlspecialchars(label_metode_pembayaran($p['metode_pembayaran'])) ?>
                                </p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Dikirim Ke</p>
                                <p class="mt-0.5 text-sm text-slate-600"><?= htmlspecialchars($p['alamat_pengiriman']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/components/footer.php'; ?>
