<?php
// ============================================================
// FILE: components/sidebar-admin.php
// Fungsi : Sidebar admin/penjual (desktop tampil, mobile lewat toggle)
// Variabel:
//   $base        = '../' untuk file di folder admin
//   $menu_aktif  = 'produk' | 'tambah' | '' (menandai menu yang terbuka)
// ============================================================
$base       = $base ?? '';
$menu_aktif = $menu_aktif ?? '';
$role_user  = $_SESSION['role'] ?? 'pembeli';

// Menu berbeda per role (bisa lebih dari satu grup):
// - admin    -> Toko (kelola toko + pesanan) & Pengguna (kelola users)
// - penjual  -> Kelola Produk (produk milik sendiri + pesanan)
if ($role_user === 'admin') {
    $grup_menu = [
        [
            'label' => 'Toko',
            'menu'  => [
                ['ikon' => 'store',         'teks' => 'Kelola Toko',    'url' => 'toko.php',    'aktif' => ['toko']],
                ['ikon' => 'receipt-text',  'teks' => 'Kelola Pesanan', 'url' => 'pesanan.php', 'aktif' => ['pesanan']],
            ],
        ],
        [
            'label' => 'Pengguna',
            'menu'  => [
                ['ikon' => 'users', 'teks' => 'Kelola Users', 'url' => 'users.php', 'aktif' => ['users']],
            ],
        ],
    ];
} else {
    $grup_menu = [
        [
            'label' => 'Kelola Produk',
            'menu'  => [
                ['ikon' => 'layout-dashboard', 'teks' => 'Kelola Produk', 'url' => 'produk.php',        'aktif' => ['produk', 'edit', 'hapus']],
                ['ikon' => 'plus-circle',      'teks' => 'Tambah Produk', 'url' => 'tambah-produk.php', 'aktif' => ['tambah']],
                ['ikon' => 'receipt-text',     'teks' => 'Kelola Pesanan', 'url' => 'pesanan.php',       'aktif' => ['pesanan']],
            ],
        ],
    ];
}
?>
<!-- SIDEBAR -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-800 bg-slate-900 hidden lg:flex">

    <!-- Brand -->
    <div class="flex items-center justify-between gap-2 border-b border-slate-800 px-5 py-4">
        <a href="<?= $base ?>index.php" class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-white">
                <i data-lucide="shopping-bag" class="h-4 w-4"></i>
            </span>
            <span class="text-base font-extrabold text-white">
                Market<span class="text-primary-400">place</span>
            </span>
        </a>
        <!-- Tombol tutup (hanya mobile) -->
        <button type="button" data-toggle="#sidebar" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" aria-label="Tutup menu">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>

    <!-- Menu -->
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        <div>
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Utama</p>
            <ul class="space-y-1">
                <li>
                    <a href="<?= $base ?>index.php"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <i data-lucide="store" class="h-4 w-4"></i> Lihat Toko
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>keranjang.php"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <i data-lucide="shopping-cart" class="h-4 w-4"></i> Keranjang
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>pesanan.php"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <i data-lucide="receipt-text" class="h-4 w-4"></i> Pesanan Saya
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <?php foreach ($grup_menu as $grup): ?>
                <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500"><?= $grup['label'] ?></p>
                <ul class="mb-6 space-y-1">
                    <?php foreach ($grup['menu'] as $m): ?>
                        <?php $menu_dipilih = in_array($menu_aktif, $m['aktif']); ?>
                        <li>
                            <a href="<?= $m['url'] ?>"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition <?= $menu_dipilih ? 'bg-primary-600 text-white shadow-btn-primary' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
                                <i data-lucide="<?= $m['ikon'] ?>" class="h-4 w-4"></i> <?= $m['teks'] ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- Profil singkat -->
    <div class="border-t border-slate-800 px-4 py-4">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-white">
                <?= strtoupper(substr($_SESSION['nama'] ?? '?', 0, 1)) ?>
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></p>
                <p class="text-xs capitalize text-slate-400"><?= htmlspecialchars($role_user) ?></p>
            </div>
            <a href="<?= $base ?>logout.php" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white" title="Logout" aria-label="Logout">
                <i data-lucide="log-out" class="h-4 w-4"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Backdrop mobile (dihilangkan saat sidebar terbuka lewat JS) -->
<div id="sidebar-backdrop" class="hidden fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>
