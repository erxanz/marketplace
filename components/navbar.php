<?php
// ============================================================
// FILE: components/navbar.php
// Fungsi : Navbar depan (beranda, detail produk, keranjang)
//          Berisi logo, search bar, keranjang, dan menu auth.
// Variabel: $base ('' untuk halaman di root)
//           $koneksi (untuk badge jumlah keranjang)
// ============================================================
$base = $base ?? '';
$jumlah_keranjang = (function_exists('jumlah_keranjang') && isset($koneksi))
    ? jumlah_keranjang($koneksi) : 0;
$cari = $_GET['cari'] ?? '';
?>
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6">

        <!-- LOGO -->
        <a href="<?= $base ?>index.php" class="flex shrink-0 items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-600 text-white">
                <i data-lucide="shopping-bag" class="h-5 w-5"></i>
            </span>
            <span class="hidden text-lg font-extrabold tracking-tight text-slate-800 sm:block">
                Market<span class="text-primary-600">place</span>
            </span>
        </a>

        <!-- SEARCH (desktop) -->
        <form action="<?= $base ?>index.php" method="get" class="relative mx-auto hidden w-full min-w-0 max-w-xl md:block">
            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input type="search" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari produk yang kamu mau..."
                   class="input-with-icon" aria-label="Cari produk">
            <button type="submit" class="sr-only">Cari</button>
        </form>

        <!-- MENU KANAN (desktop) -->
        <div class="ml-auto hidden shrink-0 items-center gap-1 md:flex">
            <a href="<?= $base ?>index.php" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'nav-link-active' : '' ?>">
                Beranda
            </a>
            <a href="<?= $base ?>keranjang.php" class="nav-link relative <?= basename($_SERVER['SCRIPT_NAME']) === 'keranjang.php' ? 'nav-link-active' : '' ?>">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="shopping-cart" class="h-4 w-4"></i> Keranjang
                    <?php if ($jumlah_keranjang > 0): ?>
                        <span class="rounded-full bg-primary-600 px-1.5 text-[10px] font-bold text-white"><?= $jumlah_keranjang ?></span>
                    <?php endif; ?>
                </span>
            </a>
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'penjual'])): ?>
                <a href="<?= $base ?>admin/produk.php" class="nav-link">Kelola Produk</a>
            <?php endif; ?>
        </div>

        <!-- AKSI (desktop) -->
        <div class="ml-2 hidden shrink-0 items-center gap-2 md:flex">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="flex items-center gap-1.5 whitespace-nowrap rounded-xl bg-primary-50 px-3 py-1.5 text-sm font-semibold text-primary-700">
                    <i data-lucide="user-round" class="h-4 w-4"></i>
                    <?= htmlspecialchars($_SESSION['nama']) ?>
                </span>
                <a href="<?= $base ?>logout.php" class="btn-outline btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?= $base ?>login.php" class="btn-outline btn-sm">Login</a>
                <a href="<?= $base ?>register.php" class="btn-primary btn-sm">Daftar</a>
            <?php endif; ?>
        </div>

        <!-- IKON KANAN (mobile) -->
        <div class="ml-auto flex items-center gap-1 md:hidden">
            <a href="<?= $base ?>keranjang.php" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Keranjang">
                <i data-lucide="shopping-cart" class="h-5 w-5"></i>
                <?php if ($jumlah_keranjang > 0): ?>
                    <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary-600 px-1 text-[10px] font-bold text-white"><?= $jumlah_keranjang ?></span>
                <?php endif; ?>
            </a>
            <button type="button" data-toggle="#menu-mobile" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Buka menu">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>
        </div>
    </div>

    <!-- MENU MOBILE -->
    <div id="menu-mobile" class="hidden border-t border-slate-200 bg-white px-4 py-3 md:hidden">
        <!-- Search (mobile) -->
        <form action="<?= $base ?>index.php" method="get" class="relative mb-3">
            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input type="search" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari produk yang kamu mau..." class="input-with-icon" aria-label="Cari produk">
        </form>

        <nav class="flex flex-col gap-1">
            <a href="<?= $base ?>index.php" class="nav-link">Beranda</a>
            <a href="<?= $base ?>keranjang.php" class="nav-link flex items-center justify-between">
                <span>Keranjang</span>
                <?php if ($jumlah_keranjang > 0): ?>
                    <span class="rounded-full bg-primary-600 px-1.5 text-[10px] font-bold text-white"><?= $jumlah_keranjang ?></span>
                <?php endif; ?>
            </a>
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'penjual'])): ?>
                <a href="<?= $base ?>admin/produk.php" class="nav-link">Kelola Produk</a>
            <?php endif; ?>

            <div class="mt-2 border-t border-slate-100 pt-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="mb-2 px-3 text-sm font-semibold text-slate-700">
                        Halo, <?= htmlspecialchars($_SESSION['nama']) ?>
                    </div>
                    <a href="<?= $base ?>logout.php" class="btn-outline btn-block btn-sm">Logout</a>
                <?php else: ?>
                    <div class="flex gap-2">
                        <a href="<?= $base ?>login.php" class="btn-outline btn-block btn-sm">Login</a>
                        <a href="<?= $base ?>register.php" class="btn-primary btn-block btn-sm">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
