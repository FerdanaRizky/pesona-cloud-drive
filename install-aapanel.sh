#!/usr/bin/env bash
set -Eeuo pipefail

APP_NAME="Pesona Cloud Drive"
REPO="https://github.com/FerdanaRizky/pesona-cloud-drive.git"
DOMAIN_DEFAULT="test-cloud.pesonawifi.my.id"
APP_DIR_DEFAULT="/www/wwwroot/$DOMAIN_DEFAULT/app-src"
SOURCE_DIR_DEFAULT="/opt/pesona-cloud-drive-source"
DB_NAME_DEFAULT="pesona_cloud_drive"
STORAGE_DEFAULT="/www/wwwroot/$DOMAIN_DEFAULT-private"

echo "=== $APP_NAME aaPanel installer ==="
[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
[[ -r /dev/tty && -w /dev/tty ]] || { echo "[ERROR] Terminal interaktif diperlukan. Jalankan: curl -fsSL URL | bash"; exit 1; }

# Initialize every value before prompting; installer runs with nounset enabled.
DOMAIN="$DOMAIN_DEFAULT"
APP_DIR="$APP_DIR_DEFAULT"
SOURCE_DIR="$SOURCE_DIR_DEFAULT"
DB_NAME="$DB_NAME_DEFAULT"
DB_USER="pcd_user"
DB_PASS=""
STORAGE="$STORAGE_DEFAULT"

prompt() {
    local target="$1" label="$2" default="$3" value=""
    IFS= read -r -p "$label [$default]: " value </dev/tty || true
    printf -v "$target" '%s' "${value:-$default}"
}
secret() {
    local target="$1" label="$2" value=""
    IFS= read -r -s -p "$label: " value </dev/tty || true
    printf '\n' >/dev/tty
    printf -v "$target" '%s' "$value"
}

prompt DOMAIN "Domain" "$DOMAIN_DEFAULT"
prompt APP_DIR "Direktori aplikasi" "/www/wwwroot/$DOMAIN/app-src"
prompt SOURCE_DIR "Direktori source Git" "$SOURCE_DIR_DEFAULT"
prompt DB_NAME "Nama database MySQL" "$DB_NAME_DEFAULT"
prompt DB_USER "Username MySQL" "pcd_user"
secret DB_PASS "Password MySQL"
prompt STORAGE "Private storage path" "/www/wwwroot/$DOMAIN-private"

[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || { echo "[ERROR] Format domain tidak valid."; exit 1; }
[[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]] || { echo "[ERROR] Nama database hanya boleh huruf, angka, underscore."; exit 1; }
[[ -n "$DB_USER" && -n "$DB_PASS" ]] || { echo "[ERROR] Username dan password database wajib diisi."; exit 1; }
[[ "$APP_DIR" != "/" && "$SOURCE_DIR" != "/" && "$STORAGE" != "/" ]] || { echo "[ERROR] Direktori root tidak boleh digunakan."; exit 1; }

for bin in git composer rsync php; do
    command -v "$bin" >/dev/null 2>&1 || { echo "[ERROR] Perintah $bin tidak ditemukan. Pastikan tersedia di PATH."; exit 1; }
done
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' || { echo "[ERROR] PHP CLI minimal 8.2 diperlukan. Sesuaikan PHP CLI aaPanel."; exit 1; }
php -m | grep -qi '^mysqli$' || { echo "[ERROR] Ekstensi mysqli PHP CLI belum aktif."; exit 1; }
php -m | grep -qi '^intl$' || { echo "[ERROR] Ekstensi intl PHP CLI belum aktif."; exit 1; }
php -m | grep -qi '^mbstring$' || { echo "[ERROR] Ekstensi mbstring PHP CLI belum aktif."; exit 1; }

mkdir -p "$(dirname "$APP_DIR")" "$(dirname "$SOURCE_DIR")"
if [[ -d "$SOURCE_DIR/.git" ]]; then
    git -C "$SOURCE_DIR" pull --ff-only
else
    git clone "$REPO" "$SOURCE_DIR"
fi

if [[ ! -f "$APP_DIR/spark" ]]; then
    if [[ -e "$APP_DIR" && -n "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" ]]; then
        echo "[ERROR] APP_DIR sudah berisi file dan bukan instalasi CodeIgniter. Tidak ada file yang dihapus."
        exit 1
    fi
    composer create-project codeigniter4/appstarter "$APP_DIR" --no-interaction
fi

[[ -f "$APP_DIR/spark" && -d "$APP_DIR/app" && -d "$APP_DIR/public" ]] || { echo "[ERROR] Struktur CodeIgniter tidak lengkap."; exit 1; }
rsync -a "$SOURCE_DIR/app/" "$APP_DIR/app/"
mkdir -p "$APP_DIR/public/assets"
rsync -a "$SOURCE_DIR/public/assets/" "$APP_DIR/public/assets/"
cp -n "$SOURCE_DIR/.env.example" "$APP_DIR/.env.example"

cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader

if [[ ! -f .env ]]; then cp env .env; fi
sed -i -E '/^[[:space:]]*#?[[:space:]]*app\.baseURL[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*app\.forceGlobalSecureRequests[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*database\.default\.(hostname|database|username|password|DBDriver|port)[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*storage\.privatePath[[:space:]]*=/d' .env

# Escape values for CodeIgniter dotenv single-quoted values.
escape_env() {
    local value="$1"
    value="${value//\\/\\\\}"
    value="${value//\'/\\\'}"
    printf '%s' "$value"
}
DB_PASS_ESCAPED="$(escape_env "$DB_PASS")"
cat >> .env <<EOF

# Pesona Cloud Drive deployment settings
CI_ENVIRONMENT = production
app.baseURL = 'https://$DOMAIN/'
app.forceGlobalSecureRequests = true
database.default.hostname = 'localhost'
database.default.database = '$DB_NAME'
database.default.username = '$DB_USER'
database.default.password = '$DB_PASS_ESCAPED'
database.default.DBDriver = MySQLi
database.default.port = 3306
storage.privatePath = '$STORAGE'
EOF
chmod 640 .env
mkdir -p "$STORAGE" writable/cache writable/logs writable/session
chmod 750 "$STORAGE"
if id www >/dev/null 2>&1; then
    chown www:www .env
    chown -R www:www "$STORAGE" writable
elif id www-data >/dev/null 2>&1; then
    chown www-data:www-data .env
    chown -R www-data:www-data "$STORAGE" writable
else
    echo "[WARN] User web server tidak ditemukan; atur kepemilikan .env, storage, dan writable secara manual."
fi

echo
echo "[OK] Source Git : $SOURCE_DIR"
echo "[OK] Aplikasi   : $APP_DIR"
echo "[NEXT] Document root aaPanel: $APP_DIR/public"
echo "[NEXT] Pastikan database '$DB_NAME' sudah dibuat di aaPanel."
echo "[NEXT] Jalankan: cd '$APP_DIR' && php spark migrate"
echo "[NEXT] Setelah migration, buka https://$DOMAIN/setup untuk membuat admin pertama."
echo "[NOTE] Jangan gunakan untuk data penting sebelum pengujian backup dan keamanan selesai."
