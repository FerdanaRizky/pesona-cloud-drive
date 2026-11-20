# Pesona Cloud Drive

Aplikasi cloud storage multi-user mandiri berbasis CodeIgniter 4, PHP, dan MySQL. Penyimpanan file menggunakan filesystem lokal privat di luar document root.

## Stack
- PHP 8.2+
- CodeIgniter 4.7
- MySQL 8 / MariaDB kompatibel
- Bootstrap 5 responsive UI
- Private local storage

## Fitur
- Login, logout, setup administrator pertama, ubah password.
- Role Admin/User dengan pemeriksaan akses di server.
- Manajemen pengguna: buat akun, kuota, suspend/aktifkan, reset password.
- File manager: folder/subfolder, upload multi-file, download, rename, move, favorit.
- Trash: restore file/folder dan hapus permanen; penghapusan folder permanen menghapus isi turunannya.
- Pencarian nama file dan folder di seluruh drive milik akun.
- Preview gambar JPEG/PNG/GIF/WebP, PDF, dan video MP4/WebM/OGG.
- Link berbagi file viewer dengan token acak, hash token di database, expiry 1/7/30 hari atau tanpa expiry, dan pencabutan link.
- Riwayat aktivitas login/logout, upload, rename, move, favorit, trash/restore, purge, share, dan revoke. Admin dapat melihat aktivitas semua akun; user hanya miliknya.
- Private storage, pemeriksaan kepemilikan, kuota akun, CSRF, dan migration database.
- Update in-place aaPanel tanpa menghapus file .env, database, dan file pengguna.

## Update instalasi aaPanel yang sudah ada
Buat backup database dan private storage sebelum update. Jalankan sebagai root:

Perintah: curl -fsSL https://raw.githubusercontent.com/FerdanaRizky/pesona-cloud-drive/main/update-aapanel.sh | bash

Default source checkout: /opt/pesona-cloud-drive-source
Default app: /www/wwwroot/test-cloud.pesonawifi.my.id/app-src

Path khusus: SOURCE_DIR=/path/source APP_DIR=/path/app bash update-aapanel.sh

Updater menjalankan git pull, menyalin app/ dan public/assets/, Composer install, lalu php spark migrate. File .env, database, private storage, dan data pengguna tidak dihapus.

## Setelah update
- File Manager: /files
- Cari: /search
- Sampah: /trash
- Riwayat: /activity
- Buat link share dari menu aksi setiap file.
- Preview tersedia untuk gambar, PDF, dan video yang didukung browser.

## Migration
Migration tambahan:
- 2026-11-20-000002_CreateDriveFolders.php
- 2026-11-20-000003_AddDriveSearchAndActivityIndexes.php

Updater menjalankan migration otomatis. Untuk manual: php spark migrate

## Instalasi baru aaPanel
1. Buat website dan database MySQL di aaPanel.
2. Jalankan bash install-aapanel.sh sebagai root.
3. Atur document root ke app-src/public.
4. Pastikan PHP-FPM open_basedir mengizinkan direktori aplikasi dan private storage.
5. Aktifkan SSL, jalankan migration, lalu buka /setup.

Panduan: docs/INSTALL-AA-PANEL.md.

## Batasan dan keamanan
- Preview video saat ini mengirim file melalui response PHP biasa; untuk video besar sebaiknya memakai internal redirect (Nginx X-Accel-Redirect) agar hemat memori dan mendukung range request.
- Pencarian saat ini berbasis nama file/folder, bukan isi dokumen.
- Folder restore dilakukan bertahap dari parent ke child.
- Link share adalah bearer link: siapa pun yang memiliki URL dapat mengakses sampai dicabut/kedaluwarsa. Jangan bagikan file sensitif menggunakan link publik.
- Belum ada antivirus scanning, file versioning, kolaborasi editor, pencarian isi, thumbnail generator, object storage, atau automated integration tests.
- Jangan gunakan untuk data penting sebelum uji integrasi, backup/restore, batas upload PHP/Nginx, dan security review.