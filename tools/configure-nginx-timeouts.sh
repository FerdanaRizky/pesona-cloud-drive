#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN="${1:-}"
APP_ROOT="${2:-}"
PRIVATE_STORAGE="${3:-}"
TIMEOUT="${PCD_NGINX_TIMEOUT:-1860}"
[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || { echo "[ERROR] Domain tidak valid."; exit 1; }
[[ -n "$APP_ROOT" && -d "$APP_ROOT" ]] || { echo "[ERROR] App root tidak valid: $APP_ROOT"; exit 1; }
[[ -n "$PRIVATE_STORAGE" && -d "$PRIVATE_STORAGE" ]] || { echo "[ERROR] Private storage tidak valid: $PRIVATE_STORAGE"; exit 1; }
[[ "$TIMEOUT" =~ ^[0-9]+$ ]] && (( TIMEOUT >= 300 && TIMEOUT <= 3600 )) || { echo "[ERROR] Timeout Nginx harus 300-3600 detik."; exit 1; }
APP_ROOT="$(readlink -f "$APP_ROOT")"
PRIVATE_STORAGE="$(readlink -f "$PRIVATE_STORAGE")"
OPEN_BASEDIR="$APP_ROOT/:$PRIVATE_STORAGE/:/tmp/"
NGINX_BIN="$(command -v nginx || true)"
[[ -n "$NGINX_BIN" ]] || NGINX_BIN="/www/server/nginx/sbin/nginx"
[[ -x "$NGINX_BIN" ]] || { echo "[WARN] Nginx tidak ditemukan."; exit 0; }
VHOST_DIR="/www/server/panel/vhost/nginx"
[[ -d "$VHOST_DIR" ]] || { echo "[WARN] Direktori vhost aaPanel tidak ditemukan."; exit 0; }
mapfile -t MATCHES < <(grep -rlE "^[[:space:]]*server_name[[:space:]].*\b${DOMAIN//./\\.}\b" "$VHOST_DIR" --include='*.conf' 2>/dev/null || true)
if (( ${#MATCHES[@]} != 1 )); then echo "[WARN] Jumlah file vhost cocok: ${#MATCHES[@]}. Tidak ada perubahan."; exit 0; fi
CONF="${MATCHES[0]}"
ROLLBACK_COPY="$CONF.pcd-rollback.$$"
cp -a "$CONF" "$ROLLBACK_COPY"
python3 - "$CONF" "$TIMEOUT" "$OPEN_BASEDIR" <<'PY'
import pathlib, re, shutil, sys, time
path = pathlib.Path(sys.argv[1]); timeout = int(sys.argv[2]); open_basedir = sys.argv[3]
text = path.read_text()
pattern = re.compile(r'^[ \t]*location\b[^\n{]*\.php[^\n{]*\{', re.M)
matches = list(pattern.finditer(text))
if len(matches) != 1: print(f"[ERROR] Blok PHP ditemukan: {len(matches)}; tidak diubah."); sys.exit(2)
start = matches[0].start(); brace = text.find("{", start); depth = 0; end = None
for i in range(brace, len(text)):
    if text[i] == "{": depth += 1
    elif text[i] == "}":
        depth -= 1
        if depth == 0: end = i; break
if end is None: print("[ERROR] Blok PHP tidak seimbang."); sys.exit(2)
block = text[start:end]
for directive, value in (("fastcgi_read_timeout", f"{timeout}s"), ("send_timeout", f"{timeout}s")):
    line = re.compile(r"^[ \\t]*" + directive + r"\\s+[^;]+;", re.M)
    if line.search(block): block = line.sub(f"    {directive} {value};", block, count=1)
    else: block += f"\\n    {directive} {value};"
admin_line = re.compile(r"^[ \\t]*fastcgi_param\\s+PHP_ADMIN_VALUE\\s+[^;]+;", re.M)
admin_value = f'    fastcgi_param PHP_ADMIN_VALUE "open_basedir={open_basedir}";'
if admin_line.search(block): block = admin_line.sub(lambda _: admin_value, block, count=1)
else: block += "\\n" + admin_value
updated = text[:start] + block + text[end:]
if updated != text:
    backup = path.with_name(path.name + ".pcd-backup." + time.strftime("%Y%m%d%H%M%S")); shutil.copy2(path, backup); path.write_text(updated)
    print(f"[OK] Timeout dan open_basedir diperbarui. Backup: {backup}")
PY
if ! "$NGINX_BIN" -t; then cp -a "$ROLLBACK_COPY" "$CONF"; rm -f "$ROLLBACK_COPY"; echo "[ERROR] nginx -t gagal; vhost dikembalikan."; exit 1; fi
rm -f "$ROLLBACK_COPY"
if [[ -x /etc/init.d/nginx ]]; then /etc/init.d/nginx reload; elif command -v systemctl >/dev/null 2>&1; then systemctl reload nginx; else echo "[WARN] Reload Nginx tidak tersedia."; fi
echo "[OK] Nginx timeout dan open_basedir selesai untuk $DOMAIN."
