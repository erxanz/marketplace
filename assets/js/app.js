/* ============================================================
   FILE: assets/js/app.js
   Fungsi : Interaksi global aplikasi
            1. Render ikon Lucide
            2. Toggle elemen (sidebar & menu mobile) via data-toggle
            3. Tutup pesan alert otomatis / manual
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // 1. Render semua ikon Lucide (<i data-lucide="...">)
    if (window.lucide) {
        window.lucide.createIcons();
    }

    // 2. Toggle umum: <button data-toggle="#id-element">
    document.querySelectorAll('[data-toggle]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var target = document.querySelector(tombol.getAttribute('data-toggle'));
            if (!target) return;
            target.classList.toggle('hidden');

            // Jika sidebar terbuka di mobile, tampilkan backdrop-nya
            var backdrop = document.getElementById('sidebar-backdrop');
            if (backdrop && target.id === 'sidebar') {
                backdrop.classList.toggle('hidden', target.classList.contains('hidden'));
            }
        });
    });

    // Klik backdrop untuk menutup sidebar
    var sidebarBackdrop = document.getElementById('sidebar-backdrop');
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            var sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.add('hidden');
            sidebarBackdrop.classList.add('hidden');
        });
    }

    // 3. Tampilkan nama file terpilih pada input upload gambar
    var inputGambar = document.getElementById('gambar');
    var labelGambar = document.getElementById('nama-file-gambar');
    if (inputGambar && labelGambar) {
        inputGambar.addEventListener('change', function () {
            if (inputGambar.files && inputGambar.files.length > 0) {
                labelGambar.textContent = inputGambar.files[0].name;
                labelGambar.classList.remove('hidden');
            } else {
                labelGambar.classList.add('hidden');
            }
        });
    }

    // 4. Pesan alert: tutup otomatis setelah 5 detik
    document.querySelectorAll('.alert-dismissible').forEach(function (elem) {
        setTimeout(function () {
            elem.classList.add('hidden');
        }, 5000);

        var tombolTutup = elem.querySelector('[data-dismiss-alert]');
        if (tombolTutup) {
            tombolTutup.addEventListener('click', function () {
                elem.classList.add('hidden');
            });
        }
    });
});
