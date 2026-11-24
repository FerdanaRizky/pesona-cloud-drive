## **1. Cara instalasi baru secara keseluruhan**

Ikuti urutan ini saat memasang Pesona Cloud Drive pada VPS baru.

### **Tahap 1 — Persiapkan server**

Rekomendasi:

- Ubuntu 22.04 atau 24.04.
- aaPanel.
- PHP 8.2 atau lebih baru (disarankan 8.3/8.4).
- MySQL atau MariaDB.
- Composer 2, Git, dan rsync.

Aktifkan ekstensi PHP:

`intl`, `mbstring`, `mysqli`, `fileinfo`, `curl`, dan `gd`.

Pastikan domain sudah mengarah ke IP VPS.

### **Tahap 2 — Buat website dan database**

Di aaPanel:

1. Buat website menggunakan domain yang akan dipakai.
2. Aktifkan PHP 8.2+.
3. Buat database MySQL.
4. Buat user database khusus aplikasi.
5. Catat nama database, username, dan password.

### **Tahap 3 — Jalankan installer**

Login SSH sebagai `root`, kemudian jalankan:

**Perintah instalasi baru**

```
git clone https://github.com/FerdanaRizky/pesona-cloud-drive.git
cd pesona-cloud-drive

chmod +x install-aapanel.sh
bash install-aapanel.sh
```

&#x20;Salin perintah

Installer akan meminta:

- Domain.
- Direktori aplikasi.
- Direktori source Git.
- Nama database.
- Username database.
- Password database.
- Direktori private storage.

Contoh struktur:

```
/www/wwwroot/domainanda.com/
├── app-src/
│   ├── app/
│   ├── public/
│   ├── vendor/
│   ├── writable/
│   ├── spark
│   └── .env

/www/wwwroot/domainanda.com-private/
└── file pengguna
```

Private storage harus berada di luar document root website.

### **Tahap 4 — Konfigurasi website aaPanel**

Atur document root menjadi:

```
/www/wwwroot/domainanda.com/app-src/public
```

Pastikan Nginx memiliki konfigurasi rewrite berikut di dalam blok `server`:

```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Jika sudah ada blok `location /`, edit blok tersebut. Jangan membuat duplikat.

Tes dan reload:

```
nginx -t && /etc/init.d/nginx reload
```

Aktifkan SSL Let's Encrypt.

### **Tahap 5 — Periksa open_basedir**

Installer versi terbaru akan mengatur `.user.ini` aplikasi secara otomatis.

Konfigurasi minimal harus mengizinkan:

```
open_basedir=/www/wwwroot/cloud.domainanda.com/app-src/:/www/wwwroot/cloud.domainanda.com-private/:/tmp/
```

Jika aaPanel memiliki Base directory limit atau Anti-XSS yang mengunci konfigurasi, tambahkan kedua direktori tersebut ke whitelist aaPanel.

### **Tahap 6 — Migration database dan setup admin**

Jalankan:

```
cd /www/wwwroot/domainanda.com/app-src
php spark migrate
```

Kemudian buka:

```
https://domainanda.com/setup
```

Buat akun Superadmin pertama, termasuk nomor WhatsApp yang diminta pada halaman setup.

Setelah itu login dan uji upload, preview, download, serta manajemen pengguna.

### **Tahap 7 — Perbaiki batas upload Nginx

Di aaPanel, buka:

Website → domain.com → Config

Tambahkan di dalam blok server HTTPS:
```
client_max_body_size 1100m;
client_body_timeout 3600s;
```

Tes dan reload Nginx:
```
nginx -t && /etc/init.d/nginx reload
```

### **Tahap 8 — Perbaiki konfigurasi PHP 8.4

Jalankan perintah berikut melalui SSH sebagai root. Perintah ini membuat backup terlebih dahulu dan hanya mengubah konfigurasi yang diperlukan.

```
PHPINI="/www/server/php/84/etc/php.ini"

cp -a "$PHPINI" "$PHPINI.backup-upload-$(date +%Y%m%d-%H%M%S)"

sed -i \
-e 's/^upload_max_filesize.*/upload_max_filesize = 1024M/' \
-e 's/^post_max_size.*/post_max_size = 1100M/' \
-e 's/^memory_limit.*/memory_limit = 512M/' \
-e 's/^max_execution_time.*/max_execution_time = 3600/' \
-e 's/^max_input_time.*/max_input_time = 3600/' \
-e 's/^max_file_uploads.*/max_file_uploads = 100/' \
"$PHPINI"

echo "===== HASIL KONFIGURASI ====="
grep -E '^(upload_max_filesize|post_max_size|memory_limit|max_execution_time|max_input_time|max_file_uploads)' "$PHPINI"
```
Restart PHP-FPM 8.4
```
/etc/init.d/php-fpm-84 restart
```

## Konfigurasi PHP-FPM untuk unduhan ZIP berukuran besar

Installer aaPanel otomatis menjalankan `tools/configure-php-fpm.sh` untuk PHP aaPanel yang dipilih. Konfigurasi ini:
- Mengatur `request_terminate_timeout = 1800s` pada konfigurasi global PHP-FPM dan pool aktif agar proses pembuatan ZIP tidak dihentikan pada batas 100 detik.
- Mengatur `max_execution_time = 3600` pada `php.ini`.
- Membuat backup file konfigurasi sebelum mengubahnya.
- Menguji konfigurasi PHP-FPM sebelum restart service.

Pengaturan ini hanya diterapkan pada versi PHP aaPanel yang dipilih installer. Installer tidak mengubah `pm.max_children` atau batas memori server secara otomatis.

Jika ingin menerapkan ulang secara manual pada server aaPanel:

```bash
PHP_PREFIX="/www/server/php/84"
bash /www/wwwroot/domainanda.com/app-src/tools/configure-php-fpm.sh "$PHP_PREFIX"
```

Ganti `84` sesuai versi PHP situs. Periksa hasilnya:

```bash
grep -nE '^[[:space:]]*request_terminate_timeout[[:space:]]*=' \
  "$PHP_PREFIX/etc/php-fpm.conf" "$PHP_PREFIX"/etc/php-fpm.d/*.conf 2>/dev/null
grep -n '^max_execution_time' "$PHP_PREFIX/etc/php.ini"
```

**Nginx:** jika konfigurasi situs memiliki `fastcgi_read_timeout` yang lebih rendah dari 300 detik, installer aaPanel kini menjalankan `tools/configure-nginx-timeouts.sh` untuk menemukan vhost domain, membuat backup, lalu mengatur `fastcgi_read_timeout 1860s` dan `send_timeout 1860s` pada blok PHP situs. Perubahan hanya dilakukan bila tepat satu file vhost dan blok PHP dapat dikenali; jika tidak, installer memberi peringatan agar administrator memeriksa konfigurasi secara manual. Skrip menjalankan `nginx -t` sebelum reload.

Catatan: batas 1800 detik mengurangi risiko proses ZIP diputus terlalu cepat, tetapi bukan jaminan semua ukuran ZIP akan selesai. Kapasitas disk sementara, ukuran file, kecepatan storage, dan timeout reverse proxy tetap berpengaruh.


====================================================================
Untuk konfig OTP
copy baris whatsapp api yang ada di dalam .env.example,
kemudian pasang di .env, 
isi sesuai konfigurasi baseURL, DeviceID, username, password sesuai dengan kondisi WA gateway anda

=====================================================================
## ** Jika mengalami eror
** Panduan mengatasi error yang mungkin muncul**

Berikut langkah penyelesaian yang sudah dimasukkan ke dokumentasi GitHub.

| Error                          | Penyebab dan penyelesaian                                       |
| ------------------------------ | --------------------------------------------------------------- |
| HTTP 500                       | Periksa log PHP-FPM, `open_basedir`, dan permission.            |
| `open_basedir restriction`     | Izinkan direktori `app-src/`, private storage, dan `/tmp/`.     |
| `Operation not permitted`      | Periksa `lsattr`; kemungkinan file `.user.ini` immutable.       |
| `/setup` menghasilkan 404      | Periksa document root dan Nginx rewrite.                        |
| Database connection error      | Periksa kredensial database di `.env`, lalu jalankan migration. |
| `Permission denied`            | Periksa kepemilikan `writable/` dan private storage.            |
| Composer gagal                 | Periksa versi PHP CLI, Composer, dan ekstensi PHP.              |
| Upload 413                     | Naikkan batas upload PHP dan `client_max_body_size` Nginx.      |
| `Module zip is already loaded` | Periksa konfigurasi PHP yang memuat ekstensi zip dua kali.      |

### **Contoh khusus: HTTP 500 karena immutable&#x20;**`.user.ini`

Jika muncul:


Operation not permitted

Periksa:

```
INI="/www/wwwroot/domainanda.com/app-src/public/.user.ini"
lsattr "$INI"
```

Jika terdapat huruf `i`, berarti file immutable.

Lepaskan sementara:

```
chattr -i "$INI"
```

Perbaiki konfigurasi:

```
cat > "$INI" <<'EOF'
open_basedir=/www/wwwroot/domainanda.com/app-src/:/www/wwwroot/domainanda.com-private/:/tmp/
EOF
```

Atur permission:

```
chmod 644 "$INI"
```

Setelah itu restart PHP-FPM melalui aaPanel dan tunggu hingga konfigurasi `.user.ini` terbaca.
```
/etc/init.d/php-fpm-84 restart
```
**Catatan:** Jangan menjalankan `chattr -i` jika atribut `i` tidak ditemukan. Jika tidak ada, periksa ACL dan konfigurasi PHP-FPM.


## **2. Cara update aplikasi setelah ada perbaikan baru**

Untuk server yang sudah terpasang, gunakan updater, bukan installer baru.

```
curl -fsSL https://raw.githubusercontent.com/FerdanaRizky/pesona-cloud-drive/main/update-aapanel.sh | bash
```

Updater akan memperbarui kode aplikasi, dependency, dan migration. File `.env`, database, private storage, serta file pengguna tidak seharusnya dihapus.

Sebelum update server produksi, tetap buat backup database dan private storage.



### Pemeriksaan server saat instalasi aaPanel

Installer juga:
- Memastikan PHP CLI versi 8.2+ memiliki ekstensi `mysqli`, `intl`, `mbstring`, `fileinfo`, dan `zip`.
- Memastikan kelas `ZipArchive` tersedia sebelum instalasi dinyatakan siap.
- Memeriksa direktori storage, cache, log, dan session dapat ditulis.
- Menampilkan ruang disk tersedia untuk ZIP sementara dan memberi peringatan jika kurang dari 5 GB.
- Menampilkan RAM fisik dan memberi peringatan jika kurang dari 1 GB.
- Mengatur timeout Nginx situs secara otomatis dengan backup dan validasi konfigurasi.

Pemeriksaan kapasitas adalah preflight, bukan jaminan bahwa setiap arsip dapat dibuat. Ruang disk yang diperlukan bergantung pada ukuran total file yang dipilih; sediakan ruang kosong setidaknya sebesar arsip ZIP yang akan dibuat, ditambah ruang operasional aplikasi.

### Perbaikan open_basedir aaPanel

Installer menjalankan `tools/configure-open-basedir.sh` untuk memastikan `public/.user.ini` mengizinkan akses ke:
- Root aplikasi CodeIgniter (termasuk `app/` dan `vendor/`)
- Direktori private storage
- `/tmp/`

Konfigurasi lama pada `public/.user.ini` dicadangkan sebelum directive `open_basedir` diganti. Installer memverifikasi bahwa direktori inti aplikasi tersedia dan allowlist sudah memuat root aplikasi serta private storage. Konfigurasi Nginx situs juga diatur agar nilai `PHP_ADMIN_VALUE open_basedir` tidak membatasi akses hanya ke folder `public/`.
