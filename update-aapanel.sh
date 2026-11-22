#!/usr/bin/env bash
set -Eeuo pipefail

REPO_DIR="${SOURCE_DIR:-/opt/pesona-cloud-drive-source}"
APP_DIR="${APP_DIR:-/www/wwwroot/test-cloud.pesonawifi.my.id/app-src}"
WEB_USER="${WEB_USER:-www}"
BACKUP_ROOT="${BACKUP_DIR:-/opt/pesona-cloud-drive-backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$BACKUP_ROOT/$STAMP"
ARCHIVE="$BACKUP_DIR/code-before-update.tar.gz"
ROLLBACK_SCRIPT="$REPO_DIR/rollback-aapanel.sh"
BACKUP_CREATED=0
UPDATE_STARTED=0

[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
for bin in git rsync composer php tar; do
  command -v "$bin" >/dev/null 2>&1 || { echo "[ERROR] $bin tidak ditemukan."; exit 1; }
done
[[ -d "$REPO_DIR/.git" ]] || { echo "[ERROR] Source repository tidak ditemukan: $REPO_DIR"; exit 1; }
[[ -f "$APP_DIR/spark" && -f "$APP_DIR/.env" ]] || { echo "[ERROR] Instalasi CodeIgniter atau .env tidak ditemukan: $APP_DIR"; exit 1; }

restore_code() {
  local archive="$1"
  echo "[ROLLBACK] Mengembalikan kode dari $archive"
  tar -xzf "$archive" -C "$APP_DIR"
  cd "$APP_DIR"
  composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction || true
  if id "$WEB_USER" >/dev/null 2>&1; then
    chown "$WEB_USER:$WEB_USER" "$APP_DIR/.env" 2>/dev/null || true
    chown -R "$WEB_USER:$WEB_USER" "$APP_DIR/writable" 2>/dev/null || true
  fi
}

on_error() {
  local code=$?
  trap - ERR
  echo "[ERROR] Update gagal (exit $code)."
  if [[ "$BACKUP_CREATED" -eq 1 && "$UPDATE_STARTED" -eq 1 ]]; then
    restore_code "$ARCHIVE" || echo "[CRITICAL] Pemulihan otomatis gagal. Backup: $ARCHIVE"
    echo "[INFO] Kode dicoba dikembalikan ke kondisi sebelum update."
    echo "[WARN] Jika migration database sudah berjalan, database tidak otomatis di-rollback."
  fi
  exit "$code"
}
trap on_error ERR

echo "=== Pesona Cloud Drive: update dengan version backup ==="
mkdir -p "$BACKUP_DIR"
PREVIOUS_COMMIT="$(git -C "$REPO_DIR" rev-parse HEAD)"
printf 'timestamp=%s\nprevious_commit=%s\napp_dir=%s\n' "$STAMP" "$PREVIOUS_COMMIT" "$APP_DIR" > "$BACKUP_DIR/metadata.txt"

echo "[INFO] Membuat snapshot kode sebelum update..."
tar -czf "$ARCHIVE" -C "$APP_DIR" app public/assets composer.json composer.lock .user.ini 2>/dev/null || {
  # .user.ini may not exist on older installations; retry without it.
  tar -czf "$ARCHIVE" -C "$APP_DIR" app public/assets composer.json composer.lock
}
BACKUP_CREATED=1
echo "[OK] Snapshot: $ARCHIVE"

echo "[INFO] Memperbarui source Git..."
git -C "$REPO_DIR" pull --ff-only
NEW_COMMIT="$(git -C "$REPO_DIR" rev-parse HEAD)"
printf 'updated_commit=%s\n' "$NEW_COMMIT" >> "$BACKUP_DIR/metadata.txt"
UPDATE_STARTED=1

echo "[INFO] Menyalin kode aplikasi dan aset..."
rsync -a "$REPO_DIR/app/" "$APP_DIR/app/"
if [[ -f "$REPO_DIR/.user.ini" ]]; then
  install -m 644 "$REPO_DIR/.user.ini" "$APP_DIR/.user.ini"
fi
mkdir -p "$APP_DIR/public/assets"
rsync -a "$REPO_DIR/public/assets/" "$APP_DIR/public/assets/"

echo "[INFO] Memvalidasi dependency..."
cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "[INFO] Menjalankan migration baru..."
php spark migrate

if id "$WEB_USER" >/dev/null 2>&1; then
  chown "$WEB_USER:$WEB_USER" "$APP_DIR/.env"
  chmod 640 "$APP_DIR/.env"
  chown -R "$WEB_USER:$WEB_USER" "$APP_DIR/writable"
fi

trap - ERR
echo "[OK] Update selesai."
echo "[VERSION] Sebelum: $PREVIOUS_COMMIT"
echo "[VERSION] Sesudah: $NEW_COMMIT"
echo "[BACKUP] $ARCHIVE"
echo "[ROLLBACK] Untuk kembali ke snapshot terakhir:"
echo "APP_DIR='$APP_DIR' BACKUP_DIR='$BACKUP_ROOT' bash '$ROLLBACK_SCRIPT'"
echo "[WARN] Snapshot ini mencakup kode aplikasi dan aset, bukan database atau file unggahan pengguna."
