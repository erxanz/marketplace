<?php
// ============================================================
// FILE: register.php (FILE BARU - fitur pendaftaran akun)
// Fungsi : Mendaftarkan akun baru (pembeli / penjual).
//          Password disimpan dengan password_hash().
//          Tidak mengubah tabel/query yang sudah ada,
//          hanya INSERT ke tabel users seperti login exist.
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Jika sudah login, langsung keluar dari halaman register
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pesan_form = '';

// ------------------------------------------------------------
// Proses pendaftaran
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $no_hp      = trim($_POST['no_hp'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');
    $password   = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';
    $role       = ($_POST['role'] ?? 'pembeli') === 'penjual' ? 'penjual' : 'pembeli';

    // 1. Validasi wajib isi
    if ($nama === '' || $email === '' || $password === '' || $konfirmasi === '') {
        $pesan_form = '<div class="alert-danger">Nama, email, dan password wajib diisi.</div>';
    }
    // 2. Format email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_form = '<div class="alert-danger">Format email tidak valid.</div>';
    }
    // 3. Panjang password
    elseif (strlen($password) < 6) {
        $pesan_form = '<div class="alert-danger">Password minimal 6 karakter.</div>';
    }
    // 4. Konfirmasi password sama
    elseif ($password !== $konfirmasi) {
        $pesan_form = '<div class="alert-danger">Konfirmasi password tidak sama.</div>';
    } else {
        // 5. Cek apakah email sudah terdaftar
        $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($hasil) > 0) {
            $pesan_form = '<div class="alert-danger">Email sudah terdaftar, silakan gunakan email lain.</div>';
        } else {
            // 6. Simpan user baru (password di-hash)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (nama, email, password, no_hp, alamat, role)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "ssssss", $nama, $email, $password_hash, $no_hp, $alamat, $role);
            mysqli_stmt_execute($stmt);

            header("Location: login.php?pesan=register-sukses");
            exit;
        }
    }
}

$base  = '';
$judul = 'Daftar Akun';
require_once __DIR__ . '/components/head.php';
?>

<main class="flex flex-1 items-center justify-center bg-gradient-to-b from-primary-50 via-slate-50 to-slate-50 px-4 py-10">

    <div class="w-full max-w-md">

        <!-- Logo -->
        <a href="index.php" class="mb-6 flex items-center justify-center gap-2">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-btn-primary">
                <i data-lucide="shopping-bag" class="h-6 w-6"></i>
            </span>
            <span class="text-2xl font-extrabold tracking-tight text-slate-800">
                Market<span class="text-primary-600">place</span>
            </span>
        </a>

        <!-- Kartu register -->
        <div class="card p-6 sm:p-8">
            <div class="mb-6 text-center">
                <h1 class="text-xl font-extrabold text-slate-800">Buat Akun Baru</h1>
                <p class="mt-1 text-sm text-slate-500">Daftar gratis untuk mulai belanja atau berjualan</p>
            </div>

            <?= tampilkan_pesan() ?>
            <?= $pesan_form ?>

            <form method="post" action="register.php" class="space-y-4">
                <!-- Nama -->
                <div>
                    <label for="nama" class="label-field">Nama Lengkap</label>
                    <div class="relative">
                        <i data-lucide="user-round" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="nama" name="nama" required placeholder="Nama Anda"
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" class="input-with-icon">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="label-field">Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" id="email" name="email" required placeholder="nama@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="input-with-icon">
                    </div>
                </div>

                <!-- No. HP & Role -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="no_hp" class="label-field">No. HP <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx"
                               value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" class="input-field">
                    </div>
                    <div>
                        <label for="role" class="label-field">Saya ingin</label>
                        <select id="role" name="role" class="input-field">
                            <option value="pembeli" <?= (($_POST['role'] ?? 'pembeli') === 'pembeli') ? 'selected' : '' ?>>Membeli produk</option>
                            <option value="penjual" <?= (($_POST['role'] ?? '') === 'penjual') ? 'selected' : '' ?>>Menjual produk</option>
                        </select>
                    </div>
                </div>

                <!-- Alamat -->
                <div>
                    <label for="alamat" class="label-field">Alamat <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea id="alamat" name="alamat" rows="2" placeholder="Alamat lengkap Anda"
                              class="input-field resize-none"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="label-field">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="password" name="password" required minlength="6"
                               placeholder="Minimal 6 karakter" class="input-with-icon">
                    </div>
                </div>

                <!-- Konfirmasi password -->
                <div>
                    <label for="konfirmasi_password" class="label-field">Ulangi Password</label>
                    <div class="relative">
                        <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" required minlength="6"
                               placeholder="Masukkan ulang password" class="input-with-icon">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-block btn-lg">
                    <i data-lucide="user-plus" class="h-4 w-4"></i> Daftar Sekarang
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-slate-500">
                Sudah punya akun?
                <a href="login.php" class="font-semibold text-primary-600 transition hover:text-primary-700">Login di sini</a>
            </p>
        </div>

        <p class="mt-6 text-center text-sm">
            <a href="index.php" class="inline-flex items-center gap-1.5 font-medium text-slate-500 transition hover:text-primary-600">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke beranda
            </a>
        </p>
    </div>
</main>

<?php require_once __DIR__ . '/components/scripts.php'; ?>
