<?php
// ============================================================
// FILE: admin/users.php
// Fungsi : KELOLA USERS (khusus admin).
//          - Daftar semua pengguna + pencarian & filter role/status.
//          - Ubah data & reset password (lewat admin/user-edit.php).
//          - Aktifkan/nonaktifkan akun (nonaktif = login diblokir).
//          - Hapus pengguna yang belum punya produk/pesanan/keranjang.
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role (khusus admin)
wajib_login('../login.php');
wajib_role(['admin'], '../index.php');

$id_admin = (int) $_SESSION['user_id'];

// ------------------------------------------------------------
// Proses toggle status akun (dilarang menonaktifkan diri sendiri)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle') {
    $id_user = (int) ($_POST['id_user'] ?? 0);

    if ($id_user === $id_admin) {
        header("Location: users.php?pesan=user-sendiri");
        exit;
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE users SET status = IF(status = 'aktif', 'nonaktif', 'aktif')
         WHERE id_user = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        header("Location: users.php?pesan=user-status");
    } else {
        header("Location: users.php?pesan=user-invalid");
    }
    exit;
}

// ------------------------------------------------------------
// Proses hapus pengguna (dilarang menghapus diri sendiri;
// hanya user tanpa produk/pesanan/keranjang — dilindungi FK)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    $id_user = (int) ($_POST['id_user'] ?? 0);

    if ($id_user === $id_admin) {
        header("Location: users.php?pesan=user-sendiri");
        exit;
    }

    // Cek apakah masih punya referensi (produk / pesanan / keranjang)
    $cek = mysqli_prepare(
        $koneksi,
        "SELECT
            (SELECT COUNT(*) FROM produk   WHERE id_penjual = ?) +
            (SELECT COUNT(*) FROM pesanan  WHERE id_user    = ?) +
            (SELECT COUNT(*) FROM keranjang WHERE id_user   = ?) AS jumlah_rujukan"
    );
    mysqli_stmt_bind_param($cek, "iii", $id_user, $id_user, $id_user);
    mysqli_stmt_execute($cek);
    $rujukan = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($cek))['jumlah_rujukan'];

    if ($rujukan > 0) {
        header("Location: users.php?pesan=user-digunakan");
        exit;
    }

    $stmt = mysqli_prepare($koneksi, "DELETE FROM users WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        header("Location: users.php?pesan=user-hapus");
    } else {
        header("Location: users.php?pesan=user-invalid");
    }
    exit;
}

// ------------------------------------------------------------
// Daftar pengguna + pencarian & filter (GET)
// ------------------------------------------------------------
$cari   = trim($_GET['cari'] ?? '');
$role_f = $_GET['role'] ?? 'all';
$status_f = $_GET['status'] ?? 'all';

if (!in_array($role_f, ['all', 'admin', 'penjual', 'pembeli'], true)) {
    $role_f = 'all';
}
if (!in_array($status_f, ['all', 'aktif', 'nonaktif'], true)) {
    $status_f = 'all';
}

$sql = "SELECT u.id_user, u.nama, u.email, u.no_hp, u.role, u.status, u.created_at,
               (SELECT COUNT(*) FROM produk    WHERE id_penjual = u.id_user) AS jumlah_produk,
               (SELECT COUNT(*) FROM pesanan   WHERE id_user    = u.id_user) AS jumlah_pesanan,
               (SELECT COUNT(*) FROM keranjang WHERE id_user    = u.id_user) AS jumlah_keranjang
        FROM users u
        WHERE 1 = 1";
$tipe   = '';
$params = [];

if ($cari !== '') {
    $sql .= " AND (u.nama LIKE ? OR u.email LIKE ?)";
    $tipe   .= 'ss';
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}
if ($role_f !== 'all') {
    $sql .= " AND u.role = ?";
    $tipe   .= 's';
    $params[] = $role_f;
}
if ($status_f !== 'all') {
    $sql .= " AND u.status = ?";
    $tipe   .= 's';
    $params[] = $status_f;
}
$sql .= " ORDER BY u.id_user ASC";

$stmt = mysqli_prepare($koneksi, $sql);
if ($tipe !== '') {
    mysqli_stmt_bind_param($stmt, $tipe, ...$params);
}
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$daftar_user = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $daftar_user[] = $baris;
}

// Statistik dari data yang tampil
$hitung = ['admin' => 0, 'penjual' => 0, 'pembeli' => 0, 'nonaktif' => 0];
foreach ($daftar_user as $u) {
    $hitung[$u['role']]++;
    if ($u['status'] === 'nonaktif') { $hitung['nonaktif']++; }
}
$total_user = count($daftar_user);

$base       = '../';
$judul      = 'Kelola Users';
$menu_aktif = 'users';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <?= tampilkan_pesan() ?>

    <!-- ================= STATISTIK ================= -->
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700 sm:h-11 sm:w-11">
                <i data-lucide="users" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Pengguna</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $total_user ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 sm:h-11 sm:w-11">
                <i data-lucide="shopping-bag" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Pembeli</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $hitung['pembeli'] ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 sm:h-11 sm:w-11">
                <i data-lucide="store" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Penjual</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $hitung['penjual'] ?></p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:gap-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 sm:h-11 sm:w-11">
                <i data-lucide="shield-check" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Admin</p>
                <p class="text-2xl font-extrabold text-slate-800"><?= $hitung['admin'] ?></p>
            </div>
        </div>
    </div>

    <!-- ================= DAFTAR PENGGUNA ================= -->
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-base font-bold text-slate-800">Daftar Pengguna</h2>
                <p class="text-xs text-slate-500">
                    Admin, penjual, dan pembeli &middot; <?= $total_user ?> pengguna
                </p>
            </div>
            <?php if ($hitung['nonaktif'] > 0): ?>
                <span class="badge-warning">
                    <i data-lucide="user-x" class="h-3.5 w-3.5"></i>
                    <?= $hitung['nonaktif'] ?> akun nonaktif
                </span>
            <?php endif; ?>
        </div>

        <!-- Filter / pencarian -->
        <form method="get" class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="cari" class="label-field">Cari nama / email</label>
                <input type="search" id="cari" name="cari" value="<?= htmlspecialchars($cari) ?>"
                       placeholder="Nama atau email..." class="input-field">
            </div>
            <div class="sm:w-44">
                <label for="filter_role" class="label-field">Role</label>
                <select id="filter_role" name="role" class="input-field">
                    <option value="all"   <?= $role_f === 'all' ? 'selected' : '' ?>>Semua role</option>
                    <option value="admin"    <?= $role_f === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="penjual"  <?= $role_f === 'penjual' ? 'selected' : '' ?>>Penjual</option>
                    <option value="pembeli"  <?= $role_f === 'pembeli' ? 'selected' : '' ?>>Pembeli</option>
                </select>
            </div>
            <div class="sm:w-44">
                <label for="filter_status" class="label-field">Status</label>
                <select id="filter_status" name="status" class="input-field">
                    <option value="all"      <?= $status_f === 'all' ? 'selected' : '' ?>>Semua status</option>
                    <option value="aktif"    <?= $status_f === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status_f === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">
                    <i data-lucide="search" class="h-4 w-4"></i> Terapkan
                </button>
                <?php if ($cari !== '' || $role_f !== 'all' || $status_f !== 'all'): ?>
                    <a href="users.php" class="btn-outline">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($total_user === 0): ?>
            <!-- Empty state -->
            <div class="p-10 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i data-lucide="users" class="h-7 w-7"></i>
                </span>
                <p class="font-semibold text-slate-700">Tidak ada pengguna</p>
                <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci atau filter pencarian Anda.</p>
                <a href="users.php" class="btn-outline mt-4 inline-flex">Tampilkan Semua</a>
            </div>
        <?php else: ?>
            <!-- Tabel (md ke atas) -->
            <div class="hidden overflow-x-auto md:block">
                <table class="tabel-data min-w-[920px]">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>No. HP</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Terdaftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_user as $u):
                            $is_self = $u['id_user'] === $id_admin;
                            $bisa_hapus = $u['jumlah_produk'] === 0
                                       && $u['jumlah_pesanan'] === 0
                                       && $u['jumlah_keranjang'] === 0
                                       && !$is_self;
                            $badge_role = [
                                'admin'   => ['badge-indigo', 'Admin'],
                                'penjual' => ['badge-info', 'Penjual'],
                                'pembeli' => ['badge-slate', 'Pembeli'],
                            ][$u['role']];
                        ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
                                            <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-800">
                                                <?= htmlspecialchars($u['nama']) ?>
                                                <?php if ($is_self): ?>
                                                    <span class="badge-slate ml-1">Anda</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="truncate text-xs text-slate-400"><?= htmlspecialchars($u['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm text-slate-600"><?= htmlspecialchars($u['no_hp'] ?: '-') ?></td>
                                <td><span class="<?= $badge_role[0] ?>"><?= $badge_role[1] ?></span></td>
                                <td>
                                    <?php if ($u['status'] === 'aktif'): ?>
                                        <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm text-slate-500">
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <a href="user-edit.php?id=<?= $u['id_user'] ?>"
                                           class="btn-outline btn-sm" title="Ubah data / reset password">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Edit
                                        </a>
                                        <?php if (!$is_self): ?>
                                            <form method="post" onsubmit="return confirm('<?= htmlspecialchars(
                                                $u['status'] === 'aktif'
                                                    ? 'Nonaktifkan akun "' . $u['nama'] . '"? Ia tidak bisa login sampai diaktifkan lagi.'
                                                    : 'Aktifkan kembali akun "' . $u['nama'] . '"?',
                                                ENT_QUOTES
                                            ) ?>')">
                                                <input type="hidden" name="aksi" value="toggle">
                                                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                                <?php if ($u['status'] === 'aktif'): ?>
                                                    <button type="submit" class="btn-outline btn-sm" title="Nonaktifkan akun">
                                                        <i data-lucide="user-x" class="h-3.5 w-3.5"></i> Nonaktif
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn-primary btn-sm" title="Aktifkan akun">
                                                        <i data-lucide="user-check" class="h-3.5 w-3.5"></i> Aktifkan
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            <?php if ($bisa_hapus): ?>
                                                <form method="post" onsubmit="return confirm('<?= htmlspecialchars(
                                                    'Hapus pengguna "' . $u['nama'] . '"? Tindakan ini tidak bisa dibatalkan.',
                                                    ENT_QUOTES
                                                ) ?>')">
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                                    <button type="submit" class="btn-danger btn-sm" title="Hapus pengguna">
                                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- List card (mobile) -->
            <ul class="divide-y divide-slate-100 md:hidden">
                <?php foreach ($daftar_user as $u):
                    $is_self = $u['id_user'] === $id_admin;
                    $bisa_hapus = $u['jumlah_produk'] === 0
                               && $u['jumlah_pesanan'] === 0
                               && $u['jumlah_keranjang'] === 0
                               && !$is_self;
                    $badge_role = [
                        'admin'   => ['badge-indigo', 'Admin'],
                        'penjual' => ['badge-info', 'Penjual'],
                        'pembeli' => ['badge-slate', 'Pembeli'],
                    ][$u['role']];
                ?>
                    <li class="flex flex-col gap-3 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
                                <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-800">
                                    <?= htmlspecialchars($u['nama']) ?>
                                    <?php if ($is_self): ?>
                                        <span class="badge-slate ml-1">Anda</span>
                                    <?php endif; ?>
                                </p>
                                <p class="truncate text-xs text-slate-400"><?= htmlspecialchars($u['email']) ?></p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="<?= $badge_role[0] ?>"><?= $badge_role[1] ?></span>
                                    <?php if ($u['status'] === 'aktif'): ?>
                                        <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif</span>
                                    <?php endif; ?>
                                    <span class="text-xs text-slate-400">
                                        Terdaftar <?= date('d M Y', strtotime($u['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                            <a href="user-edit.php?id=<?= $u['id_user'] ?>" class="btn-outline btn-sm">
                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Edit
                            </a>
                            <?php if (!$is_self): ?>
                                <form method="post" onsubmit="return confirm('<?= htmlspecialchars(
                                    $u['status'] === 'aktif'
                                        ? 'Nonaktifkan akun "' . $u['nama'] . '"? Ia tidak bisa login sampai diaktifkan lagi.'
                                        : 'Aktifkan kembali akun "' . $u['nama'] . '"?',
                                    ENT_QUOTES
                                ) ?>')">
                                    <input type="hidden" name="aksi" value="toggle">
                                    <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                    <?php if ($u['status'] === 'aktif'): ?>
                                        <button type="submit" class="btn-outline btn-sm">
                                            <i data-lucide="user-x" class="h-3.5 w-3.5"></i> Nonaktif
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-primary btn-sm">
                                            <i data-lucide="user-check" class="h-3.5 w-3.5"></i> Aktifkan
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <?php if ($bisa_hapus): ?>
                                    <form method="post" onsubmit="return confirm('<?= htmlspecialchars(
                                        'Hapus pengguna "' . $u['nama'] . '"? Tindakan ini tidak bisa dibatalkan.',
                                        ENT_QUOTES
                                    ) ?>')">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                        <button type="submit" class="btn-danger btn-sm">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
