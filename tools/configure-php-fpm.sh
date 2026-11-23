#!/usr/bin/env bash
set -Eeuo pipefail

PHP_PREFIX="${1:-}"
TIMEOUT="${PCD_FPM_TIMEOUT:-1800}"

[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
[[ -n "$PHP_PREFIX" && -d "$PHP_PREFIX" ]] || { echo "[ERROR] PHP prefix tidak valid: $PHP_PREFIX"; exit 1; }
[[ "$TIMEOUT" =~ ^[0-9]+$ ]] && (( TIMEOUT >= 120 && TIMEOUT <= 3600 )) || {
  echo "[ERROR] Timeout harus 120-3600 detik."; exit 1;
}

CONF="$PHP_PREFIX/etc/php-fpm.conf"
[[ -f "$CONF" ]] || { echo "[ERROR] Konfigurasi PHP-FPM tidak ditemukan: $CONF"; exit 1; }
SERVICE="/etc/init.d/php-fpm-$(basename "$PHP_PREFIX")"
FPM_BIN="$PHP_PREFIX/sbin/php-fpm"

set_directive() {
  local file="$1" key="$2" value="$3" tmp
  [[ -f "$file" ]] || return 0
  tmp="$(mktemp)"
  awk -v key="$key" -v value="$value" '
    BEGIN { done=0 }
    $0 ~ "^[[:space:]]*;?[[:space:]]*" key "[[:space:]]*=" {
      if (!done) { print key " = " value; done=1 }
      next
    }
    { print }
    END { if (!done) print key " = " value }
  ' "$file" > "$tmp"
  if ! cmp -s "$file" "$tmp"; then
    cp -a "$file" "$file.pcd-backup.$(date +%Y%m%d%H%M%S)"
    cat "$tmp" > "$file"
    echo "[OK] $key disetel $value pada $file"
  fi
  rm -f "$tmp"
}

set_directive "$CONF" request_terminate_timeout "${TIMEOUT}s"

# Keep PHP's own execution limit above the FPM worker limit.
PHP_INI="$PHP_PREFIX/etc/php.ini"
if [[ -f "$PHP_INI" ]]; then
  set_directive "$PHP_INI" max_execution_time 3600
fi

# Pool-level directives override the global value, so align active pool files too.
if [[ -d "$PHP_PREFIX/etc/php-fpm.d" ]]; then
  while IFS= read -r -d '' pool; do
    set_directive "$pool" request_terminate_timeout "${TIMEOUT}s"
  done < <(find "$PHP_PREFIX/etc/php-fpm.d" -maxdepth 1 -type f -name '*.conf' ! -name '*.default' -print0)
fi

if [[ -x "$FPM_BIN" ]]; then
  "$FPM_BIN" -t -y "$CONF"
else
  echo "[ERROR] Binary PHP-FPM tidak ditemukan: $FPM_BIN"
  exit 1
fi

if [[ -x "$SERVICE" ]]; then
  "$SERVICE" restart
else
  echo "[ERROR] Service tidak ditemukan: $SERVICE"
  exit 1
fi

echo "===== PHP-FPM TIMEOUT ====="
grep -nE '^[[:space:]]*request_terminate_timeout[[:space:]]*=' "$CONF" "$PHP_PREFIX"/etc/php-fpm.d/*.conf 2>/dev/null || true
echo "[OK] PHP-FPM timeout disetel menjadi ${TIMEOUT} detik."
