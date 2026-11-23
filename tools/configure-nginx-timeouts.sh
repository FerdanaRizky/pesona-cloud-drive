#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN="${1:-}"
TIMEOUT="${PCD_NGINX_TIMEOUT:-1860}"
[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || { echo "[ERROR] Domain tidak valid."; exit 1; }
[[ "$TIMEOUT" =~ ^[0-9]+$ ]] && (( TIMEOUT >= 300 && TIMEOUT <= 3600 )) || {
  echo "[ERROR] Timeout Nginx harus 300-3600 detik."; exit 1;
}

NGINX_BIN="$(command -v nginx || true)"
[[ -n "$NGINX_BIN" ]] || NGINX_BIN="/www/server/nginx/sbin/nginx"
[[ -x "$NGINX_BIN" ]] || { echo "[WARN] Nginx tidak ditemukan; konfigurasi timeout dilewati."; exit 0; }

VHOST_DIR="/www/server/panel/vhost/nginx"
[[ -d "$VHOST_DIR" ]] || { echo "[WARN] Direktori vhost aaPanel tidak ditemukan: $VHOST_DIR"; exit 0; }
mapfile -t MATCHES < <(grep -rlE "^[[:space:]]*server_name[[:space:]].*\b${DOMAIN//./\\.}\b" "$VHOST_DIR" --include='*.conf' 2>/dev/null || true)
if (( ${#MATCHES[@]} != 1 )); then
  echo "[WARN] Ditemukan ${#MATCHES[@]} file vhost untuk $DOMAIN. Tidak ada konfigurasi Nginx yang diubah."
  exit 0
fi
CONF="${MATCHES[0]}"

python3 - "$CONF" "$TIMEOUT" <<'PY'
import pathlib, re, shutil, sys, time
path = pathlib.Path(sys.argv[1])
timeout = int(sys.argv[2])
text = path.read_text()
# Find PHP location blocks and their matching closing braces.
pattern = re.compile(r'^[ \t]*location\b[^\n{]*\.php[^\n{]*\{', re.M)
matches = list(pattern.finditer(text))
if not matches:
    print("[WARN] Blok location PHP tidak ditemukan; Nginx tidak diubah.")
    sys.exit(0)
start = matches[0].start()
brace = text.find("{", matches[0].start())
depth = 0
end = None
for i in range(brace, len(text)):
    if text[i] == "{": depth += 1
    elif text[i] == "}":
        depth -= 1
        if depth == 0:
            end = i
            break
if end is None:
    print("[ERROR] Blok location PHP tidak seimbang; konfigurasi tidak diubah.")
    sys.exit(2)
block = text[start:end]
for directive, value in (("fastcgi_read_timeout", f"{timeout}s"), ("send_timeout", f"{timeout}s")):
    line = re.compile(r'^[ \t]*' + directive + r'\s+[^;]+;', re.M)
    if line.search(block):
        block = line.sub(f"    {directive} {value};", block, count=1)
    else:
        block += f"\n    {directive} {value};"
updated = text[:start] + block + text[end:]
if updated != text:
    backup = path.with_name(path.name + ".pcd-backup." + time.strftime("%Y%m%d%H%M%S"))
    shutil.copy2(path, backup)
    path.write_text(updated)
    print(f"[OK] Timeout PHP Nginx disetel {timeout}s. Backup: {backup}")
else:
    print("[OK] Timeout Nginx sudah sesuai.")
PY

"$NGINX_BIN" -t
if [[ -x /etc/init.d/nginx ]]; then
  /etc/init.d/nginx reload
elif command -v systemctl >/dev/null 2>&1; then
  systemctl reload nginx
else
  echo "[WARN] Perintah reload Nginx tidak ditemukan."
fi
echo "[OK] Nginx timeout check selesai untuk $DOMAIN."
