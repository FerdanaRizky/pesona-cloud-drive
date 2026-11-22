**1. Installer aaPanel**

Updated

File: `install-aapanel.sh`

- Mendeteksi atribut immutable (`chattr +i`) pada `.user.ini`.
- Melepaskan atribut immutable sementara ketika diperlukan.
- Memperbarui konfigurasi `open_basedir`.
- Mengembalikan atribut immutable jika sebelumnya memang aktif.
- Mempertahankan direktori yang sebelumnya diizinkan.
- Tidak mengubah `.user.ini` tingkat website aaPanel.
- Tidak menghentikan instalasi hanya karena konfigurasi tertentu tidak dapat diubah; installer memberikan peringatan.

  Commit: `2b27099`

  [Lihat perubahan installer](https://github.com/FerdanaRizky/pesona-cloud-drive/commit/2b27099bdda2f0809eef01352df1c02af539caf9?utm_source=chatgpt.com)

  **2. README**

  Updated

  Ditambahkan panduan instalasi, konfigurasi, dan troubleshooting.

  [Lihat perubahan README](https://github.com/FerdanaRizky/pesona-cloud-drive/commit/eae870e4d1b78b2ba222dab144fe633dacbc7be0?utm_source=chatgpt.com)

  **3. Dokumentasi aaPanel**

  Updated

  Ditambahkan panduan penyelesaian HTTP 500, `open_basedir`, permission, database, rewrite, Composer, dan upload.

  [Lihat perubahan dokumentasi](https://github.com/FerdanaRizky/pesona-cloud-drive/commit/e713252dbf87988f96fde879c9627d464b73afcd?utm_source=chatgpt.com)

## **2. Cara instalasi baru secara keseluruhan**

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
curl -fsSL https://raw.githubusercontent.com/FerdanaRizky/pesona-cloud-drive/main/install-aapanel.sh | bash
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
/www/wwwroot/cloud.domainanda.com/
├── app-src/
│   ├── app/
│   ├── public/
│   ├── vendor/
│   ├── writable/
│   ├── spark
│   └── .env

/www/wwwroot/cloud.domainanda.com-private/
└── file pengguna
```

Private storage harus berada di luar document root website.

### **Tahap 4 — Konfigurasi website aaPanel**

Installer terbaru akan mencoba mengatur document root vhost Nginx, rewrite `try_files`, `client_max_body_size 1100m`, dan `client_body_timeout 3600s` secara otomatis jika file vhost aaPanel ditemukan. Installer menjalankan `nginx -t` sebelum reload. Installer juga menyesuaikan batas upload pada `php.ini` aaPanel sesuai versi PHP CLI dan me-restart PHP-FPM jika service tersedia. Jika vhost atau service tidak ditemukan, installer menampilkan peringatan agar konfigurasi diperiksa manual.

Untuk pemeriksaan manual atau jika otomatisasi tidak dapat dilakukan, atur document root menjadi:

```
/www/wwwroot/cloud.domainanda.com/app-src/public
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


open_basedir=/www/wwwroot/cloud.domainanda.com/app-src/:/www/wwwroot/cloud.domainanda.com-private/:/tmp/

Jika aaPanel memiliki Base directory limit atau Anti-XSS yang mengunci konfigurasi, tambahkan kedua direktori tersebut ke whitelist aaPanel.

### **Tahap 6 — Migration database dan setup admin**

Jalankan:

```
cd /www/wwwroot/cloud.domainanda.com/app-src
php spark migrate
```

Kemudian buka:

```
https://cloud.domainanda.com/setup
```

Buat akun Superadmin pertama, termasuk nomor WhatsApp yang diminta pada halaman setup.

Setelah itu login dan uji upload, preview, download, serta manajemen pengguna.

## **3. Panduan mengatasi error yang mungkin muncul**

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
INI="/www/wwwroot/cloud.domainanda.com/app-src/public/.user.ini"
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
open_basedir=/www/wwwroot/cloud.domainanda.com/app-src/:/www/wwwroot/cloud.domainanda.com-private/:/tmp/
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

## **4. Cara update aplikasi setelah ada perbaikan baru**

Untuk server yang sudah terpasang, gunakan updater, bukan installer baru.

```
curl -fsSL https://raw.githubusercontent.com/FerdanaRizky/pesona-cloud-drive/main/update-aapanel.sh | bash
```

Updater akan memperbarui kode aplikasi, dependency, dan migration. File `.env`, database, private storage, serta file pengguna tidak seharusnya dihapus.

Sebelum update server produksi, tetap buat backup database dan private storage.

## **5. Perbaiki batas upload Nginx
Di aaPanel, buka:

Website → cloud.pesonawifi.my.id → Config

Tambahkan di dalam blok server HTTPS:
```
client_max_body_size 1100m;
client_body_timeout 3600s;
```

Tes dan reload Nginx:
```
nginx -t && /etc/init.d/nginx reload
```

## **6. Perbaiki konfigurasi PHP 8.4
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
