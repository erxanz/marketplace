<?php
// ============================================================
// FILE: config/koneksi.php
// Fungsi : Membuka koneksi ke database MySQL menggunakan MySQLi
// Setting di bawah menyesuaikan XAMPP (root / password kosong)
// ============================================================

$host     = "localhost";
$username = "root";
$password = "";          // default XAMPP: password kosong
$database = "marketplace_db";

// Membuka koneksi database
$koneksi = mysqli_connect($host, $username, $password, $database);

// Jika koneksi gagal, tampilkan pesan yang mudah dipahami lalu hentikan script
if (!$koneksi) {
    die("Koneksi database gagal. Pastikan MySQL/XAMPP sudah berjalan. "
        . "Detail error: " . mysqli_connect_error());
}

// Set charset supaya karakter Indonesia (berhasil, dll) tampil benar
mysqli_set_charset($koneksi, "utf8mb4");
?>
