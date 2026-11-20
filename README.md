# Pesona Cloud Drive

Aplikasi cloud storage multi-user mandiri berbasis CodeIgniter 4, PHP, dan MySQL. Tidak menggunakan database atau kode dari proyek lain.

## Stack
- PHP 8.2+
- CodeIgniter 4.7
- MySQL 8 / MariaDB kompatibel
- Private local storage di luar web root
- Bootstrap 5 responsive UI

## Fitur yang sudah tersedia
- Login, logout, setup administrator pertama, dan ubah password.
- Role administrator dan pengguna biasa dengan pemeriksaan akses di server.
- Admin dapat membuat pengguna, mengatur kuota, menangguhkan akun, dan mereset password sementara.
- File manager pribadi dengan folder/subfolder, upload multi-file, download, rename, move, favorit, dan soft-delete file.
- Penyimpanan privat di luar document root; file tidak diakses langsung melalui URL publik.
- Dashboard penggunaan kuota per akun.
- Script update aaPanel yang mengambil commit terbaru dan mempertahankan .env, database, serta private storage.

## Update instalasi aaPanel yang sudah ada
Jalankan sebagai root di server:

```bash
curl -fsSL https://raw.githubusercontent.com/FerdanaRizky/pesona-cloud-drive/main/update-aapanel.sh | bash
```

Script menggunakan source checkout di `/opt/pesona-cloud-drive-source` dan instalasi di `/www/wwwroot/test-cloud.pesonawifi.my.id/app-src`. Jika lokasi Anda berbeda, jalankan dengan variabel lingkungan:

```bash
SOURCE_DIR=/path/source APP_DIR=/path/app bash update-aapanel.sh
```

Update menyalin folder `app/` dan `public/assets/`, memasang dependency, lalu menjalankan migration baru. File `.env`, database, dan private storage tidak dihapus.

## Migration
Setelah update, script menjalankan:

```bash
php spark migrate
```

Migration baru menambahkan tabel `drive_folders`; migration lama tidak perlu diulang.

## Instalasi baru aaPanel
1. Buat website dan database MySQL di aaPanel.
2. Clone repository.
3. Jalankan `bash install-aapanel.sh` sebagai root.
4. Atur document root domain ke folder `public`.
5. Pastikan PHP-FPM open_basedir mengizinkan direktori aplikasi CodeIgniter (`app-src`) dan private storage di luar `public`.
6. Aktifkan SSL, jalankan migration, lalu buka `/setup`.

Lihat [docs/INSTALL-AA-PANEL.md](docs/INSTALL-AA-PANEL.md).

## Batasan tahap ini
Trash/restore dan penghapusan permanen, sharing/link publik, preview dokumen, pencarian global, versioning, audit log UI, antivirus scanning, object storage, dan automated integration tests belum selesai. Folder yang memiliki isi belum dapat dihapus. Belum dinyatakan siap produksi untuk data penting sebelum uji integrasi, backup/restore, batas ukuran upload, dan security review.

## Keamanan
- Gunakan HTTPS.
- Gunakan password kuat dan akun database khusus dengan hak minimum.
- Simpan private storage di luar document root.
- Jangan pernah commit `.env`, password, atau file pengguna.
- Backup database dan private storage secara terpisah dan uji proses restore.
