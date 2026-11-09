<?php
// ============================================================
// FILE: detail-produk.php
// Fungsi : Menampilkan detail satu produk berdasarkan ?id=...
//          (logika pengambilan data tidak berubah dari versi asli)
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Ambil id produk dari URL (pakai (int) supaya aman dari injeksi)
$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil produk + kategori + penjual dengan prepared statement
$sql = "SELECT p.*, k.nama_kategori,
               u.nama AS nama_penjual, u.no_hp AS no_hp_penjual,
               u.alamat AS alamat_penjual
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN users u ON p.id_penjual = u.id_user
        WHERE p.id_produk = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$hasil  = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($hasil);

$base = '';
$judul = 'Detail Produk';
require_once __DIR__ . '/components/head.php';
require_once __DIR__ . '/components/navbar.php';
?>

<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6">

    <?= tampilkan_pesan() ?>

    <?php if (!$produk): ?>
        <!-- ============ PRODUK TIDAK DITEMUKAN ============ -->
        <div class="card p-10 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500">
                <i data-lucide="package-x" class="h-7 w-7"></i>
            </span>
            <h2 class="text-lg font-bold text-slate-800">Produk tidak ditemukan</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                Produk mungkin sudah dihapus atau id produk tidak valid.
            </p>
            <a href="index.php" class="btn-primary mt-5 inline-flex">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke Beranda
            </a>
        </div>

    <?php else:
        $gambar = $produk['gambar'] ? $produk['gambar'] : 'default.png';
        $habis  = $produk['stok'] <= 0;
    ?>
        <!-- ============ BREADCRUMB ============ -->
        <nav aria-label="Breadcrumb" class="mb-5">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
                <li class="flex items-center gap-1.5">
                    <a href="index.php" class="transition hover:text-primary-600">Beranda</a>
                </li>
                <li aria-hidden="true"><i data-lucide="chevron-right" class="h-3.5 w-3.5 text-slate-300"></i></li>
                <li class="flex items-center gap-1.5">
                    <a href="index.php?kategori=<?= (int) $produk['id_kategori'] ?>" class="transition hover:text-primary-600">
                        <?= htmlspecialchars($produk['nama_kategori']) ?>
                    </a>
                </li>
                <li aria-hidden="true"><i data-lucide="chevron-right" class="h-3.5 w-3.5 text-slate-300"></i></li>
                <li class="font-medium text-slate-700"><?= htmlspecialchars($produk['nama_produk']) ?></li>
            </ol>
        </nav>

        <!-- ============ DUA KOLOM: GAMBAR + INFORMASI ============ -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- KOLOM KIRI: GAMBAR -->
            <div class="card h-fit overflow-hidden">
                <div class="relative bg-slate-100">
                    <img src="assets/images/<?= htmlspecialchars($gambar) ?>"
                         alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                         class="aspect-[4/3] w-full object-cover">
                    <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-slate-700 shadow-soft backdrop-blur">
                        <?= htmlspecialchars($produk['nama_kategori']) ?>
                    </span>
                    <?php if ($habis): ?>
                        <span class="absolute right-3 top-3 rounded-full bg-rose-600 px-3 py-1 text-xs font-bold text-white shadow">STOK HABIS</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KOLOM KANAN: INFORMASI -->
            <div class="flex flex-col gap-5">
                <div class="card p-5 sm:p-6">
                    <span class="badge-category"><?= htmlspecialchars($produk['nama_kategori']) ?></span>

                    <h1 class="mt-3 text-xl font-extrabold leading-snug text-slate-800 sm:text-2xl">
                        <?= htmlspecialchars($produk['nama_produk']) ?>
                    </h1>

                    <div class="mt-3 text-3xl font-extrabold tracking-tight text-primary-700">
                        <?= format_rupiah($produk['harga']) ?>
                    </div>

                    <!-- Stok -->
                    <div class="mt-4 flex items-center gap-2 text-sm">
                        <span class="text-slate-500">Stok</span>
                        <?php if ($habis): ?>
                            <span class="badge-danger"><i data-lucide="x-circle" class="h-3 w-3"></i> Habis</span>
                        <?php else: ?>
                            <span class="badge-success"><i data-lucide="check-circle-2" class="h-3 w-3"></i> <?= $produk['stok'] ?> tersedia</span>
                        <?php endif; ?>
                        <span class="badge-slate ml-auto">Status: <?= htmlspecialchars(ucfirst($produk['status'])) ?></span>
                    </div>

                    <hr class="my-5 border-slate-100">

                    <!-- Form beli -->
                    <?php if ($produk['status'] === 'aktif' && !$habis): ?>
                        <form method="post" action="proses/tambah-keranjang.php" class="flex flex-wrap items-end gap-3">
                            <input type="hidden" name="id_produk" value="<?= $produk['id_produk'] ?>">
                            <input type="hidden" name="redirect" value="detail-produk.php?id=<?= $produk['id_produk'] ?>">

                            <div>
                                <label for="jumlah" class="label-field">Jumlah</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" id="jumlah" name="jumlah" value="1"
                                           min="1" max="<?= $produk['stok'] ?>" required
                                           class="input-field w-24 text-center font-semibold">
                                    <span class="text-xs text-slate-500">/ pcs</span>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary btn-lg flex-1 min-w-[200px]">
                                <i data-lucide="shopping-cart" class="h-5 w-5"></i> Tambah ke Keranjang
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert-warning mb-0">
                            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>
                            <span>Produk ini tidak dapat dibeli saat ini.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info penjual -->
                <div class="card p-5 sm:p-6">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-500">
                        <i data-lucide="store" class="h-4 w-4 text-primary-600"></i> Informasi Penjual
                    </h2>
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-100 text-lg font-bold text-primary-700">
                            <?= strtoupper(substr($produk['nama_penjual'], 0, 1)) ?>
                        </span>
                        <div class="min-w-0 space-y-1.5 text-sm">
                            <p class="font-semibold text-slate-800"><?= htmlspecialchars($produk['nama_penjual']) ?></p>
                            <p class="flex items-start gap-2 text-slate-500">
                                <i data-lucide="phone" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                                <?= htmlspecialchars($produk['no_hp_penjual'] ?: '-') ?>
                            </p>
                            <p class="flex items-start gap-2 text-slate-500">
                                <i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                                <?= htmlspecialchars($produk['alamat_penjual'] ?: '-') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ DESKRIPSI ============ -->
        <div class="card mt-6 p-5 sm:p-6">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-500">
                <i data-lucide="align-left" class="h-4 w-4 text-primary-600"></i> Deskripsi Produk
            </h2>
            <div class="max-w-3xl whitespace-pre-line text-sm leading-relaxed text-slate-600">
                <?= htmlspecialchars($produk['deskripsi']) ?>
            </div>
        </div>

        <!-- Kembali -->
        <div class="mt-6">
            <a href="index.php" class="btn-outline inline-flex">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke Beranda
            </a>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/components/footer.php'; ?>
