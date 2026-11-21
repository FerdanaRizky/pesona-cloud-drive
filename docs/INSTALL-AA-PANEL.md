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
6. **Aktifkan URL rewrite CodeIgniter 4 di konfigurasi Nginx website.** Di dalam blok `server { ... }`, pastikan ada:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```
   Jika aaPanel sudah membuat blok `location /`, edit blok yang ada; jangan membuat duplikat `location /`. Test dan reload Nginx setelah menyimpan. Tanpa rewrite ini, URL seperti `/setup`, `/login`, dan `/dashboard` akan dicari sebagai file/folder fisik dan gagal.
7. Aktifkan SSL Let's Encrypt dan paksa HTTPS.
8. Pastikan pengaturan PHP open_basedir mengizinkan seluruh direktori aplikasi, bukan hanya folder `public`. Minimal izinkan `app-src/`, `app-src/public/`, dan private storage. Pada aaPanel, pengaturan ini berkaitan dengan **Directory → Anti-XSS attack (Base directory limit)**.
9. Pastikan file `.env` dapat dibaca user PHP-FPM (umumnya `www`). Installer mengatur owner `www:www` dan permission `640`.
11. Installer juga membuat/menyesuaikan `app-src/public/.user.ini` agar `open_basedir` mengizinkan seluruh direktori aplikasi (`app-src/`) dan private storage, sekaligus mempertahankan path yang sebelumnya diizinkan aaPanel. Ini diperlukan karena CodeIgniter memuat file dari `app/`, `vendor/`, dan `.env`, bukan hanya dari `public/`.
12. Jika aaPanel mengunci `open_basedir` melalui konfigurasi PHP-FPM tingkat pool atau pengaturan Anti-XSS yang menimpa `.user.ini`, sesuaikan whitelist melalui Website → Site directory → Anti-XSS / Base directory limit. Pastikan minimal mencakup direktori `app-src/`, private storage, dan `/tmp/`. Jangan hanya mengizinkan `app-src/public/`.
13. Setelah instalasi, verifikasi nilai `open_basedir` dari PHP-FPM (bukan hanya PHP CLI) dan buka `/setup`. Jika masih muncul pesan `open_basedir restriction`, periksa file `.user.ini` serta konfigurasi PHP-FPM website.
10. Setelah database siap, jalankan `php spark migrate`, lalu buka `/setup` untuk membuat administrator pertama.

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


## Pemecahan masalah

### HTTP 500 karena open_basedir

Periksa log PHP-FPM website. Jika ada pesan `open_basedir restriction` yang hanya mengizinkan `public/` dan `/tmp/`, PHP tidak dapat membaca `app/Config/Paths.php`, `vendor/`, atau `.env`.

Periksa konfigurasi:

```bash
APP="/www/wwwroot/DOMAIN/app-src"
STORAGE="/www/wwwroot/DOMAIN-private"
cat "$APP/public/.user.ini"
```

Pastikan direktori aplikasi dan private storage termasuk dalam daftar, misalnya:

```ini
open_basedir=/www/wwwroot/DOMAIN/app-src/:/www/wwwroot/DOMAIN-private/:/tmp/
```

Jika file tidak bisa ditulis dan muncul `Operation not permitted`, periksa immutable flag:

```bash
INI="$APP/public/.user.ini"
lsattr "$INI"
```

Huruf `i` pada atribut file berarti immutable. Hanya jika huruf tersebut ada, jalankan:

```bash
chattr -i "$INI"
cat > "$INI" <<EOF
open_basedir=$APP/:$STORAGE/:/tmp/
EOF
chmod 644 "$INI"
```

Jika tidak ada flag `i`, periksa ACL (`getfacl -p "$INI"`) dan izin direktori induk. Periksa juga apakah PHP-FPM menetapkan nilai paksa:

```bash
grep -RniE 'open_basedir|php_admin_value' /www/server/php/84/etc 2>/dev/null
```

Jika ada batasan di PHP-FPM atau aaPanel Website → Site directory → Anti-XSS/Base directory limit, masukkan seluruh direktori aplikasi, private storage, dan `/tmp/` ke whitelist. Konfigurasi PHP CLI yang menampilkan `open_basedir => no value` tidak menjamin PHP-FPM website memiliki nilai yang sama. Restart PHP-FPM dari aaPanel; cache `.user.ini` dapat memerlukan waktu sampai 5 menit.

### Checklist diagnosis singkat

| Gejala | Pemeriksaan/perbaikan |
|---|---|
| `/setup` 404 | Document root harus `app-src/public`; cek Nginx `try_files` |
| HTTP 500 | Baca log PHP-FPM; periksa open_basedir dan permission |
| Database gagal | Periksa kredensial `.env`, database/user, lalu `php spark migrate` |
| `Operation not permitted` pada `.user.ini` | Periksa `lsattr`; jika ada `i`, gunakan `chattr -i` sementara |
| Upload 413 | Naikkan limit Nginx dan PHP |
| Composer gagal | Cocokkan PHP CLI 8.2+ dan ekstensi wajib; gunakan Composer 2 |
| Peringatan zip dimuat dua kali | Hilangkan deklarasi ekstensi zip yang duplikat; biasanya bukan penyebab 500 |

Jangan menghapus direktori aplikasi, `.env`, database, atau private storage untuk memperbaiki masalah ini. Simpan backup database dan private storage sebelum update/repair.
