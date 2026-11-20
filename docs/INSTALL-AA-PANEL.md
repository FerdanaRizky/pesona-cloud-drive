# Instalasi dan update aaPanel

## Persyaratan
- Ubuntu 22.04/24.04 dengan aaPanel
- PHP 8.2+ (disarankan PHP 8.3/8.4), Composer 2, Git, rsync
- Ekstensi PHP: intl, mbstring, mysqli/mysqlnd, fileinfo, curl, gd
- MySQL 8 atau MariaDB kompatibel
- DNS domain sudah mengarah ke IP server

CodeIgniter 4 memerlukan PHP 8.2+ serta ekstensi intl dan mbstring. Referensi: https://codeigniter.com/user_guide/intro/requirements.html

## Instalasi baru
1. Buat website di aaPanel.
2. Buat database MySQL dan user khusus aplikasi.
3. Pastikan Composer tersedia di CLI dan versi PHP CLI sesuai PHP website.
4. Jalankan `bash install-aapanel.sh`.
5. Atur document root website ke direktori `app-src/public`, bukan root repository.
6. Aktifkan SSL Let's Encrypt dan paksa HTTPS.
7. Pastikan pengaturan PHP open_basedir mengizinkan seluruh direktori aplikasi, bukan hanya folder `public`. Minimal izinkan `app-src/`, `app-src/public/`, dan private storage. Pada aaPanel, pengaturan ini berkaitan dengan **Directory → Anti-XSS attack (Base directory limit)**.
8. Setelah database siap, jalankan `php spark migrate`, lalu buka `/setup` untuk membuat administrator pertama.

## Update aplikasi tanpa instal ulang
Jalankan sebagai root:

```bash
curl -fsSL https://raw.githubusercontent.com/FerdanaRizky/pesona-cloud-drive/main/update-aapanel.sh | bash
```

Default path:
- Source Git: `/opt/pesona-cloud-drive-source`
- Aplikasi: `/www/wwwroot/test-cloud.pesonawifi.my.id/app-src`

Untuk path khusus:

```bash
SOURCE_DIR=/path/source APP_DIR=/path/app bash update-aapanel.sh
```

Updater melakukan `git pull --ff-only`, menyalin `app/` dan `public/assets/`, menjalankan Composer, lalu `php spark migrate`. Updater tidak menghapus atau menimpa `.env`, database, folder private storage, dan file pengguna.

Sebelum setiap update pada server produksi, tetap disarankan membuat backup database dan private storage.

## Catatan fitur dan keamanan
- Role admin diperiksa di server melalui filter, bukan hanya menyembunyikan menu.
- File disimpan di private storage dan setiap operasi memeriksa kepemilikan user.
- Upload memperhitungkan kuota akun dengan penguncian transaksi.
- Pengguna baru dan pengguna yang password-nya direset diwajibkan mengganti password sementara.
- File di sampah masih dihitung dalam kuota sampai dihapus permanen. Restore tidak mengubah pemakaian kuota; penghapusan permanen mengurangi kuota dan menghapus file fisik.
- Folder dapat dipulihkan bertahap dari parent ke child. Penghapusan permanen folder menghapus seluruh isi turunannya.
- Link berbagi menggunakan token acak yang diverifikasi dengan hash dan disimpan terenkripsi agar URL bisa ditampilkan kembali. Siapa pun yang memiliki link dapat mengakses file sampai link dicabut atau kedaluwarsa.
- Preview gambar, PDF, video, dan dokumen Office (melalui LibreOffice) didukung. Untuk file besar, aktifkan pengiriman melalui Nginx X-Accel-Redirect sesuai langkah di bawah.
- Pencarian hanya berdasarkan nama file/folder. Riwayat aktivitas dibatasi 250 catatan terbaru pada tampilan.
- Fitur ini belum dinyatakan siap produksi sebelum pengujian integrasi, backup/restore, batas ukuran upload PHP/Nginx, dan security review selesai.


## Optimasi streaming Nginx (opsional, direkomendasikan untuk file besar)

Fitur ini membuat PHP tetap memeriksa login, kepemilikan file, token share, dan masa berlaku, tetapi Nginx yang mengirimkan byte file. Nginx menangani HTTP Range untuk video dan PDF tanpa membaca seluruh file ke memori PHP.

1. Buka konfigurasi Nginx website di aaPanel.
2. Tambahkan isi file `docs/nginx-x-accel.conf` ke dalam blok `server { ... }` website. Sesuaikan kedua path `alias` jika direktori aplikasi atau private storage berbeda.
3. Pastikan kedua lokasi tetap memiliki directive `internal;`. Jangan menghapusnya dan jangan membuat lokasi tersebut dapat diakses langsung dari browser.
4. Tambahkan baris berikut ke `.env` aplikasi:

   ```ini
   storage.xAccelEnabled = true
   ```

5. Simpan konfigurasi, lalu test dan reload Nginx dari aaPanel.
6. Uji preview gambar/PDF, seek video, download login, serta download melalui tautan berbagi.

Jika belum menambahkan lokasi Nginx, biarkan `storage.xAccelEnabled` tidak diaktifkan atau bernilai `false`. Aplikasi akan memakai pengiriman PHP sebagai fallback. Jangan aktifkan sebelum path `alias` benar dan lokasi internal telah dites.

Contoh path default:
- File pengguna: `/www/wwwroot/test-cloud.pesonawifi.my.id-private/`
- Cache PDF Office: `/www/wwwroot/test-cloud.pesonawifi.my.id/app-src/writable/cache/office-preview/`

Nginx harus memiliki izin baca ke kedua direktori tersebut. `X-Accel-Redirect` hanya digunakan untuk file yang berada di dalam salah satu root tersebut; file di luar root akan memakai fallback PHP.
