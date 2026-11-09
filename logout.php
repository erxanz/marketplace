<?php
// ============================================================
// FILE: logout.php
// Fungsi : Menghapus session (mengeluarkan user dari aplikasi)
// ============================================================
session_start();

// Hapus seluruh isi session
session_unset();
session_destroy();

// Kembali ke halaman utama dengan pesan
header("Location: index.php?pesan=logout");
exit;
?>
