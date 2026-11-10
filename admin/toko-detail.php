<?php
// ============================================================
// FILE: admin/toko-detail.php
// Fungsi : Detail toko untuk admin — profil penjual + daftar
//          SELURUH produk yang dijual tokonya (read-only).
//          Status toko bisa diaktifkan/nonaktifkan dari sini.
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role (khusus admin)
wajib_login('../login.php');
wajib_role(['admin'], '../index.php');

$id_toko = (int) ($_GET['id'] ?? 0);

// ------------------------------------------------------------
// Proses toggle status toko (hanya baris ber-role 'penjual')
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle') {
    $id_post = (int) ($_POST['id_toko'] ?? 0);
    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE users
         SET status = IF(status = 'aktif', 'nonaktif', 'aktif')
         WHERE id_user = ? AND role = 'penjual'"
    );
    mysqli_stmt_bind_param($stmt, "i", $id_post);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        header("Location: toko-detail.php?id=$id_post&pesan=toko-diubah");
    } else {
        header("Location: toko-detail.php?id=$id_post&pesan=toko-invalid");
    }
    exit;
}

// ------------------------------------------------------------
// Data toko (wajib role penjual)
// ------------------------------------------------------------
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_user, nama, email, no_hp, alamat, status, created_at
     FROM users
     WHERE id_user = ? AND role = 'penjual'"
);
mysqli_stmt_bind_param($stmt, "i", $id_toko);
mysqli_stmt_execute($stmt);
$toko = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$toko) {
    header("Location: toko.php?pesan=toko-invalid");
    exit;
}

// ------------------------------------------------------------
// Produk milik toko ini + nama kategori
// ------------------------------------------------------------
$sql = "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar,
               p.status, p.created_at, k.nama_kategori
        FROM produk p
        JOIN kategori k ON k.id_kategori = p.id_kategori
        WHERE p.id_penjual = ?
        ORDER BY p.id_produk DESC";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_toko);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$daftar_produk = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $daftar_produk[] = $baris;
}

// Statistik produk toko
$total_produk = count($daftar_produk);
$aktif        = 0;
$nonaktif     = 0;
$total_stok   = 0;
foreach ($daftar_produk as $p) {
    if ($p['status'] === 'aktif') { $aktif++; } else { $nonaktif++; }
    $total_stok += (int) $p['stok'];
}

$base       = '../';
$judul      = 'Detail Toko';
$menu_aktif = 'toko';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <?= tampilkan_pesan() ?>

    <!-- Kembali -->
    <a href="toko.php" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-primary-700">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke Kelola Toko
    </a>

    <!-- ================= PROFIL TOKO ================= -->
    <div class="card mb-6 overflow-hidden">
        <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary-100 text-xl font-extrabold text-primary-700">
                    <?= strtoupper(substr($toko['nama'], 0, 1)) ?>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl"><?= htmlspecialchars($toko['nama']) ?></h1>
                        <?php if ($toko['status'] === 'aktif'): ?>
                            <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Toko Aktif</span>
                        <?php else: ?>
                            <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Toko Nonaktif</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="mail" class="h-3.5 w-3.5 text-slate-400"></i>
                            <?= htmlspecialchars($toko['email']) ?>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="phone" class="h-3.5 w-3.5 text-slate-400"></i>
                            <?= htmlspecialchars($toko['no_hp'] ?: '-') ?>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>
                            Bergabung <?= date('d M Y', strtotime($toko['created_at'])) ?>
                        </span>
                    </div>
                    <?php if (!empty($toko['alamat'])): ?>
                        <p class="mt-1.5 flex items-start gap-1.5 text-sm text-slate-500">
                            <i data-lucide="map-pin" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                            <?= htmlspecialchars($toko['alamat']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Toggle status toko -->
            <form method="post" onsubmit="return confirm('<?= htmlspecialchars(
                $toko['status'] === 'aktif'
                    ? 'Nonaktifkan toko "' . $toko['nama'] . '"? Seluruh produk tokonya tidak akan tampil di beranda.'
                    : 'Aktifkan kembali toko "' . $toko['nama'] . '"?',
                ENT_QUOTES
            ) ?>')">
                <input type="hidden" name="aksi" value="toggle">
                <input type="hidden" name="id_toko" value="<?= $toko['id_user'] ?>">
                <?php if ($toko['status'] === 'aktif'): ?>
                    <button type="submit" class="btn-outline btn-sm w-full sm:w-auto">
                        <i data-lucide="circle-pause" class="h-3.5 w-3.5"></i> Nonaktifkan Toko
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn-primary btn-sm w-full sm:w-auto">
                        <i data-lucide="circle-play" class="h-3.5 w-3.5"></i> Aktifkan Toko
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ================= STATISTIK PRODUK ================= -->
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700 sm:h-11 sm:w-11">
                <i data-lucide="package" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Produk</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $total_produk ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 sm:h-11 sm:w-11">
                <i data-lucide="circle-check" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Produk Aktif</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $aktif ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 sm:h-11 sm:w-11">
                <i data-lucide="circle-pause" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Nonaktif</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $nonaktif ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 sm:h-11 sm:w-11">
                <i data-lucide="boxes" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Stok</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $total_stok ?></p>
            </div>
        </div>
    </div>

    <!-- ================= PRODUK YANG DIJUAL ================= -->
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-base font-bold text-slate-800">Produk yang Dijual</h2>
                <p class="text-xs text-slate-500">
                    <?= htmlspecialchars($toko['nama']) ?>
                    &middot; <?= $total_produk ?> produk
                </p>
            </div>
            <span class="badge-slate">
                <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                Tampilan admin (read-only)
            </span>
        </div>

        <?php if ($total_produk === 0): ?>
            <!-- Empty state -->
            <div class="p-10 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i data-lucide="package-x" class="h-7 w-7"></i>
                </span>
                <p class="font-semibold text-slate-700">Toko belum memiliki produk</p>
                <p class="mt-1 text-sm text-slate-500">Produk akan muncul di sini setelah penjual menambahkannya.</p>
            </div>
        <?php else: ?>
            <!-- Tabel (md ke atas) -->
            <div class="hidden overflow-x-auto md:block">
                <table class="tabel-data min-w-[720px]">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th class="text-right">Harga</th>
                            <th class="text-center">Stok</th>
                            <th>Status</th>
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
                                            <p class="max-w-[280px] truncate font-semibold text-slate-800">
                                                <?= htmlspecialchars($p['nama_produk']) ?>
                                            </p>
                                            <p class="text-xs text-slate-400">ID #<?= $p['id_produk'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-slate"><?= htmlspecialchars($p['nama_kategori']) ?></span></td>
                                <td class="text-right font-semibold text-slate-800"><?= format_rupiah($p['harga']) ?></td>
                                <td class="text-center">
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- List card (mobile) -->
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
                        <div class="border-t border-slate-100 pt-3 text-sm">
                            <span class="font-bold text-slate-800"><?= format_rupiah($p['harga']) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
