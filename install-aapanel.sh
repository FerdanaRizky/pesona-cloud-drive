#!/usr/bin/env bash
set -Eeuo pipefail
APP_NAME="Pesona Cloud Drive"
REPO="https://github.com/FerdanaRizky/pesona-cloud-drive.git"
DOMAIN_DEFAULT="test-cloud.pesonawifi.my.id"
echo "=== $APP_NAME aaPanel installer ==="
[[ $EUID -eq 0 ]] || { echo "Jalankan sebagai root."; exit 1; }
command -v git >/dev/null || { apt-get update && apt-get install -y git curl unzip rsync; }
command -v rsync >/dev/null || apt-get install -y rsync
command -v composer >/dev/null || { echo "Composer belum terpasang. Install Composer dan ulangi."; exit 1; }
read -rp "Domain [$DOMAIN_DEFAULT]: " DOMAIN; DOMAIN=${DOMAIN:-$DOMAIN_DEFAULT}
read -rp "Direktori aplikasi [/www/wwwroot/$DOMAIN/app-src]: " APP_DIR; APP_DIR=${APP_DIR:-/www/wwwroot/$DOMAIN/app-src}
read -rp "Direktori source Git [/opt/pesona-cloud-drive-source]: " SOURCE_DIR; SOURCE_DIR=${SOURCE_DIR:-/opt/pesona-cloud-drive-source}
read -rp "Nama database [pesona_cloud_drive]: " DB_NAME; DB_NAME=${DB_NAME:-pesona_cloud_drive}
read -rp "Username MySQL: " DB_USER
read -rsp "Password MySQL: " DB_PASS; echo
read -rp "Private storage path [/www/wwwroot/$DOMAIN-private]: " STORAGE; STORAGE=${STORAGE:-/www/wwwroot/$DOMAIN-private}
mkdir -p "$(dirname "$APP_DIR")" "$(dirname "$SOURCE_DIR")"
if [[ -d "$SOURCE_DIR/.git" ]]; then git -C "$SOURCE_DIR" pull --ff-only; else git clone "$REPO" "$SOURCE_DIR"; fi
if [[ ! -f "$APP_DIR/spark" ]]; then
  [[ ! -e "$APP_DIR" || -z "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" ]] || { echo "Direktori APP_DIR tidak kosong dan bukan instalasi CI4. Hentikan untuk mencegah overwrite."; exit 1; }
  composer create-project codeigniter4/appstarter "$APP_DIR" --no-interaction
fi
rsync -a "$SOURCE_DIR/app/" "$APP_DIR/app/"
mkdir -p "$APP_DIR/public/assets"
rsync -a "$SOURCE_DIR/public/assets/" "$APP_DIR/public/assets/"
cp -n "$SOURCE_DIR/.env.example" "$APP_DIR/.env.example"
cp -n "$APP_DIR/env" "$APP_DIR/.env" 2>/dev/null || true
if [[ ! -f "$APP_DIR/.env" ]]; then cp "$SOURCE_DIR/.env.example" "$APP_DIR/.env"; fi
cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader
sed -i "s|^app.baseURL.*|app.baseURL = 'https://$DOMAIN/'|" .env
sed -i "s|^database.default.hostname.*|database.default.hostname = localhost|" .env
sed -i "s|^database.default.database.*|database.default.database = $DB_NAME|" .env
sed -i "s|^database.default.username.*|database.default.username = $DB_USER|" .env
sed -i "s|^database.default.password.*|database.default.password = '$DB_PASS'|" .env
sed -i "s|^database.default.DBDriver.*|database.default.DBDriver = MySQLi|" .env
sed -i "s|^storage.privatePath.*|storage.privatePath = '$STORAGE'|" .env
mkdir -p "$STORAGE" writable/cache writable/logs writable/session
chmod 750 "$STORAGE"
chown -R www:www "$STORAGE" writable 2>/dev/null || chown -R www-data:www-data "$STORAGE" writable
echo
echo "Source Git : $SOURCE_DIR"
echo "Aplikasi   : $APP_DIR"
echo "Document root aaPanel wajib diarahkan ke: $APP_DIR/public"
echo "Pastikan database sudah dibuat dan ekstensi PHP intl, mbstring, mysqli, fileinfo, curl aktif."
echo "Periksa konfigurasi .env, lalu jalankan: cd '$APP_DIR' && php spark migrate"
echo "Catatan: wizard admin awal dan hardening produksi belum selesai. Jangan publikasikan untuk pengguna umum."
