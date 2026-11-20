# Pesona Cloud Drive

Aplikasi cloud storage multi-user mandiri berbasis CodeIgniter 4, PHP, dan MySQL. Penyimpanan file menggunakan filesystem lokal privat di luar document root.

## Stack
- PHP 8.2+
- CodeIgniter 4.7
- MySQL 8 / MariaDB kompatibel
- Bootstrap 5 responsive UI
- Private local storage

## Fitur
- Login, logout, setup administrator pertama, ubah password, dan reset password mandiri melalui email (tautan sekali pakai, berlaku 60 menit).
- Role Admin/User dengan pemeriksaan akses di server.
- Manajemen pengguna: buat akun, kuota, suspend/aktifkan, reset password.
- File manager: folder/subfolder, upload multi-file, download, rename, move, favorit.
- Trash: restore file/folder dan hapus permanen; penghapusan folder permanen menghapus isi turunannya.
- Pencarian nama file dan folder di seluruh drive milik akun.
- Preview gambar JPEG/PNG/GIF/WebP, PDF, dan video MP4/WebM/OGG.
- Thumbnail gambar di daftar file serta preview dokumen Office (DOC/DOCX, XLS/XLSX, PPT/PPTX, ODT/ODS/ODP) melalui konversi PDF menggunakan LibreOffice.
- Manajemen link berbagi: daftar URL, ubah masa berlaku per link, cabut link satu per satu, atau cabut seluruh link file. Token diverifikasi dengan hash dan URL disimpan terenkripsi untuk dapat ditampilkan kembali.
- Audit log login/logout, upload, rename, move, favorit, trash/restore, purge, share/revoke, serta tindakan administrasi pengguna. Filter tanggal, jenis aktivitas, dan pengguna (admin).
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
- Manajemen link berbagi: /shares
- Buat link share dari menu aksi setiap file.
- Audit log dengan filter pengguna/tanggal: /activity
- Office preview membutuhkan LibreOffice di server (opsional): `apt install libreoffice`.
- Preview tersedia untuk gambar, PDF, dan video yang didukung browser.

## Konfigurasi email reset password
Atur parameter SMTP berikut di file .env pada root aplikasi. Gunakan password aplikasi/provider SMTP jika diwajibkan provider.

email.fromEmail = 'no-reply@domain-anda.com'
email.fromName = 'Pesona Cloud Drive'
email.SMTPHost = 'smtp.domain-anda.com'
email.SMTPUser = 'no-reply@domain-anda.com'
email.SMTPPass = 'PASSWORD_SMTP'
email.SMTPPort = 587
email.SMTPCrypto = 'tls'

Pastikan DNS domain pengirim memiliki SPF/DKIM/DMARC yang sesuai dan koneksi SMTP keluar diizinkan server. Reset memakai respons generik untuk mencegah enumerasi akun, membatasi 3 permintaan per jam per email/IP, menyimpan hash token saja, dan membatalkan token setelah digunakan. Tautan berlaku 60 menit.

## Migration
Migration tambahan:
- 2026-11-20-000002_CreateDriveFolders.php
- 2026-11-20-000003_AddDriveSearchAndActivityIndexes.php
- 2026-11-20-000004_AddEncryptedShareTokens.php
- 2026-11-21-000005_CreatePasswordResetTokens.php

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
- Konversi Office dilakukan saat preview pertama dan cache PDF; ukuran sumber dibatasi 50 MB. LibreOffice tidak disertakan otomatis oleh updater.
- Belum ada antivirus scanning, file versioning, kolaborasi editor, pencarian isi dokumen, object storage, atau automated integration tests.
- Jangan gunakan untuk data penting sebelum uji integrasi, backup/restore, batas upload PHP/Nginx, dan security review.