<?php
// ============================================================
// FILE: index.php
// Fungsi : Halaman utama Marketplace.
//          Menampilkan produk aktif + pencarian (?cari=) +
//          filter kategori (?kategori=id). Backend tetap sama,
//          hanya query ditambah kondisi saat parameter dikirim.
// ============================================================
session_start();
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// ------------------------------------------------------------
// Parameter pencarian & filter kategori (opsional)
// ------------------------------------------------------------
$cari          = trim($_GET['cari'] ?? '');
$kategori_aktif = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

// Susun query: kondisi pencarian/filter hanya ditambahkan jika diisi
$sql = "SELECT p.id_produk, p.nama_produk, p.harga, p.gambar, p.stok,
               k.nama_kategori, u.nama AS nama_penjual
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN users u ON p.id_penjual = u.id_user
        WHERE p.status = 'aktif' AND u.status = 'aktif'";
$tipe   = "";
$params = [];

if ($cari !== '') {
    $sql   .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $tipe  .= "ss";
    $params[] = "%" . $cari . "%";
    $params[] = "%" . $cari . "%";
}
if ($kategori_aktif > 0) {
    $sql   .= " AND p.id_kategori = ?";
    $tipe  .= "i";
    $params[] = $kategori_aktif;
}
$sql .= " ORDER BY p.id_produk DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $tipe, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_produk = mysqli_num_rows($result);

// Daftar kategori untuk chips filter
$daftar_kategori = [];
$hasil_kategori  = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");
while ($k = mysqli_fetch_assoc($hasil_kategori)) {
    $daftar_kategori[] = $k;
}

// Nama kategori aktif (untuk info hasil pencarian)
$nama_kategori_aktif = '';
foreach ($daftar_kategori as $k) {
    if ((int) $k['id_kategori'] === $kategori_aktif) {
        $nama_kategori_aktif = $k['nama_kategori'];
    }
}

$base = '';
require_once __DIR__ . '/components/head.php';
require_once __DIR__ . '/components/navbar.php';
?>

<!-- ================= PESAN (alert) ================= -->
<div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6">
    <?= tampilkan_pesan() ?>
</div>

<!-- ================= HERO ================= -->
<section class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-emerald-900 px-6 py-10 shadow-card sm:px-10 sm:py-14">
        <!-- Hiasan latar -->
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-24 right-24 h-64 w-64 rounded-full bg-primary-400/20 blur-3xl"></div>

        <div class="relative max-w-2xl">
            <span class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                <i data-lucide="sparkles" class="h-3.5 w-3.5"></i> Belanja jadi lebih mudah
            </span>
            <h1 class="text-2xl font-extrabold leading-tight text-white sm:text-4xl">
                Temukan Produk Pilihan<br class="hidden sm:block"> Untuk Kebutuhanmu
            </h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-primary-100 sm:text-base">
                Ratusan produk dari penjual terpercaya. Aman, cepat, dan harga bersahabat.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#daftar-produk" class="btn bg-white text-primary-700 hover:bg-primary-50">
                    <i data-lucide="shopping-cart" class="h-4 w-4"></i> Belanja Sekarang
                </a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn border border-white/40 text-white hover:bg-white/10">
                        <i data-lucide="store" class="h-4 w-4"></i> Jadi Penjual
                    </a>
                <?php else: ?>
                    <a href="keranjang.php" class="btn border border-white/40 text-white hover:bg-white/10">
                        <i data-lucide="receipt-text" class="h-4 w-4"></i> Lihat Keranjang
                    </a>
                <?php endif; ?>
            </div>

            <!-- Statistik mini -->
            <div class="mt-8 flex flex-wrap gap-x-8 gap-y-3 text-white">
                <div>
                    <div class="text-xl font-extrabold sm:text-2xl"><?= $total_produk ?></div>
                    <div class="text-xs text-primary-100">Produk Tersedia</div>
                </div>
                <div class="sm:border-l sm:border-white/25 sm:pl-8">
                    <div class="text-xl font-extrabold sm:text-2xl"><?= count($daftar_kategori) ?></div>
                    <div class="text-xs text-primary-100">Kategori</div>
                </div>
                <div class="sm:border-l sm:border-white/25 sm:pl-8">
                    <div class="text-xl font-extrabold sm:text-2xl">100%</div>
                    <div class="text-xs text-primary-100">Aman &amp; Terpercaya</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= FILTER KATEGORI ================= -->
<section class="mx-auto mt-8 w-full max-w-7xl px-4 sm:px-6">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="section-title">Kategori</h2>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="index.php<?= $cari !== '' ? '?cari=' . urlencode($cari) : '' ?>"
           class="inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-medium transition
                  <?= $kategori_aktif === 0
                      ? 'border-primary-600 bg-primary-600 text-white shadow-btn-primary'
                      : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300 hover:text-primary-700' ?>">
            <i data-lucide="layout-grid" class="h-4 w-4"></i> Semua
        </a>
        <?php foreach ($daftar_kategori as $k): ?>
            <a href="index.php?kategori=<?= (int) $k['id_kategori'] ?><?= $cari !== '' ? '&cari=' . urlencode($cari) : '' ?>"
               class="inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-medium transition
                      <?= $kategori_aktif === (int) $k['id_kategori']
                          ? 'border-primary-600 bg-primary-600 text-white shadow-btn-primary'
                          : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300 hover:text-primary-700' ?>">
                <?= htmlspecialchars($k['nama_kategori']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ================= DAFTAR PRODUK ================= -->
<section id="daftar-produk" class="mx-auto mt-8 w-full max-w-7xl px-4 pb-12 sm:px-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="section-title flex items-center gap-2">
            <i data-lucide="package-search" class="h-5 w-5 text-primary-600"></i>
            <?= $cari !== '' || $kategori_aktif > 0 ? 'Hasil Pencarian' : 'Semua Produk' ?>
        </h2>
        <span class="text-sm text-slate-500"><?= $total_produk ?> produk ditemukan</span>
    </div>

    <!-- Info filter aktif -->
    <?php if ($cari !== '' || $kategori_aktif > 0): ?>
        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
            <span class="text-slate-500">Filter:</span>
            <?php if ($cari !== ''): ?>
                <span class="badge-slate">Kata kunci: &ldquo;<?= htmlspecialchars($cari) ?>&rdquo;</span>
            <?php endif; ?>
            <?php if ($kategori_aktif > 0): ?>
                <span class="badge-slate">Kategori: <?= htmlspecialchars($nama_kategori_aktif) ?></span>
            <?php endif; ?>
            <a href="index.php" class="ml-auto inline-flex items-center gap-1 font-semibold text-primary-600 hover:text-primary-700">
                <i data-lucide="x" class="h-4 w-4"></i> Hapus filter
            </a>
        </div>
    <?php endif; ?>

    <?php if ($total_produk === 0): ?>
        <!-- Empty state -->
        <div class="card p-10 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <i data-lucide="package-x" class="h-7 w-7"></i>
            </span>
            <h3 class="text-lg font-bold text-slate-800">Produk tidak ditemukan</h3>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                Coba gunakan kata kunci lain, pilih kategori berbeda, atau lihat semua produk.
            </p>
            <a href="index.php" class="btn-primary mt-5 inline-flex">
                <i data-lucide="rotate-ccw" class="h-4 w-4"></i> Lihat Semua Produk
            </a>
        </div>
    <?php else:
        // Tujuan redirect tambah-keranjang (mempertahankan filter pencarian)
        $tujuan_redirect = 'index.php';
        if ($cari !== '') {
            $tujuan_redirect .= '?cari=' . urlencode($cari);
        }
        if ($kategori_aktif > 0) {
            $tujuan_redirect .= ($cari !== '' ? '&' : '?') . 'kategori=' . $kategori_aktif;
        }
    ?>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php while ($p = mysqli_fetch_assoc($result)):
                $gambar = $p['gambar'] ? $p['gambar'] : 'default.png';
                $habis  = $p['stok'] <= 0;
            ?>
                <!-- CARD PRODUK -->
                <div class="produk-card group">
                    <!-- Gambar + badge kategori -->
                    <a href="detail-produk.php?id=<?= $p['id_produk'] ?>" class="relative block overflow-hidden">
                        <img src="assets/images/<?= htmlspecialchars($gambar) ?>"
                             alt="<?= htmlspecialchars($p['nama_produk']) ?>"
                             class="produk-gambar">
                        <span class="absolute left-2.5 top-2.5 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-soft backdrop-blur">
                            <?= htmlspecialchars($p['nama_kategori']) ?>
                        </span>
                        <?php if ($habis): ?>
                            <span class="absolute right-2.5 top-2.5 rounded-full bg-rose-600 px-2.5 py-1 text-[11px] font-bold text-white shadow">HABIS</span>
                        <?php endif; ?>
                    </a>

                    <!-- Info -->
                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-1.5 flex items-center gap-1.5 text-xs text-slate-500">
                            <i data-lucide="store" class="h-3.5 w-3.5 text-slate-400"></i>
                            <span class="truncate"><?= htmlspecialchars($p['nama_penjual']) ?></span>
                        </div>

                        <a href="detail-produk.php?id=<?= $p['id_produk'] ?>"
                           class="line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-snug text-slate-800 transition hover:text-primary-600">
                            <?= htmlspecialchars($p['nama_produk']) ?>
                        </a>

                        <div class="mt-2 text-lg font-extrabold text-primary-700"><?= format_rupiah($p['harga']) ?></div>

                        <div class="mt-1 flex items-center gap-1 text-xs text-slate-500">
                            <i data-lucide="boxes" class="h-3.5 w-3.5"></i>
                            Stok:
                            <?php if ($habis): ?>
                                <span class="font-semibold text-rose-600">Habis</span>
                            <?php else: ?>
                                <span class="font-semibold text-slate-700"><?= $p['stok'] ?> tersedia</span>
                            <?php endif; ?>
                        </div>

                        <!-- Aksi -->
                        <div class="mt-auto grid grid-cols-2 gap-2 pt-4">
                            <a href="detail-produk.php?id=<?= $p['id_produk'] ?>" class="btn-outline btn-sm">
                                Detail
                            </a>
                            <form method="post" action="proses/tambah-keranjang.php">
                                <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                                <input type="hidden" name="jumlah" value="1">
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($tujuan_redirect) ?>">
                                <button type="submit" class="btn-primary btn-sm w-full" <?= $habis ? 'disabled' : '' ?>>
                                    <i data-lucide="shopping-cart" class="h-3.5 w-3.5"></i> Keranjang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/components/footer.php'; ?>
