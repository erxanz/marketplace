<?php
// ============================================================
// FILE: admin/toko.php
// Fungsi : Halaman admin untuk KELOLA TOKO (penjual).
//          - Daftar semua toko + jumlah produk & total stok.
//          - Aktifkan / nonaktifkan toko. Toko nonaktif membuat
//            seluruh produknya tidak tampil di beranda.
//          Hanya admin yang boleh membuka halaman ini.
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role (khusus admin)
wajib_login('../login.php');
wajib_role(['admin'], '../index.php');

// ------------------------------------------------------------
// Proses toggle status toko (hanya baris ber-role 'penjual')
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle') {
    $id_toko = (int) ($_POST['id_toko'] ?? 0);
    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE users
         SET status = IF(status = 'aktif', 'nonaktif', 'aktif')
         WHERE id_user = ? AND role = 'penjual'"
    );
    mysqli_stmt_bind_param($stmt, "i", $id_toko);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        header("Location: toko.php?pesan=toko-diubah");
    } else {
        header("Location: toko.php?pesan=toko-invalid");
    }
    exit;
}

// ------------------------------------------------------------
// Daftar toko (penjual) + statistik produknya
// ------------------------------------------------------------
$sql = "SELECT u.id_user, u.nama, u.email, u.no_hp, u.status, u.created_at,
               COUNT(p.id_produk) AS jumlah_produk,
               COALESCE(SUM(p.stok), 0) AS total_stok
        FROM users u
        LEFT JOIN produk p ON p.id_penjual = u.id_user
        WHERE u.role = 'penjual'
        GROUP BY u.id_user
        ORDER BY u.nama ASC";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$daftar_toko = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $daftar_toko[] = $baris;
}

$total_toko = count($daftar_toko);
$toko_aktif = 0;
foreach ($daftar_toko as $t) {
    if ($t['status'] === 'aktif') { $toko_aktif++; }
}

$base       = '../';
$judul      = 'Kelola Toko';
$menu_aktif = 'toko';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <?= tampilkan_pesan() ?>

    <!-- ================= STATISTIK ================= -->
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4">
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700 sm:h-11 sm:w-11">
                <i data-lucide="store" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Toko</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $total_toko ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 sm:h-11 sm:w-11">
                <i data-lucide="circle-check" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Toko Aktif</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $toko_aktif ?></p>
            </div>
        </div>
    </div>

    <!-- ================= DAFTAR TOKO ================= -->
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-base font-bold text-slate-800">Daftar Toko</h2>
                <p class="text-xs text-slate-500">
                    Penjual di marketplace &middot; <?= $total_toko ?> toko
                </p>
            </div>
            <span class="badge-slate">
                <i data-lucide="info" class="h-3.5 w-3.5"></i>
                Toko nonaktif = produk disembunyikan
            </span>
        </div>

        <?php if ($total_toko === 0): ?>
            <!-- Empty state -->
            <div class="p-10 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i data-lucide="store" class="h-7 w-7"></i>
                </span>
                <p class="font-semibold text-slate-700">Belum ada toko</p>
                <p class="mt-1 text-sm text-slate-500">Toko akan muncul setelah ada pengguna mendaftar sebagai penjual.</p>
            </div>
        <?php else: ?>
            <!-- Tabel (md ke atas) -->
            <div class="hidden overflow-x-auto md:block">
                <table class="tabel-data min-w-[720px]">
                    <thead>
                        <tr>
                            <th>Toko</th>
                            <th>No. HP</th>
                            <th class="text-center">Produk</th>
                            <th class="text-center">Total Stok</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_toko as $t): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
                                            <?= strtoupper(substr($t['nama'], 0, 1)) ?>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-800"><?= htmlspecialchars($t['nama']) ?></p>
                                            <p class="truncate text-xs text-slate-400"><?= htmlspecialchars($t['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm text-slate-600"><?= htmlspecialchars($t['no_hp'] ?: '-') ?></td>
                                <td class="text-center font-semibold text-slate-800"><?= (int) $t['jumlah_produk'] ?></td>
                                <td class="text-center font-semibold text-slate-800"><?= (int) $t['total_stok'] ?></td>
                                <td>
                                    <?php if ($t['status'] === 'aktif'): ?>
                                        <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center">
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars($t['status'] === 'aktif'
                                            ? 'Nonaktifkan toko "' . $t['nama'] . '"? Seluruh produk tokonya tidak akan tampil di beranda.'
                                            : 'Aktifkan kembali toko "' . $t['nama'] . '"?', ENT_QUOTES) ?>')">
                                            <input type="hidden" name="aksi" value="toggle">
                                            <input type="hidden" name="id_toko" value="<?= $t['id_user'] ?>">
                                            <?php if ($t['status'] === 'aktif'): ?>
                                                <button type="submit" class="btn-outline btn-sm" title="Nonaktifkan toko">
                                                    <i data-lucide="circle-pause" class="h-3.5 w-3.5"></i> Nonaktifkan
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-primary btn-sm" title="Aktifkan toko">
                                                    <i data-lucide="circle-play" class="h-3.5 w-3.5"></i> Aktifkan
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- List card (mobile, tanpa scroll horizontal) -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <?php foreach ($daftar_toko as $t): ?>
                    <li class="flex flex-col gap-3 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
                                <?= strtoupper(substr($t['nama'], 0, 1)) ?>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-800"><?= htmlspecialchars($t['nama']) ?></p>
                                <p class="truncate text-xs text-slate-400"><?= htmlspecialchars($t['email']) ?></p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <?php if ($t['status'] === 'aktif'): ?>
                                        <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif</span>
                                    <?php endif; ?>
                                    <span class="text-xs text-slate-500">
                                        <?= (int) $t['jumlah_produk'] ?> produk &middot; stok <?= (int) $t['total_stok'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <span class="text-sm text-slate-500"><?= htmlspecialchars($t['no_hp'] ?: '-') ?></span>
                            <form method="post" onsubmit="return confirm('<?= htmlspecialchars($t['status'] === 'aktif'
                                ? 'Nonaktifkan toko "' . $t['nama'] . '"? Seluruh produk tokonya tidak akan tampil di beranda.'
                                : 'Aktifkan kembali toko "' . $t['nama'] . '"?', ENT_QUOTES) ?>')">
                                <input type="hidden" name="aksi" value="toggle">
                                <input type="hidden" name="id_toko" value="<?= $t['id_user'] ?>">
                                <?php if ($t['status'] === 'aktif'): ?>
                                    <button type="submit" class="btn-outline btn-sm" title="Nonaktifkan toko">
                                        <i data-lucide="circle-pause" class="h-3.5 w-3.5"></i> Nonaktifkan
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn-primary btn-sm" title="Aktifkan toko">
                                        <i data-lucide="circle-play" class="h-3.5 w-3.5"></i> Aktifkan
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
