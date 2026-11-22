#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/www/wwwroot/test-cloud.pesonawifi.my.id/app-src}"
BACKUP_ROOT="${BACKUP_DIR:-/opt/pesona-cloud-drive-backups}"
WEB_USER="${WEB_USER:-www}"

[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
command -v tar >/dev/null 2>&1 || { echo "[ERROR] tar tidak ditemukan."; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "[ERROR] Composer tidak ditemukan."; exit 1; }
[[ -d "$APP_DIR" ]] || { echo "[ERROR] APP_DIR tidak ditemukan: $APP_DIR"; exit 1; }
[[ -d "$BACKUP_ROOT" ]] || { echo "[ERROR] Folder backup belum ada: $BACKUP_ROOT"; exit 1; }

if [[ -n "${VERSION:-}" ]]; then
  SNAPSHOT="$BACKUP_ROOT/$VERSION"
else
  SNAPSHOT="$(find "$BACKUP_ROOT" -mindepth 2 -maxdepth 2 -type f -name 'code-before-update.tar.gz' -printf '%h\n' | sort | tail -n 1)"
fi
ARCHIVE="$SNAPSHOT/code-before-update.tar.gz"
[[ -f "$ARCHIVE" ]] || { echo "[ERROR] Snapshot tidak ditemukan: $ARCHIVE"; echo "[INFO] Snapshot tersedia:"; find "$BACKUP_ROOT" -mindepth 2 -maxdepth 2 -type f -name 'code-before-update.tar.gz' -printf '%h\n' | sort; exit 1; }

echo "=== Pesona Cloud Drive: rollback kode ==="
echo "[INFO] Snapshot: $SNAPSHOT"
[[ "${CONFIRM:-}" == "yes" ]] || {
  echo "[PERINGATAN] Ini mengganti kode aplikasi dengan snapshot terpilih."
  echo "Perintah konfirmasi:"
  echo "APP_DIR='$APP_DIR' BACKUP_DIR='$BACKUP_ROOT' VERSION='$(basename "$SNAPSHOT")' CONFIRM=yes bash '$0'"
  exit 0
}

tar -xzf "$ARCHIVE" -C "$APP_DIR"
cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
if id "$WEB_USER" >/dev/null 2>&1; then
  chown "$WEB_USER:$WEB_USER" "$APP_DIR/.env" 2>/dev/null || true
  chmod 640 "$APP_DIR/.env"
  chown -R "$WEB_USER:$WEB_USER" "$APP_DIR/writable" 2>/dev/null || true
fi

echo "[OK] Kode berhasil dikembalikan ke snapshot $(basename "$SNAPSHOT")."
echo "[WARN] Database tidak di-rollback. Jika update menjalankan migration yang tidak kompatibel dengan kode lama, pulihkan database dari backup database yang sesuai."
