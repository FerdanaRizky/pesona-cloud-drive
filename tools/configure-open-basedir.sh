#!/usr/bin/env bash
set -Eeuo pipefail
APP_ROOT="${1:-}"
PRIVATE_STORAGE="${2:-}"
[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
[[ -n "$APP_ROOT" && -d "$APP_ROOT" ]] || { echo "[ERROR] APP_ROOT tidak valid."; exit 1; }
[[ -n "$PRIVATE_STORAGE" && -d "$PRIVATE_STORAGE" ]] || { echo "[ERROR] PRIVATE_STORAGE tidak valid."; exit 1; }
APP_ROOT="$(readlink -f "$APP_ROOT")"
PRIVATE_STORAGE="$(readlink -f "$PRIVATE_STORAGE")"
PUBLIC_DIR="$APP_ROOT/public"
[[ -f "$PUBLIC_DIR/index.php" ]] || { echo "[ERROR] public/index.php tidak ditemukan."; exit 1; }
INI="$PUBLIC_DIR/.user.ini"
ALLOW="$APP_ROOT/:$PRIVATE_STORAGE/:/tmp/"
mkdir -p "$PUBLIC_DIR"
touch "$INI"
TMP="$(mktemp)"
awk -v allow="$ALLOW" '
  BEGIN { done=0 }
  /^[[:space:]]*open_basedir[[:space:]]*=/ {
    if (!done) { print "open_basedir=" allow; done=1 }
    next
  }
  { print }
  END { if (!done) print "open_basedir=" allow }
' "$INI" > "$TMP"
if ! cmp -s "$INI" "$TMP"; then
  cp -a "$INI" "$INI.pcd-backup.$(date +%Y%m%d%H%M%S)"
  cat "$TMP" > "$INI"
fi
rm -f "$TMP"
chmod 644 "$INI"
if id www >/dev/null 2>&1; then chown www:www "$INI"
elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$INI"; fi
echo "[OK] public/.user.ini open_basedir: $ALLOW"
grep -n '^open_basedir=' "$INI"
