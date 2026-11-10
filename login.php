<?php
// ============================================================
// FILE: login.php
// Fungsi : Halaman login untuk admin, penjual, dan pembeli
//          Password diverifikasi dengan password_verify()
//          (logika autentikasi tidak berubah)
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Jika sudah login, langsung arahkan sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'pembeli') {
        header("Location: index.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin/toko.php");
    } else {
        header("Location: admin/produk.php");
    }
    exit;
}

$pesan = '';

// Proses login saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input sederhana
    if ($email === '' || $password === '') {
        $pesan = '<div class="alert-danger">Email dan password wajib diisi.</div>';
    } else {
        // Cari user berdasarkan email (prepared statement)
        $sql  = "SELECT id_user, nama, password, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
        $user  = mysqli_fetch_assoc($hasil);

        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {
            // Simpan identitas user ke session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];

            // Arahkan sesuai role
            if ($user['role'] === 'pembeli') {
                header("Location: index.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: admin/toko.php");
            } else {
                header("Location: admin/produk.php");
            }
            exit;
        } else {
            $pesan = '<div class="alert-danger">Email atau password salah.</div>';
        }
    }
}

$base  = '';
$judul = 'Login';
require_once __DIR__ . '/components/head.php';
?>

<!-- ================= HALAMAN AUTH ================= -->
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

        <!-- Kartu login -->
        <div class="card p-6 sm:p-8">
            <div class="mb-6 text-center">
                <h1 class="text-xl font-extrabold text-slate-800">Selamat Datang Kembali</h1>
                <p class="mt-1 text-sm text-slate-500">Silakan login menggunakan akun Anda</p>
            </div>

            <?= tampilkan_pesan() ?>
            <?= $pesan ?>

            <form method="post" action="login.php" class="space-y-4">
                <!-- Email -->
                <div>
                    <label for="email" class="label-field">Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" id="email" name="email" required autofocus
                               placeholder="nama@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="input-with-icon">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="label-field">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="password" name="password" required
                               placeholder="Masukkan password"
                               class="input-with-icon">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-block btn-lg">
                    <i data-lucide="log-in" class="h-4 w-4"></i> Login
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-slate-500">
                Belum punya akun?
                <a href="register.php" class="font-semibold text-primary-600 transition hover:text-primary-700">Daftar sekarang</a>
            </p>

            <!-- Akun demo -->
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <i data-lucide="key-round" class="h-3.5 w-3.5"></i> Akun Demo <span class="text-[10px] font-normal normal-case text-slate-400">(klik teks/password untuk menyalin)</span>
                </p>
                <ul class="space-y-1.5 text-xs text-slate-600">
                    <li class="flex items-center justify-between gap-2">
                        <span>
                            <span class="badge-slate mr-1.5">Admin</span>
                            <span onclick="copyToClipboard('admin@marketplace.test')" class="cursor-pointer hover:underline hover:text-indigo-600" title="Klik untuk salin">admin@marketplace.test</span>
                        </span>
                        <code onclick="copyToClipboard('admin123')" class="cursor-pointer font-mono font-semibold hover:bg-slate-200 rounded px-1 transition-colors" title="Klik untuk salin">admin123</code>
                    </li>
                    <li class="flex items-center justify-between gap-2">
                        <span>
                            <span class="badge-slate mr-1.5">Penjual</span>
                            <span onclick="copyToClipboard('budi@marketplace.test')" class="cursor-pointer hover:underline hover:text-indigo-600" title="Klik untuk salin">budi@marketplace.test</span>
                        </span>
                        <code onclick="copyToClipboard('penjual123')" class="cursor-pointer font-mono font-semibold hover:bg-slate-200 rounded px-1 transition-colors" title="Klik untuk salin">penjual123</code>
                    </li>
                    <li class="flex items-center justify-between gap-2">
                        <span>
                            <span class="badge-slate mr-1.5">Pembeli</span>
                            <span onclick="copyToClipboard('siti@marketplace.test')" class="cursor-pointer hover:underline hover:text-indigo-600" title="Klik untuk salin">siti@marketplace.test</span>
                        </span>
                        <code onclick="copyToClipboard('pembeli123')" class="cursor-pointer font-mono font-semibold hover:bg-slate-200 rounded px-1 transition-colors" title="Klik untuk salin">pembeli123</code>
                    </li>
                </ul>
            </div>

            <!-- Toast Element (Wajib ada di HTML, letakkan di dekat penutup body) -->
            <div id="copy-toast" class="fixed bottom-5 right-5 z-50 translate-y-20 opacity-0 transition-all duration-300 rounded-lg bg-slate-900 px-4 py-2 text-xs font-medium text-white shadow-lg flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span id="toast-message">Tersalin ke clipboard!</span>
            </div>

            <!-- Script untuk menangani copy & toast non-ganggu -->
            <script>
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    const toast = document.getElementById('copy-toast');
                    const message = document.getElementById('toast-message');
                    
                    message.textContent = `Tersalin: ${text}`;
                    
                    // Munculkan toast
                    toast.classList.remove('translate-y-20', 'opacity-0');
                    toast.classList.add('translate-y-0', 'opacity-100');
                    
                    // Sembunyikan otomatis setelah 2 detik
                    setTimeout(() => {
                        toast.classList.remove('translate-y-0', 'opacity-100');
                        toast.classList.add('translate-y-20', 'opacity-0');
                    }, 2000);
                }).catch(err => {
                    console.error('Gagal menyalin:', err);
                });
            }
            </script>

        </div>

        <p class="mt-6 text-center text-sm">
            <a href="index.php" class="inline-flex items-center gap-1.5 font-medium text-slate-500 transition hover:text-primary-600">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke beranda
            </a>
        </p>
    </div>
</main>

<?php require_once __DIR__ . '/components/scripts.php'; ?>
