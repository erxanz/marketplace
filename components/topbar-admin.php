<?php
// ============================================================
// FILE: components/topbar-admin.php
// Fungsi : Header atas halaman admin/penjual
// Variabel: $base, $judul (judul halaman)
// ============================================================
$base  = $base ?? '';
$judul = $judul ?? 'Admin';
?>
<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex items-center gap-3 px-4 py-3.5 sm:px-6">

        <!-- Tombol buka sidebar (mobile) -->
        <button type="button" data-toggle="#sidebar"
                class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 lg:hidden"
                aria-label="Buka menu">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>

        <h1 class="text-base font-bold text-slate-800 sm:text-lg"><?= htmlspecialchars($judul) ?></h1>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            <a href="<?= $base ?>index.php" class="btn-outline btn-sm hidden sm:inline-flex">
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i> Lihat Toko
            </a>
            <span class="hidden items-center gap-2 rounded-xl bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700 sm:flex">
                <i data-lucide="user-round" class="h-4 w-4 text-slate-400"></i>
                <?= htmlspecialchars($_SESSION['nama'] ?? '') ?>
            </span>
            <a href="<?= $base ?>logout.php" class="btn-outline btn-sm" title="Logout">
                <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </div>
</header>
