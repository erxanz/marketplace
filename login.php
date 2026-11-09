<?php
// ============================================================
// FILE: login.php
// Fungsi : Halaman login untuk admin, penjual, dan pembeli
//          Password diverifikasi dengan password_verify()
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Jika sudah login, langsung arahkan sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'pembeli') {
        header("Location: index.php");
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
        $pesan = '<div class="alert alert-danger">Email dan password wajib diisi.</div>';
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
            } else {
                header("Location: admin/produk.php");
            }
            exit;
        } else {
            $pesan = '<div class="alert alert-danger">Email atau password salah.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-1">Login Marketplace</h4>
                    <p class="text-center text-muted small mb-4">Silakan login menggunakan akun Anda</p>

                    <?= tampilkan_pesan() ?>
                    <?= $pesan ?>

                    <form method="post" action="login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <div class="mt-3 small text-muted">
                        <div class="fw-semibold mb-1">Akun demo:</div>
                        <div>Admin: admin@marketplace.test / admin123</div>
                        <div>Penjual: budi@marketplace.test / penjual123</div>
                        <div>Pembeli: siti@marketplace.test / pembeli123</div>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="index.php">&larr; Kembali ke beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
