<?php
// ============================================================
// FILE: components/footer.php
// Fungsi : Footer depan + memuat JavaScript global
// Variabel: $base
// ============================================================
$base = $base ?? '';
?>
<footer class="mt-auto border-t border-slate-200 bg-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
        <!-- Brand -->
        <div>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-600 text-white">
                    <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-slate-800">
                    Market<span class="text-primary-600">place</span>
                </span>
            </div>
            <p class="text-sm leading-relaxed text-slate-500">
                Marketplace sederhana untuk jual beli produk online.
                Dibuat dengan PHP Native, MySQLi, dan Tailwind CSS.
            </p>
        </div>

        <!-- Navigasi -->
        <div>
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-800">Navigasi</h3>
            <ul class="space-y-2 text-sm text-slate-500">
                <li><a href="<?= $base ?>index.php" class="transition hover:text-primary-600">Beranda</a></li>
                <li><a href="<?= $base ?>keranjang.php" class="transition hover:text-primary-600">Keranjang</a></li>
                <li><a href="<?= $base ?>login.php" class="transition hover:text-primary-600">Login</a></li>
                <li><a href="<?= $base ?>register.php" class="transition hover:text-primary-600">Daftar Akun</a></li>
            </ul>
        </div>

        <!-- Kategori -->
        <div>
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-800">Kategori</h3>
            <ul class="space-y-2 text-sm text-slate-500">
                <li><a href="<?= $base ?>index.php?kategori=1" class="transition hover:text-primary-600">Elektronik</a></li>
                <li><a href="<?= $base ?>index.php?kategori=2" class="transition hover:text-primary-600">Fashion</a></li>
                <li><a href="<?= $base ?>index.php?kategori=3" class="transition hover:text-primary-600">Rumah Tangga</a></li>
                <li><a href="<?= $base ?>index.php?kategori=4" class="transition hover:text-primary-600">Kesehatan</a></li>
            </ul>
        </div>

        <!-- Kontak -->
        <div>
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-800">Bantuan</h3>
            <ul class="space-y-2.5 text-sm text-slate-500">
                <li class="flex items-start gap-2"><i data-lucide="mail" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600"></i> support@marketplace.test</li>
                <li class="flex items-start gap-2"><i data-lucide="phone" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600"></i> 0811-1111-1111</li>
                <li class="flex items-start gap-2"><i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600"></i> Jl. Pusat No. 1, Jakarta</li>
            </ul>
        </div>
    </div>

    <div class="border-t border-slate-100 py-4 text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> Marketplace &mdash; Tugas Praktikum Web (Soal 11)
    </div>
</footer>

<?php require_once __DIR__ . '/scripts.php'; ?>
