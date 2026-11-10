<?php
// ============================================================
// FILE: admin/pesanan.php
// Fungsi : Halaman KELOLA PESANAN untuk admin & penjual.
//          - Admin melihat semua pesanan.
//          - Penjual hanya pesanan yang memuat produk miliknya.
//          - Ubah status: diproses -> dikirim -> selesai,
//            plus pembatalan selama belum selesai.
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role (admin & penjual)
wajib_login('../login.php');
wajib_role(['admin', 'penjual'], '../index.php');

$is_admin = $_SESSION['role'] === 'admin';

// ------------------------------------------------------------
// Proses ubah status pesanan
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ubah-status') {
    $id_pesanan = (int) ($_POST['id_pesanan'] ?? 0);
    $aksi       = $_POST['status_baru'] ?? '';

    // Peta aksi -> status baru + status yang boleh ditinggalkan
    $peta_aksi = [
        'proses'  => ['status_baru' => 'diproses',    'dari' => ['menunggu']],
        'kirim'   => ['status_baru' => 'dikirim',     'dari' => ['diproses']],
        'selesai' => ['status_baru' => 'selesai',     'dari' => ['dikirim']],
        'batal'   => ['status_baru' => 'dibatalkan',  'dari' => ['menunggu', 'diproses']],
    ];

    if (isset($peta_aksi[$aksi]) && $id_pesanan > 0) {
        $status_baru = $peta_aksi[$aksi]['status_baru'];
        $dari        = $peta_aksi[$aksi]['dari'];
        $placeholder = implode(',', array_fill(0, count($dari), '?'));

        if ($is_admin) {
            // Admin: pesanan apa pun
            $sql = "UPDATE pesanan SET status = ?
                    WHERE id_pesanan = ? AND status IN ($placeholder)";
            $params = array_merge([$status_baru, $id_pesanan], $dari);
            $tipe   = 'si' . str_repeat('s', count($dari));
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, $tipe, ...$params);
        } else {
            // Penjual: hanya pesanan yang memuat produk miliknya
            $sql = "UPDATE pesanan pe SET pe.status = ?
                    WHERE pe.id_pesanan = ? AND pe.status IN ($placeholder)
                      AND EXISTS (
                        SELECT 1 FROM detail_pesanan dp
                        JOIN produk pr ON pr.id_produk = dp.id_produk
                        WHERE dp.id_pesanan = pe.id_pesanan
                          AND pr.id_penjual = ?
                      )";
            $params = array_merge([$status_baru, $id_pesanan], $dari, [$_SESSION['user_id']]);
            $tipe   = 'si' . str_repeat('s', count($dari)) . 'i';
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, $tipe, ...$params);
        }
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            header("Location: pesanan.php?pesan=status-ubah");
        } else {
            header("Location: pesanan.php?pesan=status-gagal");
        }
        exit;
    }

    header("Location: pesanan.php?pesan=status-gagal");
    exit;
}

// ------------------------------------------------------------
// Daftar pesanan (+ ringkasan item) — penjual difilter produknya
// ------------------------------------------------------------
$sql = "SELECT pe.id_pesanan, pe.tanggal_pesanan, pe.total_harga,
               pe.metode_pembayaran, pe.status, u.nama AS nama_pembeli,
               (SELECT COUNT(*) FROM detail_pesanan dp
                 WHERE dp.id_pesanan = pe.id_pesanan) AS jumlah_item,
               (SELECT GROUP_CONCAT(CONCAT(dp2.jumlah, 'x ', pr.nama_produk) SEPARATOR ' | ')
                  FROM detail_pesanan dp2
                  JOIN produk pr ON pr.id_produk = dp2.id_produk
                 WHERE dp2.id_pesanan = pe.id_pesanan) AS ringkasan
        FROM pesanan pe
        JOIN users u ON u.id_user = pe.id_user
        WHERE 1 = 1";
$tipe   = '';
$params = [];

if (!$is_admin) {
    $sql .= " AND EXISTS (
                SELECT 1 FROM detail_pesanan dp
                JOIN produk pr ON pr.id_produk = dp.id_produk
                WHERE dp.id_pesanan = pe.id_pesanan
                  AND pr.id_penjual = ?
              )";
    $tipe   = 'i';
    $params = [$_SESSION['user_id']];
}

$sql .= " ORDER BY pe.tanggal_pesanan DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if ($tipe !== '') {
    mysqli_stmt_bind_param($stmt, $tipe, ...$params);
}
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$daftar_pesanan = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $daftar_pesanan[] = $baris;
}

// Statistik sederhana
$perlu_diproses = 0;
$aktif_berjalan = 0;
foreach ($daftar_pesanan as $p) {
    if (in_array($p['status'], ['menunggu', 'diproses'], true)) {
        $perlu_diproses++;
    }
    if (!in_array($p['status'], ['selesai', 'dibatalkan'], true)) {
        $aktif_berjalan++;
    }
}

// Tombol aksi yang tersedia per status
function tombol_status($id_pesanan, $status)
{
    $map = [
        'menunggu' => [
            ['proses',  'Proses',  'btn-primary', 'circle-play'],
            ['batal',   'Batalkan', 'btn-outline', 'circle-x'],
        ],
        'diproses' => [
            ['kirim',   'Kirim',   'btn-primary', 'truck'],
            ['batal',   'Batalkan', 'btn-outline', 'circle-x'],
        ],
        'dikirim'  => [
            ['selesai', 'Selesai', 'btn-primary', 'circle-check'],
        ],
    ];
    if (empty($map[$status])) {
        return '<span class="text-xs text-slate-400">-</span>';
    }

    $html = '<div class="flex flex-wrap items-center justify-center gap-2">';
    foreach ($map[$status] as [$aksi, $label, $kelas, $ikon]) {
        if ($aksi === 'proses') {
            $konfirmasi = htmlspecialchars("Proses pesanan #$id_pesanan?", ENT_QUOTES);
        } elseif ($aksi === 'kirim') {
            $konfirmasi = htmlspecialchars("Tandai pesanan #$id_pesanan sudah dikirim?", ENT_QUOTES);
        } elseif ($aksi === 'selesai') {
            $konfirmasi = htmlspecialchars("Tandai pesanan #$id_pesanan selesai?", ENT_QUOTES);
        } else {
            $konfirmasi = htmlspecialchars("Batalkan pesanan #$id_pesanan?", ENT_QUOTES);
        }
        $html .= '<form method="post" onsubmit="return confirm(\'' . $konfirmasi . '\')">'
               . '<input type="hidden" name="aksi" value="ubah-status">'
               . '<input type="hidden" name="id_pesanan" value="' . $id_pesanan . '">'
               . '<input type="hidden" name="status_baru" value="' . $aksi . '">'
               . '<button type="submit" class="' . $kelas . ' btn-sm">'
               . '<i data-lucide="' . $ikon . '" class="h-3.5 w-3.5"></i> ' . $label . '</button>'
               . '</form>';
    }
    return $html . '</div>';
}

$base       = '../';
$judul      = 'Kelola Pesanan';
$menu_aktif = 'pesanan';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <?= tampilkan_pesan() ?>

    <!-- ================= STATISTIK ================= -->
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700 sm:h-11 sm:w-11">
                <i data-lucide="receipt-text" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Pesanan</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= count($daftar_pesanan) ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 sm:h-11 sm:w-11">
                <i data-lucide="clock" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Perlu Diproses</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $perlu_diproses ?></p>
            </div>
        </div>
        <div class="card col-span-2 flex items-center gap-3 p-4 sm:gap-4 sm:p-5 lg:col-span-1">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 sm:h-11 sm:w-11">
                <i data-lucide="package-check" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Sedang Berjalan</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $aktif_berjalan ?></p>
            </div>
        </div>
    </div>

    <!-- ================= DAFTAR PESANAN ================= -->
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-base font-bold text-slate-800">Daftar Pesanan</h2>
                <p class="text-xs text-slate-500">
                    <?= $is_admin ? 'Semua pesanan marketplace' : 'Pesanan produk Anda' ?>
                    &middot; <?= count($daftar_pesanan) ?> pesanan
                </p>
            </div>
            <span class="badge-slate">
                <i data-lucide="info" class="h-3.5 w-3.5"></i>
                Status: diproses &rarr; dikirim &rarr; selesai
            </span>
        </div>

        <?php if (empty($daftar_pesanan)): ?>
            <!-- Empty state -->
            <div class="p-10 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i data-lucide="receipt-text" class="h-7 w-7"></i>
                </span>
                <p class="font-semibold text-slate-700">Belum ada pesanan</p>
                <p class="mt-1 text-sm text-slate-500">Pesanan akan muncul di sini setelah pembeli checkout.</p>
            </div>
        <?php else: ?>
            <!-- Tabel (md ke atas) -->
            <div class="hidden overflow-x-auto md:block">
                <table class="tabel-data min-w-[860px]">
                    <thead>
                        <tr>
                            <th>Pesanan</th>
                            <th>Pembeli</th>
                            <th>Ringkasan</th>
                            <th class="text-right">Total</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_pesanan as $p): ?>
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800">#<?= $p['id_pesanan'] ?></p>
                                    <p class="text-xs text-slate-400">
                                        <?= date('d M Y H:i', strtotime($p['tanggal_pesanan'])) ?>
                                    </p>
                                </td>
                                <td class="text-sm font-medium text-slate-700">
                                    <?= htmlspecialchars($p['nama_pembeli']) ?>
                                </td>
                                <td class="max-w-[240px]">
                                    <p class="truncate text-sm text-slate-600" title="<?= htmlspecialchars($p['ringkasan'] ?? '') ?>">
                                        <?= htmlspecialchars($p['ringkasan'] ?? '-') ?>
                                    </p>
                                    <p class="text-xs text-slate-400"><?= (int) $p['jumlah_item'] ?> produk</p>
                                </td>
                                <td class="text-right text-sm font-extrabold text-primary-700">
                                    <?= format_rupiah($p['total_harga']) ?>
                                </td>
                                <td class="text-sm text-slate-600">
                                    <?= htmlspecialchars(label_metode_pembayaran($p['metode_pembayaran'])) ?>
                                </td>
                                <td><?= badge_status_pesanan($p['status']) ?></td>
                                <td><?= tombol_status($p['id_pesanan'], $p['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- List card (mobile) -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <?php foreach ($daftar_pesanan as $p): ?>
                    <li class="flex flex-col gap-3 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800">
                                    #<?= $p['id_pesanan'] ?>
                                    <span class="ml-1 text-xs font-medium text-slate-400">
                                        &middot; <?= htmlspecialchars($p['nama_pembeli']) ?>
                                    </span>
                                </p>
                                <p class="text-xs text-slate-400">
                                    <?= date('d M Y H:i', strtotime($p['tanggal_pesanan'])) ?>
                                </p>
                            </div>
                            <?= badge_status_pesanan($p['status']) ?>
                        </div>
                        <p class="text-sm leading-relaxed text-slate-600">
                            <?= htmlspecialchars($p['ringkasan'] ?? '-') ?>
                        </p>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                            <span class="font-bold text-primary-700"><?= format_rupiah($p['total_harga']) ?></span>
                            <span><?= htmlspecialchars(label_metode_pembayaran($p['metode_pembayaran'])) ?></span>
                        </div>
                        <?= tombol_status($p['id_pesanan'], $p['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
