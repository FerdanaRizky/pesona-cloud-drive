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


## Upload file berukuran besar (aaPanel)

Aplikasi menyediakan batas PHP hingga 2 GB melalui file `.user.ini`. Updater akan memasang file ini ke root aplikasi. Untuk mengatasi HTTP **413 Request Entity Too Large**, batas Nginx website juga harus dinaikkan karena Nginx menolak request sebelum mencapai PHP.

1. Buka aaPanel → Website → website Pesona Cloud Drive → Config.
2. Di dalam blok `server { ... }`, tambahkan:
   ```nginx
   client_max_body_size 2100m;
   ```
3. Simpan konfigurasi lalu lakukan Reload Nginx dari aaPanel.
4. Buka aaPanel → App Store → PHP yang dipakai website → Config → upload settings. Pastikan `upload_max_filesize = 2048M` dan `post_max_size = 2100M`, lalu restart PHP-FPM.
5. Periksa ruang disk direktori temporary PHP dan private storage. File upload sementara membutuhkan ruang tambahan.

Batas bawaan aplikasi adalah 2 GB per file. Untuk batas lebih besar, naikkan ketiga nilai `upload_max_filesize`, `post_max_size`, dan `client_max_body_size` secara konsisten (nilai post/Nginx sedikit lebih besar daripada ukuran file). Pengaturan Nginx wajib dilakukan di konfigurasi website aaPanel; file PHP `.user.ini` tidak dapat mengubah batas Nginx.

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

## Instalasi baru pada server baru

Panduan ini untuk memasang Pesona Cloud Drive dari awal pada VPS/server baru yang menggunakan Ubuntu dan aaPanel. Jalankan langkah-langkah berikut secara berurutan.

### 1. Siapkan server dan aaPanel
- Gunakan Ubuntu 22.04 atau 24.04 dan pasang aaPanel.
- Pastikan domain/subdomain sudah diarahkan ke IP publik server melalui DNS (record A).
- Buka panel aaPanel, lalu siapkan website untuk domain yang akan digunakan. Folder website boleh mengikuti default aaPanel.
- Pastikan port 80 dan 443 dapat diakses agar verifikasi domain dan SSL berjalan.

### 2. Siapkan PHP dan Composer
Di aaPanel → App Store, pasang PHP 8.2 atau lebih baru (disarankan PHP 8.3/8.4) dan aktifkan ekstensi:
- `intl`, `mbstring`, `mysqli`/MySQLnd
- `fileinfo`, `curl`, `gd`

Pasang/siapkan Composer 2, Git, dan rsync. Pastikan perintah `php` dan `composer` bisa dijalankan dari terminal SSH. PHP CLI harus memakai versi 8.2+ yang sama atau kompatibel dengan PHP-FPM website. Periksa dengan:

```bash
php -v
php -m
composer --version
git --version
rsync --version
```

Installer tidak memasang aaPanel, PHP, Composer, atau database server secara otomatis; komponen tersebut harus disiapkan terlebih dahulu.

### 3. Buat database
Di aaPanel → Database → Add database:
- Buat database MySQL/MariaDB untuk Pesona Cloud Drive.
- Buat/ gunakan user database khusus aplikasi.
- Simpan nama database, username, dan password. Data ini akan diminta oleh installer.
- Host database default installer adalah `localhost` dan port `3306`.

Jangan memakai password kosong.

### 4. Jalankan installer
Login ke server melalui SSH sebagai `root`, lalu jalankan:

```bash
curl -fsSL https://raw.githubusercontent.com/FerdanaRizky/pesona-cloud-drive/main/install-aapanel.sh | bash
```

Installer akan meminta informasi berikut:
- Domain aplikasi, misalnya `drive.domainanda.com`.
- Direktori aplikasi (default: `/www/wwwroot/DOMAIN/app-src`).
- Direktori source Git (default: `/opt/pesona-cloud-drive-source`).
- Nama database, username, dan password database.
- Direktori private storage (default: `/www/wwwroot/DOMAIN-private`).

Masukkan domain dan kredensial database yang sudah dibuat. Catat direktori aplikasi dan private storage yang ditampilkan installer. Private storage berada di luar document root agar file pengguna tidak dapat diakses langsung melalui URL.

### 5. Atur website aaPanel
Setelah installer selesai:
1. Buka Website → domain aplikasi → Site directory / Running directory.
2. Atur document root menjadi direktori aplikasi ditambah `/public`, contoh:
   `/www/wwwroot/drive.domainanda.com/app-src/public`.
3. Pastikan website menggunakan versi PHP yang sudah disiapkan.
4. Pastikan pengaturan `open_basedir` mengizinkan direktori aplikasi dan private storage. Jangan membatasi akses hanya ke folder `public`.
5. Aktifkan SSL Let's Encrypt untuk domain dan pastikan website dapat dibuka melalui HTTPS.
6. Pastikan direktori `writable` dan private storage dapat ditulis oleh user PHP/web server. Installer mencoba mengatur kepemilikan ke user `www` atau `www-data`; sesuaikan jika instalasi aaPanel menggunakan user lain.

### 6. Jalankan database migration dan buat admin pertama
Installer menyiapkan file aplikasi dan `.env`, tetapi database migration dijalankan setelah database tersedia. Melalui SSH:

```bash
cd /www/wwwroot/DOMAIN/app-src
php spark migrate
```

Ganti `DOMAIN` dengan domain yang dipakai atau gunakan direktori aplikasi khusus yang Anda masukkan saat instalasi.

Setelah migration berhasil, buka:

```
https://DOMAIN/setup
```

Ikuti halaman setup untuk membuat akun administrator pertama. Setelah admin berhasil dibuat, login dan periksa fitur manajemen pengguna serta upload file.



## Batasan dan keamanan
- Preview video saat ini mengirim file melalui response PHP biasa; untuk video besar sebaiknya memakai internal redirect (Nginx X-Accel-Redirect) agar hemat memori dan mendukung range request.
- Pencarian saat ini berbasis nama file/folder, bukan isi dokumen.
- Folder restore dilakukan bertahap dari parent ke child.
- Link share adalah bearer link: siapa pun yang memiliki URL dapat mengakses sampai dicabut/kedaluwarsa. Jangan bagikan file sensitif menggunakan link publik.
- Konversi Office dilakukan saat preview pertama dan cache PDF; ukuran sumber dibatasi 50 MB. LibreOffice tidak disertakan otomatis oleh updater.
- Belum ada antivirus scanning, file versioning, kolaborasi editor, pencarian isi dokumen, object storage, atau automated integration tests.
- Jangan gunakan untuk data penting sebelum uji integrasi, backup/restore, batas upload PHP/Nginx, dan security review.
