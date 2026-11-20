# Pesona Cloud Drive

Aplikasi cloud storage multi-user mandiri berbasis CodeIgniter 4, PHP, dan MySQL. Tidak menggunakan database atau kode dari proyek lain.

## Stack
- PHP 8.2+
- CodeIgniter 4.7
- MySQL 8 / MariaDB kompatibel
- Private local storage di luar web root
- Bootstrap 5 responsive UI

## Status pembangunan
Repository tahap fondasi. Modul lanjutan, wizard admin, sharing, trash/restore, preview, versioning, automated tests, dan security hardening masih perlu diselesaikan sebelum dinyatakan siap produksi.

## Instalasi aaPanel
1. Buat website dan database MySQL di aaPanel.
2. Clone repository.
3. Jalankan bash install-aapanel.sh sebagai root.
4. Atur document root domain ke folder public.
5. Aktifkan SSL dan jalankan migration.

Lihat docs/INSTALL-AA-PANEL.md.

Jangan gunakan untuk menyimpan data penting sebelum backup, pengujian, dan hardening selesai.
