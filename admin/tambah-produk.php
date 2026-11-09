<?php
// ============================================================
// FILE: admin/tambah-produk.php
// Fungsi : Form tambah produk baru + menyimpan ke database.
//          (logika validasi, upload, dan insert tidak berubah)
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role (hanya penjual / pemilik toko)
wajib_login('../login.php');
// Admin dialihkan ke halaman Kelola Toko
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: toko.php");
    exit;
}
wajib_role(['penjual'], '../index.php');

$pesan_form = '';

// Ambil data kategori untuk dropdown
$kategori_result = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");

// Proses saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $harga       = trim($_POST['harga'] ?? '');
    $stok        = trim($_POST['stok'] ?? '');
    $status      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    // 1. Validasi input wajib
    if ($nama_produk === '' || $id_kategori <= 0 || $deskripsi === '' || $harga === '' || $stok === '') {
        $pesan_form = '<div class="alert-danger">Semua field wajib diisi dengan benar.</div>';
    }
    // 2. Validasi harga dan stok harus angka
    elseif (!is_numeric($harga) || (float) $harga < 0 || !ctype_digit($stok)) {
        $pesan_form = '<div class="alert-danger">Harga harus angka positif dan stok harus bilangan bulat.</div>';
    } else {
        // 3. Upload gambar
        $folder_upload = __DIR__ . '/../assets/images/';
        $upload = simpan_gambar($_FILES['gambar'] ?? [], $folder_upload);

        if (!$upload['sukses']) {
            $pesan_form = '<div class="alert-danger">' . htmlspecialchars($upload['pesan']) . '</div>';
        } else {
            // 4. Simpan produk ke database (prepared statement)
            $id_penjual = $_SESSION['user_id'];
            $sql = "INSERT INTO produk (id_penjual, id_kategori, nama_produk, deskripsi, harga, stok, gambar, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "iissdiss",
                $id_penjual, $id_kategori, $nama_produk, $deskripsi,
                $harga, $stok, $upload['nama'], $status);
            mysqli_stmt_execute($stmt);

            header("Location: produk.php?pesan=tambah-sukses");
            exit;
        }
    }
}

$base       = '../';
$judul      = 'Tambah Produk';
$menu_aktif = 'tambah';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
?>
<div class="flex min-h-screen flex-1 flex-col lg:pl-64">
<?php require_once __DIR__ . '/../components/topbar-admin.php'; ?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <div class="mx-auto max-w-3xl">

        <!-- Judul -->
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                <i data-lucide="package-plus" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-extrabold text-slate-800">Tambah Produk Baru</h1>
                <p class="text-sm text-slate-500">Isi data produk yang akan dijual di toko Anda</p>
            </div>
        </div>

        <?= $pesan_form ?>

        <div class="card p-5 sm:p-6">
            <!-- enctype="multipart/form-data" wajib untuk upload gambar -->
            <form method="post" action="tambah-produk.php" enctype="multipart/form-data" class="space-y-5">

                <!-- Nama produk -->
                <div>
                    <label for="nama_produk" class="label-field">Nama Produk</label>
                    <div class="relative">
                        <i data-lucide="tag" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="nama_produk" name="nama_produk" required
                               placeholder="Contoh: Laptop ASUS VivoBook 14"
                               value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>"
                               class="input-with-icon">
                    </div>
                </div>

                <!-- Kategori -->
                <div>
                    <label for="id_kategori" class="label-field">Kategori</label>
                    <div class="relative">
                        <i data-lucide="layers" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <select id="id_kategori" name="id_kategori" required class="input-with-icon">
                            <option value="">-- Pilih Kategori --</option>
                            <?php while ($k = mysqli_fetch_assoc($kategori_result)): ?>
                                <option value="<?= $k['id_kategori'] ?>"
                                    <?= ((int) ($_POST['id_kategori'] ?? 0) === (int) $k['id_kategori']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label for="deskripsi" class="label-field">Deskripsi Produk</label>
                    <textarea id="deskripsi" name="deskripsi" rows="5" required
                              placeholder="Jelaskan produk Anda: bahan, ukuran, keunggulan, dll."
                              class="input-field resize-y"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>

                <!-- Harga, stok, status -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <label for="harga" class="label-field">Harga (Rp)</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">Rp</span>
                            <input type="number" id="harga" name="harga" min="0" step="100" required
                                   placeholder="0" value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>"
                                   class="input-field pl-10">
                        </div>
                    </div>
                    <div>
                        <label for="stok" class="label-field">Stok</label>
                        <div class="relative">
                            <i data-lucide="boxes" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input type="number" id="stok" name="stok" min="0" step="1" required
                                   placeholder="0" value="<?= htmlspecialchars($_POST['stok'] ?? '') ?>"
                                   class="input-with-icon">
                        </div>
                    </div>
                    <div>
                        <label for="status" class="label-field">Status</label>
                        <select id="status" name="status" class="input-field">
                            <option value="aktif">Aktif (tampil)</option>
                            <option value="nonaktif">Nonaktif (sembunyi)</option>
                        </select>
                    </div>
                </div>

                <!-- Upload gambar -->
                <div>
                    <label for="gambar" class="label-field">Gambar Produk</label>
                    <label for="gambar"
                           class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-7 text-center transition hover:border-primary-400 hover:bg-primary-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-primary-600 shadow-soft">
                            <i data-lucide="image-up" class="h-5 w-5"></i>
                        </span>
                        <span class="text-sm font-semibold text-slate-700">Klik untuk memilih gambar</span>
                        <span class="text-xs text-slate-400">Format JPG, PNG, atau WEBP &middot; Maksimal 2 MB</span>
                        <input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp" required class="sr-only">
                        <!-- Nama file terpilih ditampilkan oleh app.js -->
                        <span id="nama-file-gambar" class="badge-success hidden"></span>
                    </label>
                </div>

                <!-- Aksi -->
                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                    <button type="submit" class="btn-primary">
                        <i data-lucide="save" class="h-4 w-4"></i> Simpan Produk
                    </button>
                    <a href="produk.php" class="btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
