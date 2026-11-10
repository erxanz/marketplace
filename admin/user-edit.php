<?php
// ============================================================
// FILE: admin/user-edit.php
// Fungsi : Ubah data pengguna + reset password (khusus admin).
//          - Nama, email, no. HP, alamat, role.
//          - Password baru (opsional; kosongkan = tidak diganti).
//          - Role akun sendiri tidak bisa diubah.
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role (khusus admin)
wajib_login('../login.php');
wajib_role(['admin'], '../index.php');

$id_admin = (int) $_SESSION['user_id'];
$id_user  = (int) ($_GET['id'] ?? $_POST['id_user'] ?? 0);

// ------------------------------------------------------------
// Muat data user (wajib ada)
// ------------------------------------------------------------
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT u.*, (SELECT COUNT(*) FROM produk   WHERE id_penjual = u.id_user) AS jumlah_produk,
                 (SELECT COUNT(*) FROM pesanan  WHERE id_user    = u.id_user) AS jumlah_pesanan
     FROM users u
     WHERE u.id_user = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    header("Location: users.php?pesan=user-invalid");
    exit;
}

// ------------------------------------------------------------
// Proses simpan perubahan
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $no_hp   = trim($_POST['no_hp'] ?? '');
    $alamat  = trim($_POST['alamat'] ?? '');
    $role    = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $is_self = $id_user === $id_admin;

    // Role akun sendiri tidak boleh diubah (pakai role lama)
    if ($is_self) {
        $role = $user['role'];
    }

    // Validasi dasar
    if ($nama === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !in_array($role, ['admin', 'penjual', 'pembeli'], true)) {
        header("Location: user-edit.php?id=$id_user&pesan=user-gagal");
        exit;
    }

    // Password baru: opsional, minimal 6 karakter jika diisi
    if ($password !== '' && strlen($password) < 6) {
        header("Location: user-edit.php?id=$id_user&pesan=user-gagal");
        exit;
    }

    // Email unik (kecuali tetap miliknya sendiri)
    $cek = mysqli_prepare(
        $koneksi,
        "SELECT id_user FROM users WHERE email = ? AND id_user != ?"
    );
    mysqli_stmt_bind_param($cek, "si", $email, $id_user);
    mysqli_stmt_execute($cek);
    if (mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0) {
        header("Location: user-edit.php?id=$id_user&pesan=user-gagal");
        exit;
    }

    // Susun query UPDATE (password hanya jika diisi)
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql  = "UPDATE users
                 SET nama = ?, email = ?, no_hp = ?, alamat = ?, role = ?, password = ?
                 WHERE id_user = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $nama, $email, $no_hp, $alamat, $role, $hash, $id_user);
    } else {
        $sql  = "UPDATE users
                 SET nama = ?, email = ?, no_hp = ?, alamat = ?, role = ?
                 WHERE id_user = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $nama, $email, $no_hp, $alamat, $role, $id_user);
    }
    mysqli_stmt_execute($stmt);

    header("Location: users.php?pesan=user-diubah");
    exit;
}

$base       = '../';
$judul      = 'Edit Pengguna';
$menu_aktif = 'users';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <?= tampilkan_pesan() ?>

    <!-- Kembali -->
    <a href="users.php" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-primary-700">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke Kelola Users
    </a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- ================= FORM EDIT ================= -->
        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800">Ubah Data Pengguna</h2>
                    <p class="text-xs text-slate-500">
                        ID #<?= $user['id_user'] ?> &middot; terdaftar <?= date('d M Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>

                <form method="post" action="user-edit.php?id=<?= $user['id_user'] ?>" class="p-5">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id_user" value="<?= $user['id_user'] ?>">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="nama" class="label-field">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" required maxlength="100"
                                   value="<?= htmlspecialchars($_POST['nama'] ?? $user['nama']) ?>"
                                   class="input-field">
                        </div>

                        <div>
                            <label for="email" class="label-field">Email</label>
                            <input type="email" id="email" name="email" required maxlength="100"
                                   value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                                   class="input-field">
                        </div>

                        <div>
                            <label for="no_hp" class="label-field">No. HP</label>
                            <input type="text" id="no_hp" name="no_hp" maxlength="20"
                                   value="<?= htmlspecialchars($_POST['no_hp'] ?? ($user['no_hp'] ?? '')) ?>"
                                   placeholder="08xxxxxxxxxx"
                                   class="input-field">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="alamat" class="label-field">Alamat</label>
                            <textarea id="alamat" name="alamat" rows="3" maxlength="500"
                                      placeholder="Alamat lengkap..."
                                      class="input-field resize-none"><?= htmlspecialchars($_POST['alamat'] ?? ($user['alamat'] ?? '')) ?></textarea>
                        </div>

                        <div>
                            <label for="role" class="label-field">Role</label>
                            <select id="role" name="role" class="input-field" <?= $id_user === $id_admin ? 'disabled' : '' ?>>
                                <option value="pembeli" <?= ($user['role'] ?? '') === 'pembeli' ? 'selected' : '' ?>>Pembeli</option>
                                <option value="penjual" <?= ($user['role'] ?? '') === 'penjual' ? 'selected' : '' ?>>Penjual</option>
                                <option value="admin"   <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if ($id_user === $id_admin): ?>
                                <p class="mt-1 text-xs text-slate-400">Role akun sendiri tidak bisa diubah.</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="password" class="label-field">Password Baru</label>
                            <input type="password" id="password" name="password" minlength="6" maxlength="255"
                                   placeholder="Kosongkan jika tidak diganti"
                                   autocomplete="new-password"
                                   class="input-field">
                            <p class="mt-1 text-xs text-slate-400">Minimal 6 karakter. Kosongkan = password tetap.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <button type="submit" class="btn-primary">
                            <i data-lucide="save" class="h-4 w-4"></i> Simpan Perubahan
                        </button>
                        <a href="users.php" class="btn-outline">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= INFO AKUN ================= -->
        <div class="space-y-4">
            <div class="card p-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-100 text-lg font-extrabold text-primary-700">
                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-bold text-slate-800"><?= htmlspecialchars($user['nama']) ?></p>
                        <p class="truncate text-xs text-slate-400"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                </div>

                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-500">Role</dt>
                        <dd>
                            <?php
                            $badge_role = [
                                'admin'   => ['badge-indigo', 'Admin'],
                                'penjual' => ['badge-info', 'Penjual'],
                                'pembeli' => ['badge-slate', 'Pembeli'],
                            ][$user['role']];
                            ?>
                            <span class="<?= $badge_role[0] ?>"><?= $badge_role[1] ?></span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-500">Status</dt>
                        <dd>
                            <?php if ($user['status'] === 'aktif'): ?>
                                <span class="badge-success"><span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span> Aktif</span>
                            <?php else: ?>
                                <span class="badge-warning"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-500">Produk dimiliki</dt>
                        <dd class="font-semibold text-slate-700"><?= (int) $user['jumlah_produk'] ?></dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-500">Pesanan dibuat</dt>
                        <dd class="font-semibold text-slate-700"><?= (int) $user['jumlah_pesanan'] ?></dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-500">Bergabung</dt>
                        <dd class="font-semibold text-slate-700">
                            <?= date('d M Y H:i', strtotime($user['created_at'])) ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="card p-5">
                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-400">
                    <i data-lucide="info" class="h-3.5 w-3.5 text-primary-600"></i> Catatan
                </p>
                <p class="text-xs leading-relaxed text-slate-500">
                    Status <strong>nonaktif</strong> memblokir login pengguna. Pengguna yang sudah punya
                    produk, pesanan, atau keranjang <strong>tidak bisa dihapus</strong> (perlindungan Foreign Key).
                </p>
            </div>
        </div>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
