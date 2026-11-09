<?php
// ============================================================
// FILE: admin/produk.php
// Fungsi : Dashboard kelola produk (daftar produk + statistik).
//          Hanya admin dan penjual. Penjual hanya melihat
//          produk miliknya sendiri. (query tidak berubah)
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role
wajib_login('../login.php');
wajib_role(['admin', 'penjual'], '../index.php');

// Ambil semua produk + nama kategori
// Admin melihat semua produk, penjual hanya produknya sendiri
if ($_SESSION['role'] === 'admin') {
    $sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
            FROM produk p
            JOIN kategori k ON p.id_kategori = k.id_kategori
            JOIN users u ON p.id_penjual = u.id_user
            ORDER BY p.id_produk DESC";
    $stmt = mysqli_prepare($koneksi, $sql);
} else {
    $sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
            FROM produk p
            JOIN kategori k ON p.id_kategori = k.id_kategori
            JOIN users u ON p.id_penjual = u.id_user
            WHERE p.id_penjual = ?
            ORDER BY p.id_produk DESC";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
}
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

// Simpan semua baris ke array (untuk tabel + statistik)
$daftar_produk = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $daftar_produk[] = $baris;
}

// Statistik dari data yang sama (tanpa query tambahan)
$total_produk = count($daftar_produk);
$aktif        = 0;
$nonaktif     = 0;
$total_stok   = 0;
foreach ($daftar_produk as $p) {
    if ($p['status'] === 'aktif') { $aktif++; } else { $nonaktif++; }
    $total_stok += (int) $p['stok'];
}

$base       = '../';
$judul      = 'Kelola Produk';
$menu_aktif = 'produk';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <?= tampilkan_pesan() ?>

    <!-- ================= STATISTIK ================= -->
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <!-- Total produk -->
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                <i data-lucide="package" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Produk</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $total_produk ?></p>
            </div>
        </div>
        <!-- Aktif -->
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                <i data-lucide="circle-check" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Produk Aktif</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $aktif ?></p>
            </div>
        </div>
        <!-- Nonaktif -->
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <i data-lucide="circle-pause" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Nonaktif</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $nonaktif ?></p>
            </div>
        </div>
        <!-- Stok -->
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                <i data-lucide="boxes" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Stok</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $total_stok ?></p>
            </div>
        </div>
    </div>

    <!-- ================= TABEL PRODUK ================= -->
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-base font-bold text-slate-800">Daftar Produk</h2>
                <p class="text-xs text-slate-500">
                    <?= $_SESSION['role'] === 'admin' ? 'Semua produk di marketplace' : 'Produk milik Anda' ?>
                    &middot; <?= $total_produk ?> data
                </p>
            </div>
            <a href="tambah-produk.php" class="btn-primary btn-sm">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah Produk
            </a>
        </div>

        <?php if ($total_produk === 0): ?>
            <!-- Empty state -->
            <div class="p-10 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i data-lucide="package-plus" class="h-7 w-7"></i>
                </span>
                <p class="font-semibold text-slate-700">Belum ada produk</p>
                <p class="mt-1 text-sm text-slate-500">Mulai tambahkan produk pertama Anda.</p>
                <a href="tambah-produk.php" class="btn-primary mt-4 inline-flex">
                    <i data-lucide="plus" class="h-4 w-4"></i> Tambah Produk
                </a>
            </div>
        <?php else: ?>
            <div class="hidden overflow-x-auto md:block">
                <table class="tabel-data min-w-[720px]">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_produk as $p):
                            $gambar = $p['gambar'] ? $p['gambar'] : 'default.png';
                        ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="../assets/images/<?= htmlspecialchars($gambar) ?>"
                                             alt="<?= htmlspecialchars($p['nama_produk']) ?>" class="img-thumb">
                                        <div class="min-w-0">
                                            <p class="max-w-[240px] truncate font-semibold text-slate-800">
                                                <?= htmlspecialchars($p['nama_produk']) ?>
                                            </p>
                                            <p class="text-xs text-slate-400">oleh <?= htmlspecialchars($p['nama_penjual']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-slate"><?= htmlspecialchars($p['nama_kategori']) ?></span></td>
                                <td class="font-semibold text-slate-800"><?= format_rupiah($p['harga']) ?></td>
                                <td>
                                    <?php if ($p['stok'] <= 0): ?>
                                        <span class="badge-danger">Habis</span>
                                    <?php else: ?>
                                        <span class="font-medium"><?= $p['stok'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'aktif'): ?>
                                        <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="edit-produk.php?id=<?= $p['id_produk'] ?>"
                                           class="btn-outline btn-sm" title="Edit produk">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Edit
                                        </a>
                                        <a href="hapus-produk.php?id=<?= $p['id_produk'] ?>"
                                           class="btn-danger btn-sm" title="Hapus produk">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===== LIST CARD (mobile, tanpa scroll horizontal) ===== -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <?php foreach ($daftar_produk as $p):
                    $gambar = $p['gambar'] ? $p['gambar'] : 'default.png';
                ?>
                    <li class="flex flex-col gap-3 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <img src="../assets/images/<?= htmlspecialchars($gambar) ?>"
                                 alt="<?= htmlspecialchars($p['nama_produk']) ?>" class="img-thumb h-12 w-12 shrink-0">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-800"><?= htmlspecialchars($p['nama_produk']) ?></p>
                                <p class="text-xs text-slate-400">oleh <?= htmlspecialchars($p['nama_penjual']) ?></p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="badge-slate"><?= htmlspecialchars($p['nama_kategori']) ?></span>
                                    <?php if ($p['status'] === 'aktif'): ?>
                                        <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif</span>
                                    <?php endif; ?>
                                    <?php if ($p['stok'] <= 0): ?>
                                        <span class="badge-danger">Stok habis</span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-500">Stok: <span class="font-semibold text-slate-700"><?= $p['stok'] ?></span></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <span class="font-bold text-slate-800"><?= format_rupiah($p['harga']) ?></span>
                            <div class="flex gap-2">
                                <a href="edit-produk.php?id=<?= $p['id_produk'] ?>"
                                   class="btn-outline btn-sm" title="Edit produk">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Edit
                                </a>
                                <a href="hapus-produk.php?id=<?= $p['id_produk'] ?>"
                                   class="btn-danger btn-sm" title="Hapus produk">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus
                                </a>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
