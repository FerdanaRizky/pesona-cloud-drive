#!/usr/bin/env bash
set -Eeuo pipefail
DB_NAME="$1"; DB_USER="$2"; DB_PASS="${PCD_DB_PASS:?Database password environment variable missing}"; SQL_FILE="$3"
[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
[[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]] || { echo "[ERROR] Nama database tidak valid."; exit 1; }
[[ -f "$SQL_FILE" && -r "$SQL_FILE" && "$SQL_FILE" == *.sql && -s "$SQL_FILE" ]] || { echo "[ERROR] File backup .sql tidak valid atau kosong."; exit 1; }
MYSQL_BIN=""
for candidate in /www/server/mysql/bin/mysql /usr/bin/mysql /usr/local/bin/mysql; do
  if [[ -x "$candidate" ]]; then MYSQL_BIN="$candidate"; break; fi
done
[[ -n "$MYSQL_BIN" ]] || { echo "[ERROR] MySQL client tidak ditemukan."; exit 1; }
CNF="$(mktemp)"; chmod 600 "$CNF"
trap 'rm -f "$CNF"' EXIT
PASS_ESC="$(printf '%s' "$DB_PASS" | sed 's/\\/\\\\/g; s/"/\\"/g; s/	/\\t/g')"
USER_ESC="$(printf '%s' "$DB_USER" | sed 's/\\/\\\\/g; s/"/\\"/g')"
printf '[client]\nuser="%s"\npassword="%s"\nhost="localhost"\ndefault-character-set=utf8mb4\n' "$USER_ESC" "$PASS_ESC" > "$CNF"
mysql_cmd() { "$MYSQL_BIN" --defaults-extra-file="$CNF" --batch --skip-column-names "$@"; }
echo "[INFO] Memeriksa koneksi MySQL..."
mysql_cmd -e "SELECT 1" >/dev/null || { echo "[ERROR] Koneksi MySQL gagal."; exit 1; }
DB_EXISTS="$(mysql_cmd -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}'")"
[[ "$DB_EXISTS" == "1" ]] || { echo "[ERROR] Database '$DB_NAME' belum dibuat di aaPanel."; exit 1; }
TABLE_COUNT="$(mysql_cmd -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}'")"
[[ "$TABLE_COUNT" =~ ^[0-9]+$ ]] || { echo "[ERROR] Gagal memeriksa tabel database."; exit 1; }
if (( TABLE_COUNT > 0 )); then
  echo "[ERROR] Database berisi $TABLE_COUNT tabel. Import dibatalkan agar data tidak tertimpa."
  exit 1
fi
if grep -Eiq '^[[:space:]]*(CREATE[[:space:]]+DATABASE|USE[[:space:]]+)' "$SQL_FILE"; then
  echo "[ERROR] Dump memuat CREATE DATABASE/USE. Ekspor ulang tanpa opsi tersebut."
  exit 1
fi
echo "[INFO] Mengimpor backup ke '$DB_NAME'..."
"$MYSQL_BIN" --defaults-extra-file="$CNF" --database="$DB_NAME" < "$SQL_FILE"
echo "[OK] Database berhasil dipulihkan."
