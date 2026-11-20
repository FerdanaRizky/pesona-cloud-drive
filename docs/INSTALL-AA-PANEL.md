# Instalasi aaPanel

## Persyaratan
- Ubuntu 22.04/24.04 dengan aaPanel
- PHP 8.2+ (disarankan PHP 8.3/8.4), Composer 2, Git
- Ekstensi PHP: intl, mbstring, mysqli/mysqlnd, fileinfo, curl, gd, json
- MySQL 8 atau MariaDB kompatibel
- DNS domain sudah mengarah ke IP server

CodeIgniter 4 memerlukan PHP 8.2+ serta ekstensi intl dan mbstring. Referensi: https://codeigniter.com/user_guide/intro/requirements.html

## Langkah
1. Buat website test-cloud.pesonawifi.my.id di aaPanel.
2. Buat database MySQL dan user khusus aplikasi.
3. Pastikan Composer tersedia di CLI dan versi PHP CLI sesuai PHP website.
4. Jalankan bash install-aapanel.sh.
5. Atur document root website ke direktori app-src/public, bukan root repository.
6. Aktifkan SSL Let's Encrypt dan paksa HTTPS.
7. Setelah database siap, jalankan php spark migrate.

## Catatan
Installer tahap awal menyiapkan source, dependency, environment, dan private storage. Setup admin awal, validasi migration, dan hardening produksi akan ditambahkan pada tahap berikutnya. Jangan buka layanan untuk pengguna umum sebelum setup admin dan pengujian selesai.
