#!/usr/bin/env bash
set -Eeuo pipefail
APP_NAME="Pesona Cloud Drive"
REPO="https://github.com/FerdanaRizky/pesona-cloud-drive.git"
DOMAIN_DEFAULT="test-cloud.pesonawifi.my.id"
echo "=== $APP_NAME installer ==="
[[ $EUID -eq 0 ]] || { echo "Jalankan sebagai root."; exit 1; }
command -v git >/dev/null || { apt-get update && apt-get install -y git curl unzip; }
command -v composer >/dev/null || { echo "Composer belum terpasang. Install Composer dan ulangi."; exit 1; }
read -rp "Domain [$DOMAIN_DEFAULT]: " DOMAIN; DOMAIN=${DOMAIN:-$DOMAIN_DEFAULT}
read -rp "Direktori aplikasi [/www/wwwroot/$DOMAIN/app-src]: " APP_DIR; APP_DIR=${APP_DIR:-/www/wwwroot/$DOMAIN/app-src}
read -rp "Nama database [pesona_cloud_drive]: " DB_NAME; DB_NAME=${DB_NAME:-pesona_cloud_drive}
read -rp "Username MySQL: " DB_USER
read -rsp "Password MySQL: " DB_PASS; echo
read -rp "Private storage path [/www/wwwroot/$DOMAIN-private]: " STORAGE; STORAGE=${STORAGE:-/www/wwwroot/$DOMAIN-private}
mkdir -p "$(dirname "$APP_DIR")"
if [[ -e "$APP_DIR/.git" ]]; then git -C "$APP_DIR" pull --ff-only; else git clone "$REPO" "$APP_DIR"; fi
cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader
cp -n .env.example .env
sed -i "s|^app.baseURL.*|app.baseURL = 'https://$DOMAIN/'|" .env
sed -i "s|^database.default.database.*|database.default.database = $DB_NAME|" .env
sed -i "s|^database.default.username.*|database.default.username = $DB_USER|" .env
sed -i "s|^database.default.password.*|database.default.password = '$DB_PASS'|" .env
sed -i "s|^storage.privatePath.*|storage.privatePath = '$STORAGE'|" .env
mkdir -p "$STORAGE" writable/cache writable/logs writable/session
chmod 750 "$STORAGE"
chown -R www:www "$STORAGE" writable 2>/dev/null || chown -R www-data:www-data "$STORAGE" writable
echo "Source installed: $APP_DIR"
echo "Set aaPanel document root to: $APP_DIR/public"
echo "Create MySQL database first; enable PHP intl, mbstring, mysqli, fileinfo, curl and gd."
echo "Run migration after checking DB credentials: php spark migrate"
echo "Admin setup wizard and production hardening are pending in the next build stage."
