/* =========================================
   FILE: assets/js/script.js
   Fungsi : Efek kecil untuk kenyamanan user
   ========================================= */

// Tutup otomatis pesan alert setelah 5 detik
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-dismissible').forEach(function (elem) {
        setTimeout(function () {
            var tombol = elem.querySelector('.btn-close');
            if (tombol) {
                tombol.click();
            }
        }, 5000);
    });
});
