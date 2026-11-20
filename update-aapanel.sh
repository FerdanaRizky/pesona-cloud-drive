#!/usr/bin/env bash
set -Eeuo pipefail

REPO_DIR="${SOURCE_DIR:-/opt/pesona-cloud-drive-source}"
APP_DIR="${APP_DIR:-/www/wwwroot/test-cloud.pesonawifi.my.id/app-src}"
WEB_USER="${WEB_USER:-www}"

[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
command -v git >/dev/null 2>&1 || { echo "[ERROR] Git tidak ditemukan."; exit 1; }
command -v rsync >/dev/null 2>&1 || { echo "[ERROR] rsync tidak ditemukan."; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "[ERROR] Composer tidak ditemukan."; exit 1; }
command -v php >/dev/null 2>&1 || { echo "[ERROR] PHP CLI tidak ditemukan."; exit 1; }

[[ -d "$REPO_DIR/.git" ]] || { echo "[ERROR] Source repository tidak ditemukan: $REPO_DIR"; exit 1; }
[[ -f "$APP_DIR/spark" && -f "$APP_DIR/.env" ]] || { echo "[ERROR] Instalasi CodeIgniter atau .env tidak ditemukan: $APP_DIR"; exit 1; }

echo "=== Pesona Cloud Drive: update in-place ==="
echo "[INFO] Memperbarui source Git..."
git -C "$REPO_DIR" pull --ff-only

echo "[INFO] Menyalin kode aplikasi dan aset (file .env, vendor, storage, dan data pengguna tidak disentuh)..."
rsync -a "$REPO_DIR/app/" "$APP_DIR/app/"
mkdir -p "$APP_DIR/public/assets"
rsync -a "$REPO_DIR/public/assets/" "$APP_DIR/public/assets/"

echo "[INFO] Memvalidasi dependency..."
cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "[INFO] Menjalankan migration baru..."
php spark migrate

if id "$WEB_USER" >/dev/null 2>&1; then
    chown "$WEB_USER":"$WEB_USER" "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
    chown -R "$WEB_USER":"$WEB_USER" "$APP_DIR/writable"
fi

echo "[OK] Update selesai."
echo "[INFO] .env, database, vendor-independent private storage, dan file pengguna tidak dihapus."
echo "[NEXT] Bersihkan cache browser bila tampilan lama masih muncul."
