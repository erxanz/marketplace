/** @type {import('tailwindcss').Config} */
// ============================================================
// Konfigurasi Tailwind CSS
// content: seluruh file PHP/JS yang berisi class Tailwind,
//          supaya semua class terdeteksi dan masuk ke output.css
// ============================================================
module.exports = {
  content: [
    "./*.{php,html}",
    "./admin/*.php",
    "./proses/*.php",
    "./config/*.php",
    "./components/*.php",
    "./assets/js/*.js",
  ],
  // Class yang dibentuk dinamis oleh PHP (string concatenation)
  // sehingga tidak terdeteksi otomatis oleh Tailwind
  safelist: [
    "alert-success",
    "alert-danger",
    "alert-warning",
    "alert-info",
    "alert-dismissible",
    "badge-success",
    "badge-warning",
    "badge-slate",
    "badge-danger",
    "badge-category",
    "nav-link-active",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["Inter", "ui-sans-serif", "system-ui", "-apple-system", "sans-serif"],
      },
      colors: {
        // Primary: Emerald Green
        primary: {
          50: "#ecfdf5",
          100: "#d1fae5",
          200: "#a7f3d0",
          300: "#6ee7b7",
          400: "#34d399",
          500: "#10b981",
          600: "#059669",
          700: "#047857",
          800: "#065f46",
          900: "#064e3b",
        },
      },
      boxShadow: {
        soft: "0 1px 2px rgba(15,23,42,0.05), 0 8px 24px -16px rgba(15,23,42,0.18)",
        card: "0 1px 3px rgba(15,23,42,0.06), 0 16px 40px -24px rgba(15,23,42,0.25)",
        "btn-primary": "0 6px 16px -8px rgba(5,150,105,0.7)",
      },
    },
  },
  plugins: [],
};
