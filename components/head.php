<?php
// ============================================================
// FILE: components/head.php
// Fungsi : Komponen <head> + pembuka <body> untuk semua halaman
// Variabel:
//   $judul = judul halaman (opsional, default 'Beranda')
//   $base  = path ke folder root: '' (halaman root) atau '../' (folder admin/proses)
// ============================================================
$judul = $judul ?? 'Beranda';
$base  = $base ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul) ?> - Marketplace</title>
    <!-- Hasil build Tailwind CSS (npm run build) -->
    <link rel="stylesheet" href="<?= $base ?>assets/css/output.css">
</head>
<body class="flex min-h-screen flex-col">
