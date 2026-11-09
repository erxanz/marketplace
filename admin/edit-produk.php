<?php
// ============================================================
// FILE: admin/edit-produk.php
// Fungsi : Mengubah data produk berdasarkan ?id=id_produk.
//          (logika kepemilikan, validasi, upload tidak berubah)
// ============================================================
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek login dan role
wajib_login('../login.php');
wajib_role(['admin', 'penjual'], '../index.php');

$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pesan_form = '';

// ------------------------------------------------------------
// Ambil data produk lama (penjual hanya boleh edit produknya)
// ------------------------------------------------------------
$sql = "SELECT p.*, k.nama_kategori
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE p.id_produk = ?";
if ($_SESSION['role'] !== 'admin') {
    $sql .= " AND p.id_penjual = ?";
}
$stmt = mysqli_prepare($koneksi, $sql);
if ($_SESSION['role'] !== 'admin') {
    mysqli_stmt_bind_param($stmt, "ii", $id_produk, $_SESSION['user_id']);
} else {
    mysqli_stmt_bind_param($stmt, "i", $id_produk);
}
mysqli_stmt_execute($stmt);
$hasil  = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($hasil);

// Produk tidak ditemukan atau bukan milik penjual
if (!$produk) {
    header("Location: produk.php?pesan=bukan-milik");
    exit;
}

// Data kategori untuk dropdown
$kategori_result = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");

// ------------------------------------------------------------
// Proses penyimpanan perubahan
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $harga       = trim($_POST['harga'] ?? '');
    $stok        = trim($_POST['stok'] ?? '');
    $status      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    // 1. Validasi input
    if ($nama_produk === '' || $id_kategori <= 0 || $deskripsi === '' || $harga === '' || $stok === '') {
        $pesan_form = '<div class="alert-danger">Semua field wajib diisi dengan benar.</div>';
    } elseif (!is_numeric($harga) || (float) $harga < 0 || !ctype_digit($stok)) {
        $pesan_form = '<div class="alert-danger">Harga harus angka positif dan stok harus bilangan bulat.</div>';
    } else {
        $gambar_baru = $produk['gambar']; // default: gambar lama tidak berubah

        // 2. Jika user upload gambar baru, upload lalu hapus gambar lama
        $file = $_FILES['gambar'] ?? [];
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $folder_upload = __DIR__ . '/../assets/images/';
            $upload = simpan_gambar($file, $folder_upload);
            if (!$upload['sukses']) {
                $pesan_form = '<div class="alert-danger">' . htmlspecialchars($upload['pesan']) . '</div>';
            } else {
                // Hapus file gambar lama jika ada
                if ($produk['gambar'] && file_exists($folder_upload . $produk['gambar'])) {
                    unlink($folder_upload . $produk['gambar']);
                }
                $gambar_baru = $upload['nama'];
            }
        }

        // 3. Update database (prepared statement)
        if ($pesan_form === '') {
            $sql = "UPDATE produk
                    SET nama_produk = ?, id_kategori = ?, deskripsi = ?,
                        harga = ?, stok = ?, gambar = ?, status = ?
                    WHERE id_produk = ?";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "sisdissi",
                $nama_produk, $id_kategori, $deskripsi,
                $harga, $stok, $gambar_baru, $status, $id_produk);
            mysqli_stmt_execute($stmt);

            header("Location: produk.php?pesan=ubah-sukses");
            exit;
        }
    }
}

$base       = '../';
$judul      = 'Edit Produk';
$menu_aktif = 'edit';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar-admin.php';
require_once __DIR__ . '/../components/topbar-admin.php';
?>

<main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">

    <div class="mx-auto max-w-3xl">

        <!-- Judul -->
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <i data-lucide="square-pen" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-extrabold text-slate-800">Edit Produk</h1>
                <p class="text-sm text-slate-500">Ubah informasi produk: <?= htmlspecialchars($produk['nama_produk']) ?></p>
            </div>
        </div>

        <?= $pesan_form ?>

        <div class="card p-5 sm:p-6">
            <form method="post" action="edit-produk.php?id=<?= $id_produk ?>" enctype="multipart/form-data" class="space-y-5">

                <!-- Nama produk -->
                <div>
                    <label for="nama_produk" class="label-field">Nama Produk</label>
                    <div class="relative">
                        <i data-lucide="tag" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="nama_produk" name="nama_produk" required
                               value="<?= htmlspecialchars($_POST['nama_produk'] ?? $produk['nama_produk']) ?>"
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
                            <?php while ($k = mysqli_fetch_assoc($kategori_result)):
                                $terpilih = (int) ($_POST['id_kategori'] ?? $produk['id_kategori']) === (int) $k['id_kategori'];
                            ?>
                                <option value="<?= $k['id_kategori'] ?>" <?= $terpilih ? 'selected' : '' ?>>
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
                              class="input-field resize-y"><?= htmlspecialchars($_POST['deskripsi'] ?? $produk['deskripsi']) ?></textarea>
                </div>

                <!-- Harga, stok, status -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <label for="harga" class="label-field">Harga (Rp)</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">Rp</span>
                            <input type="number" id="harga" name="harga" min="0" step="100" required
                                   value="<?= htmlspecialchars($_POST['harga'] ?? $produk['harga']) ?>"
                                   class="input-field pl-10">
                        </div>
                    </div>
                    <div>
                        <label for="stok" class="label-field">Stok</label>
                        <input type="number" id="stok" name="stok" min="0" step="1" required
                               value="<?= htmlspecialchars($_POST['stok'] ?? $produk['stok']) ?>"
                               class="input-field">
                    </div>
                    <div>
                        <label for="status" class="label-field">Status</label>
                        <select id="status" name="status" class="input-field">
                            <option value="aktif" <?= ($_POST['status'] ?? $produk['status']) === 'aktif' ? 'selected' : '' ?>>Aktif (tampil)</option>
                            <option value="nonaktif" <?= ($_POST['status'] ?? $produk['status']) === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (sembunyi)</option>
                        </select>
                    </div>
                </div>

                <!-- Gambar saat ini + ganti gambar -->
                <div>
                    <span class="label-field">Gambar Saat Ini</span>
                    <?php $gambar_lama = $produk['gambar'] ? $produk['gambar'] : 'default.png'; ?>
                    <div class="mb-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <img src="../assets/images/<?= htmlspecialchars($gambar_lama) ?>"
                             alt="Gambar produk" class="img-thumb">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-700"><?= htmlspecialchars($produk['gambar'] ?: 'default.png') ?></p>
                            <p class="text-xs text-slate-400">Gambar yang sedang digunakan produk ini</p>
                        </div>
                    </div>

                    <label for="gambar"
                           class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-primary-400 hover:bg-primary-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-primary-600 shadow-soft">
                            <i data-lucide="image-up" class="h-5 w-5"></i>
                        </span>
                        <span class="text-sm font-semibold text-slate-700">Ganti dengan gambar baru (opsional)</span>
                        <span class="text-xs text-slate-400">Format JPG, PNG, atau WEBP &middot; Maksimal 2 MB</span>
                        <input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp" class="sr-only">
                        <span id="nama-file-gambar" class="badge-success hidden"></span>
                    </label>
                    <p class="mt-1.5 text-xs text-slate-400">Kosongi jika tidak ingin mengganti gambar.</p>
                </div>

                <!-- Aksi -->
                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                    <button type="submit" class="btn-primary">
                        <i data-lucide="save" class="h-4 w-4"></i> Simpan Perubahan
                    </button>
                    <a href="produk.php" class="btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>
