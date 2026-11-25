#!/usr/bin/env bash
set -Eeuo pipefail

APP_NAME="Pesona Cloud Drive"
REPO="https://github.com/FerdanaRizky/pesona-cloud-drive.git"
DOMAIN_DEFAULT="domain-anda.com"
APP_DIR_DEFAULT="/www/wwwroot/$DOMAIN_DEFAULT/app-src"
SOURCE_DIR_DEFAULT="/opt/pesona-cloud-drive-source"
DB_NAME_DEFAULT="pesona_cloud_drive"
STORAGE_DEFAULT="/www/wwwroot/$DOMAIN_DEFAULT-private"

echo "=== $APP_NAME aaPanel installer ==="
[[ $EUID -eq 0 ]] || { echo "[ERROR] Jalankan sebagai root."; exit 1; }
[[ -r /dev/tty && -w /dev/tty ]] || { echo "[ERROR] Terminal interaktif diperlukan."; exit 1; }

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

DOMAIN="$DOMAIN_DEFAULT"
APP_DIR="$APP_DIR_DEFAULT"
SOURCE_DIR="$SOURCE_DIR_DEFAULT"
DB_NAME="$DB_NAME_DEFAULT"
DB_USER="pcd_user"
DB_PASS=""
STORAGE="$STORAGE_DEFAULT"

prompt DOMAIN "Domain" "$DOMAIN_DEFAULT"
prompt APP_DIR "Direktori aplikasi" "/www/wwwroot/$DOMAIN/app-src"
prompt SOURCE_DIR "Direktori source Git" "$SOURCE_DIR_DEFAULT"
prompt DB_NAME "Nama database MySQL" "$DB_NAME_DEFAULT"
prompt DB_USER "Username MySQL" "pcd_user"
secret DB_PASS "Password MySQL"
prompt STORAGE "Private storage path" "/www/wwwroot/$DOMAIN-private"

[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || { echo "[ERROR] Format domain tidak valid."; exit 1; }
[[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]] || { echo "[ERROR] Nama database tidak valid."; exit 1; }
[[ -n "$DB_USER" && -n "$DB_PASS" ]] || { echo "[ERROR] Username dan password database wajib diisi."; exit 1; }
[[ "$APP_DIR" != "/" && "$SOURCE_DIR" != "/" && "$STORAGE" != "/" ]] || { echo "[ERROR] Direktori root tidak boleh digunakan."; exit 1; }

for bin in git composer rsync; do
    command -v "$bin" >/dev/null 2>&1 || { echo "[ERROR] Perintah $bin tidak ditemukan di PATH."; exit 1; }
done

# Select PHP CLI >= 8.2 with the extensions required by the application.
PHP_CANDIDATES=()
# Prefer aaPanel-managed PHP so CLI and PHP-FPM share the same configuration.
for candidate in /www/server/php/*/bin/php; do
    [[ -x "$candidate" ]] && PHP_CANDIDATES+=("$candidate")
done
SYSTEM_PHP="$(command -v php 2>/dev/null || true)"
[[ -n "$SYSTEM_PHP" ]] && PHP_CANDIDATES+=("$SYSTEM_PHP")

PHP_BIN=""
for candidate in "${PHP_CANDIDATES[@]}"; do
    if "$candidate" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' >/dev/null 2>&1 \
       && "$candidate" -m 2>/dev/null | grep -qi '^mysqli$' \
       && "$candidate" -m 2>/dev/null | grep -qi '^intl$' \
       && "$candidate" -m 2>/dev/null | grep -qi '^mbstring$' \
       && "$candidate" -m 2>/dev/null | grep -qi '^fileinfo$' \
       && "$candidate" -m 2>/dev/null | grep -qi '^zip$'; then
        PHP_BIN="$candidate"
        break
    fi
done
if [[ -z "$PHP_BIN" ]]; then
    echo "[ERROR] PHP 8.2+ wajib memiliki ekstensi mysqli, intl, mbstring, fileinfo, dan zip."
    echo "[INFO] Periksa PHP aaPanel dan aktifkan ekstensi yang dibutuhkan."
    exit 1
fi
export PATH="$(dirname "$PHP_BIN"):$PATH"
hash -r
echo "[OK] PHP CLI: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

# CodeIgniter DotEnv requires putenv(); remove only this function from aaPanel
# disable_functions for the selected PHP version, preserving other restrictions.
PHP_BIN_DIR="$(dirname "$PHP_BIN")"
PHP_PREFIX="$(dirname "$PHP_BIN_DIR")"
PHP_VERSION_DIR="$(basename "$PHP_PREFIX")"
PHP_INI="$PHP_PREFIX/etc/php.ini"
remove_putenv_from_config() {
    local config="$1" tmp
    [[ -f "$config" ]] || return 0
    tmp="$(mktemp)"
    awk '
      /^[[:space:]]*disable_functions[[:space:]]*=/ ||
      /^[[:space:]]*php_admin_value\[disable_functions\][[:space:]]*=/ {
        key=$0; sub(/=.*/, "", key)
        value=$0; sub(/^[^=]*=[[:space:]]*/, "", value)
        n=split(value, items, ","); out=""
        for (i=1; i<=n; i++) {
          item=items[i]; gsub(/^[[:space:]]+|[[:space:]]+$/, "", item)
          if (tolower(item) == "putenv" || item == "") continue
          out=out (out=="" ? "" : ",") item
        }
        print key "=" out; next
      }
      { print }
    ' "$config" > "$tmp"
    if ! cmp -s "$config" "$tmp"; then
        cp -a "$config" "$config.pcd-backup.$(date +%Y%m%d%H%M%S)"
        cat "$tmp" > "$config"
        echo "[OK] putenv diaktifkan pada konfigurasi: $config"
    fi
    rm -f "$tmp"
}
remove_putenv_from_config "$PHP_INI"
if [[ -d "$PHP_PREFIX/etc/php-fpm.d" ]]; then
    while IFS= read -r -d '' pool_config; do
        remove_putenv_from_config "$pool_config"
    done < <(find "$PHP_PREFIX/etc/php-fpm.d" -maxdepth 1 -type f -name '*.conf' -print0)
fi

mkdir -p "$(dirname "$APP_DIR")" "$(dirname "$SOURCE_DIR")"
if [[ -d "$SOURCE_DIR/.git" ]]; then
    git -C "$SOURCE_DIR" pull --ff-only
else
    git clone "$REPO" "$SOURCE_DIR"
fi

# Create a clean CodeIgniter skeleton only if the target is empty/missing.
if [[ ! -f "$APP_DIR/spark" || ! -d "$APP_DIR/app" || ! -f "$APP_DIR/app/Config/Paths.php" || ! -f "$APP_DIR/public/index.php" ]]; then
    if [[ -e "$APP_DIR" && -n "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" && ! -f "$APP_DIR/spark" ]]; then
        echo "[ERROR] APP_DIR sudah berisi file tetapi bukan struktur CodeIgniter (spark tidak ditemukan). Tidak ada file yang dihapus."
        exit 1
    fi
    BOOTSTRAP_DIR="$(mktemp -d /tmp/pcd-ci4.XXXXXX)"
    trap 'rm -rf "$BOOTSTRAP_DIR"' EXIT
    composer create-project codeigniter4/appstarter "$BOOTSTRAP_DIR/app" --no-interaction
    mkdir -p "$APP_DIR"
    for item in spark composer.json composer.lock env preload.php phpunit.dist.xml; do
        [[ -e "$APP_DIR/$item" ]] || cp -a "$BOOTSTRAP_DIR/app/$item" "$APP_DIR/$item"
    done
    for dir in app public tests writable; do
        [[ -d "$APP_DIR/$dir" ]] || cp -a "$BOOTSTRAP_DIR/app/$dir" "$APP_DIR/$dir"
    done
    mkdir -p "$APP_DIR/public"
    for item in index.php .htaccess; do
        [[ -e "$APP_DIR/public/$item" ]] || cp -a "$BOOTSTRAP_DIR/app/public/$item" "$APP_DIR/public/$item"
    done
    rm -rf "$BOOTSTRAP_DIR"
    trap - EXIT
fi

[[ -f "$APP_DIR/spark" && -f "$APP_DIR/public/index.php" && -f "$APP_DIR/app/Config/Paths.php" ]] || {
    echo "[ERROR] Struktur CodeIgniter belum lengkap."; exit 1;
}

rsync -a "$SOURCE_DIR/app/" "$APP_DIR/app/"
if [[ -d "$SOURCE_DIR/tools" ]]; then
    mkdir -p "$APP_DIR/tools"
    rsync -a "$SOURCE_DIR/tools/" "$APP_DIR/tools/"
fi
mkdir -p "$APP_DIR/public/assets"
rsync -a "$SOURCE_DIR/public/assets/" "$APP_DIR/public/assets/"

# Deploy the official Pesona Cloud branding assets on every fresh installation.
for asset in favicon.ico favicon-32.png apple-touch-icon.png pesona-cloud-logo.png; do
    if [[ ! -f "$SOURCE_DIR/public/$asset" ]]; then
        echo "[ERROR] Aset branding wajib tidak ditemukan: public/$asset"
        exit 1
    fi
    install -m 644 "$SOURCE_DIR/public/$asset" "$APP_DIR/public/$asset"
done
# Views load the main logo from /assets/pesona-cloud-logo.png.
install -m 644 "$SOURCE_DIR/public/pesona-cloud-logo.png" "$APP_DIR/public/assets/pesona-cloud-logo.png"

cp -n "$SOURCE_DIR/.env.example" "$APP_DIR/.env.example"

cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader
[[ -f .env ]] || cp env .env

# Replace only deployment keys, preserving unrelated .env settings.
sed -i -E '/^[[:space:]]*#?[[:space:]]*app\.baseURL[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*app\.forceGlobalSecureRequests[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*database\.default\.(hostname|database|username|password|DBDriver|port)[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*storage\.privatePath[[:space:]]*=/d' .env
# Generate a persistent encryption starter key once. Never rotate it on reinstall/update.
# Existing share-token ciphertext depends on this key.
if ! grep -Eq '^[[:space:]]*encryption\.key[[:space:]]*=' .env; then
    ENCRYPTION_KEY="$(openssl rand -hex 16)"
    printf "\nencryption.key = '%s'\n" "$ENCRYPTION_KEY" >> .env
    echo "[OK] encryption.key dibuat untuk enkripsi token tautan berbagi."
else
    echo "[OK] encryption.key yang sudah ada dipertahankan."
fi

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

# Preflight writable paths and available disk before deployment completes.
for path in "$STORAGE" "$APP_DIR/writable/cache" "$APP_DIR/writable/logs" "$APP_DIR/writable/session"; do
    [[ -d "$path" && -w "$path" ]] || { echo "[ERROR] Direktori tidak dapat ditulis: $path"; exit 1; }
done
AVAILABLE_KB="$(df -Pk "$APP_DIR/writable/cache" | awk 'NR==2 {print $4}')"
AVAILABLE_GB="$(( ${AVAILABLE_KB:-0} / 1024 / 1024 ))"
echo "[INFO] Ruang disk tersedia pada storage sementara: ${AVAILABLE_GB} GB"
if (( AVAILABLE_GB < 5 )); then
    echo "[WARN] Ruang disk tersisa kurang dari 5 GB. Unduhan ZIP besar dapat gagal jika kapasitas sementara tidak mencukupi."
fi
MEM_TOTAL_KB="$(awk '/MemTotal:/ {print $2}' /proc/meminfo 2>/dev/null || echo 0)"
MEM_TOTAL_GB="$(( ${MEM_TOTAL_KB:-0} / 1024 / 1024 ))"
echo "[INFO] RAM fisik: ${MEM_TOTAL_GB} GB"
if (( MEM_TOTAL_KB > 0 && MEM_TOTAL_KB < 1048576 )); then
    echo "[WARN] RAM kurang dari 1 GB. Performa saat banyak proses bersamaan mungkin terbatas."
fi

if id www >/dev/null 2>&1; then
    chown www:www .env
    chown -R www:www "$STORAGE" writable
elif id www-data >/dev/null 2>&1; then
    chown www-data:www-data .env
    chown -R www-data:www-data "$STORAGE" writable
else
    echo "[WARN] User web server tidak ditemukan; atur kepemilikan file secara manual."
fi

# Extend app-local open_basedir without removing existing allowed paths.
normalize_path() {
    local p="$1"
    [[ "$p" == */ ]] || p="$p/"
    printf '%s' "$p"
}
APP_DIR_ABS="$(cd "$APP_DIR" && pwd)"
STORAGE_ABS="$(cd "$STORAGE" && pwd)"
ALLOWED_REQUIRED="$(normalize_path "$APP_DIR_ABS"):$(normalize_path "$STORAGE_ABS"):/tmp/"
PUBLIC_INI="$APP_DIR/public/.user.ini"
[[ -e "$PUBLIC_INI" ]] || install -m 644 /dev/null "$PUBLIC_INI"
USER_INI_FILES=()
SITE_ROOT="$(dirname "$APP_DIR")"
for ini in "$SITE_ROOT/.user.ini" "$APP_DIR/.user.ini" "$PUBLIC_INI"; do
    [[ -f "$ini" ]] || continue
    duplicate=0
    for existing in "${USER_INI_FILES[@]}"; do
        [[ "$existing" == "$ini" ]] && duplicate=1
    done
    [[ "$duplicate" -eq 0 ]] && USER_INI_FILES+=("$ini")
done

for ini in "${USER_INI_FILES[@]}"; do
    tmp="$(mktemp)"
    awk -v required="$ALLOWED_REQUIRED" '
      BEGIN { updated=0 }
      /^[[:space:]]*open_basedir[[:space:]]*=/ {
        line=$0
        sub(/^[^=]*=[[:space:]]*/, "", line)
        gsub(/[[:space:]"]/ , "", line)
        n=split(line, paths, ":")
        for (i=1; i<=n; i++) if (paths[i] != "") allowed[paths[i]]=1
        m=split(required, extra, ":")
        for (i=1; i<=m; i++) if (extra[i] != "") allowed[extra[i]]=1
        value=""
        for (p in allowed) value=value (value=="" ? "" : ":") p
        print "open_basedir=" value
        updated=1
        next
      }
      { print }
      END { if (!updated) print "open_basedir=" required }
    ' "$ini" > "$tmp"
    immutable=0
    if command -v lsattr >/dev/null 2>&1 && lsattr -d "$ini" 2>/dev/null | awk 'NR==1 {exit(index($1,"i") ? 0 : 1)}'; then
        immutable=1
        chattr -i "$ini"
    fi
    cat "$tmp" > "$ini"
    rm -f "$tmp"
    chmod 644 "$ini" 2>/dev/null || true
    if id www >/dev/null 2>&1; then chown www:www "$ini" 2>/dev/null || true
    elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$ini" 2>/dev/null || true
    fi
    [[ "$immutable" -eq 1 ]] && chattr +i "$ini"
    echo "[OK] open_basedir disesuaikan: $ini"
done

# Set an explicit open_basedir allowlist for CodeIgniter's public entry point.
if [[ -f "$SOURCE_DIR/tools/configure-open-basedir.sh" ]]; then
    bash "$SOURCE_DIR/tools/configure-open-basedir.sh" "$APP_DIR" "$STORAGE"
else
    echo "[ERROR] tools/configure-open-basedir.sh tidak ditemukan."
    exit 1
fi

# Apply safe PHP-FPM timeout defaults for long bulk ZIP downloads.
if [[ -f "$SOURCE_DIR/tools/configure-php-fpm.sh" ]]; then
    bash "$SOURCE_DIR/tools/configure-php-fpm.sh" "$PHP_PREFIX"
else
    echo "[ERROR] tools/configure-php-fpm.sh tidak ditemukan di source repository."
    exit 1
fi

# Configure the selected site's PHP FastCGI and response-send timeouts safely.
if [[ -f "$SOURCE_DIR/tools/configure-nginx-timeouts.sh" ]]; then
    bash "$SOURCE_DIR/tools/configure-nginx-timeouts.sh" "$DOMAIN" "$APP_DIR" "$STORAGE"
else
    echo "[ERROR] tools/configure-nginx-timeouts.sh tidak ditemukan di source repository."
    exit 1
fi

if [[ ! -r "$APP_DIR/app/Config/Paths.php" || ! -r "$APP_DIR/vendor/autoload.php" ]]; then
    echo "[ERROR] File inti aplikasi tidak dapat dibaca oleh user installer."
    exit 1
fi
if ! grep -Fq "open_basedir=$APP_DIR/:$STORAGE/:/tmp/" "$APP_DIR/public/.user.ini"; then
    echo "[ERROR] open_basedir public/.user.ini belum mencakup app dan private storage."
    exit 1
fi

if ! "$PHP_BIN" -r 'exit(class_exists("ZipArchive") ? 0 : 1);'; then
    echo "[ERROR] Ekstensi ZipArchive tidak aktif pada PHP terpilih."
    exit 1
fi
if ! "$PHP_BIN" -r 'exit(function_exists("putenv") ? 0 : 1);'; then
    echo "[ERROR] putenv() masih dinonaktifkan pada PHP CLI. Periksa konfigurasi PHP."
    exit 1
fi
echo "[OK] Fungsi PHP putenv() tersedia."

# Collect system facts with CLI PHP; failures are non-fatal on fresh installations.
if [[ -f "$APP_DIR/tools/collect-system-info.php" ]]; then
    "$PHP_BIN" "$APP_DIR/tools/collect-system-info.php" || echo "[WARN] Snapshot informasi sistem dilewati."
    if [[ -f "$APP_DIR/writable/cache/system-info.json" ]]; then
        if id www >/dev/null 2>&1; then chown www:www "$APP_DIR/writable/cache/system-info.json"; chmod 640 "$APP_DIR/writable/cache/system-info.json"
        elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$APP_DIR/writable/cache/system-info.json"; chmod 640 "$APP_DIR/writable/cache/system-info.json"
        fi
    fi
}

echo
echo "[OK] Source Git : $SOURCE_DIR"
echo "[OK] Aplikasi   : $APP_DIR"
echo "[NEXT] Document root aaPanel: $APP_DIR/public"
echo "[NEXT] Pastikan database '$DB_NAME' sudah dibuat di aaPanel."
echo "[NEXT] Jalankan: cd '$APP_DIR' && '$PHP_BIN' spark migrate"
echo "[NEXT] Setelah migration, buka https://$DOMAIN/setup untuk membuat admin pertama."
echo "[NOTE] Jangan gunakan untuk data penting sebelum pengujian backup dan keamanan selesai."
 \\
       && "$candidate" -m 2>/dev/null | grep -qi '^zip
        PHP_BIN="$candidate"
        break
    fi
done
if [[ -z "$PHP_BIN" ]]; then
    echo "[ERROR] Tidak ditemukan PHP CLI versi 8.2+ dengan ekstensi mysqli, intl, mbstring, dan fileinfo."
    echo "[INFO] Periksa PHP aaPanel dan aktifkan ekstensi yang dibutuhkan."
    exit 1
fi
export PATH="$(dirname "$PHP_BIN"):$PATH"
hash -r
echo "[OK] PHP CLI: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

# CodeIgniter DotEnv requires putenv(); remove only this function from aaPanel
# disable_functions for the selected PHP version, preserving other restrictions.
PHP_BIN_DIR="$(dirname "$PHP_BIN")"
PHP_PREFIX="$(dirname "$PHP_BIN_DIR")"
PHP_VERSION_DIR="$(basename "$PHP_PREFIX")"
PHP_INI="$PHP_PREFIX/etc/php.ini"
remove_putenv_from_config() {
    local config="$1" tmp
    [[ -f "$config" ]] || return 0
    tmp="$(mktemp)"
    awk '
      /^[[:space:]]*disable_functions[[:space:]]*=/ ||
      /^[[:space:]]*php_admin_value\[disable_functions\][[:space:]]*=/ {
        key=$0; sub(/=.*/, "", key)
        value=$0; sub(/^[^=]*=[[:space:]]*/, "", value)
        n=split(value, items, ","); out=""
        for (i=1; i<=n; i++) {
          item=items[i]; gsub(/^[[:space:]]+|[[:space:]]+$/, "", item)
          if (tolower(item) == "putenv" || item == "") continue
          out=out (out=="" ? "" : ",") item
        }
        print key "=" out; next
      }
      { print }
    ' "$config" > "$tmp"
    if ! cmp -s "$config" "$tmp"; then
        cp -a "$config" "$config.pcd-backup.$(date +%Y%m%d%H%M%S)"
        cat "$tmp" > "$config"
        echo "[OK] putenv diaktifkan pada konfigurasi: $config"
    fi
    rm -f "$tmp"
}
remove_putenv_from_config "$PHP_INI"
if [[ -d "$PHP_PREFIX/etc/php-fpm.d" ]]; then
    while IFS= read -r -d '' pool_config; do
        remove_putenv_from_config "$pool_config"
    done < <(find "$PHP_PREFIX/etc/php-fpm.d" -maxdepth 1 -type f -name '*.conf' -print0)
fi

mkdir -p "$(dirname "$APP_DIR")" "$(dirname "$SOURCE_DIR")"
if [[ -d "$SOURCE_DIR/.git" ]]; then
    git -C "$SOURCE_DIR" pull --ff-only
else
    git clone "$REPO" "$SOURCE_DIR"
fi

# Create a clean CodeIgniter skeleton only if the target is empty/missing.
if [[ ! -f "$APP_DIR/spark" || ! -d "$APP_DIR/app" || ! -f "$APP_DIR/app/Config/Paths.php" || ! -f "$APP_DIR/public/index.php" ]]; then
    if [[ -e "$APP_DIR" && -n "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" && ! -f "$APP_DIR/spark" ]]; then
        echo "[ERROR] APP_DIR sudah berisi file tetapi bukan struktur CodeIgniter (spark tidak ditemukan). Tidak ada file yang dihapus."
        exit 1
    fi
    BOOTSTRAP_DIR="$(mktemp -d /tmp/pcd-ci4.XXXXXX)"
    trap 'rm -rf "$BOOTSTRAP_DIR"' EXIT
    composer create-project codeigniter4/appstarter "$BOOTSTRAP_DIR/app" --no-interaction
    mkdir -p "$APP_DIR"
    for item in spark composer.json composer.lock env preload.php phpunit.dist.xml; do
        [[ -e "$APP_DIR/$item" ]] || cp -a "$BOOTSTRAP_DIR/app/$item" "$APP_DIR/$item"
    done
    for dir in app public tests writable; do
        [[ -d "$APP_DIR/$dir" ]] || cp -a "$BOOTSTRAP_DIR/app/$dir" "$APP_DIR/$dir"
    done
    mkdir -p "$APP_DIR/public"
    for item in index.php .htaccess; do
        [[ -e "$APP_DIR/public/$item" ]] || cp -a "$BOOTSTRAP_DIR/app/public/$item" "$APP_DIR/public/$item"
    done
    rm -rf "$BOOTSTRAP_DIR"
    trap - EXIT
fi

[[ -f "$APP_DIR/spark" && -f "$APP_DIR/public/index.php" && -f "$APP_DIR/app/Config/Paths.php" ]] || {
    echo "[ERROR] Struktur CodeIgniter belum lengkap."; exit 1;
}

rsync -a "$SOURCE_DIR/app/" "$APP_DIR/app/"
if [[ -d "$SOURCE_DIR/tools" ]]; then
    mkdir -p "$APP_DIR/tools"
    rsync -a "$SOURCE_DIR/tools/" "$APP_DIR/tools/"
fi
mkdir -p "$APP_DIR/public/assets"
rsync -a "$SOURCE_DIR/public/assets/" "$APP_DIR/public/assets/"

# Deploy the official Pesona Cloud branding assets on every fresh installation.
for asset in favicon.ico favicon-32.png apple-touch-icon.png pesona-cloud-logo.png; do
    if [[ ! -f "$SOURCE_DIR/public/$asset" ]]; then
        echo "[ERROR] Aset branding wajib tidak ditemukan: public/$asset"
        exit 1
    fi
    install -m 644 "$SOURCE_DIR/public/$asset" "$APP_DIR/public/$asset"
done
# Views load the main logo from /assets/pesona-cloud-logo.png.
install -m 644 "$SOURCE_DIR/public/pesona-cloud-logo.png" "$APP_DIR/public/assets/pesona-cloud-logo.png"

cp -n "$SOURCE_DIR/.env.example" "$APP_DIR/.env.example"

cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader
[[ -f .env ]] || cp env .env

# Replace only deployment keys, preserving unrelated .env settings.
sed -i -E '/^[[:space:]]*#?[[:space:]]*app\.baseURL[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*app\.forceGlobalSecureRequests[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*database\.default\.(hostname|database|username|password|DBDriver|port)[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*storage\.privatePath[[:space:]]*=/d' .env
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
    echo "[WARN] User web server tidak ditemukan; atur kepemilikan file secara manual."
fi

# Extend app-local open_basedir without removing existing allowed paths.
normalize_path() {
    local p="$1"
    [[ "$p" == */ ]] || p="$p/"
    printf '%s' "$p"
}
APP_DIR_ABS="$(cd "$APP_DIR" && pwd)"
STORAGE_ABS="$(cd "$STORAGE" && pwd)"
ALLOWED_REQUIRED="$(normalize_path "$APP_DIR_ABS"):$(normalize_path "$STORAGE_ABS"):/tmp/"
PUBLIC_INI="$APP_DIR/public/.user.ini"
[[ -e "$PUBLIC_INI" ]] || install -m 644 /dev/null "$PUBLIC_INI"
USER_INI_FILES=()
SITE_ROOT="$(dirname "$APP_DIR")"
for ini in "$SITE_ROOT/.user.ini" "$APP_DIR/.user.ini" "$PUBLIC_INI"; do
    [[ -f "$ini" ]] || continue
    duplicate=0
    for existing in "${USER_INI_FILES[@]}"; do
        [[ "$existing" == "$ini" ]] && duplicate=1
    done
    [[ "$duplicate" -eq 0 ]] && USER_INI_FILES+=("$ini")
done

for ini in "${USER_INI_FILES[@]}"; do
    tmp="$(mktemp)"
    awk -v required="$ALLOWED_REQUIRED" '
      BEGIN { updated=0 }
      /^[[:space:]]*open_basedir[[:space:]]*=/ {
        line=$0
        sub(/^[^=]*=[[:space:]]*/, "", line)
        gsub(/[[:space:]"]/ , "", line)
        n=split(line, paths, ":")
        for (i=1; i<=n; i++) if (paths[i] != "") allowed[paths[i]]=1
        m=split(required, extra, ":")
        for (i=1; i<=m; i++) if (extra[i] != "") allowed[extra[i]]=1
        value=""
        for (p in allowed) value=value (value=="" ? "" : ":") p
        print "open_basedir=" value
        updated=1
        next
      }
      { print }
      END { if (!updated) print "open_basedir=" required }
    ' "$ini" > "$tmp"
    immutable=0
    if command -v lsattr >/dev/null 2>&1 && lsattr -d "$ini" 2>/dev/null | awk 'NR==1 {exit(index($1,"i") ? 0 : 1)}'; then
        immutable=1
        chattr -i "$ini"
    fi
    cat "$tmp" > "$ini"
    rm -f "$tmp"
    chmod 644 "$ini" 2>/dev/null || true
    if id www >/dev/null 2>&1; then chown www:www "$ini" 2>/dev/null || true
    elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$ini" 2>/dev/null || true
    fi
    [[ "$immutable" -eq 1 ]] && chattr +i "$ini"
    echo "[OK] open_basedir disesuaikan: $ini"
done

# Apply safe PHP-FPM timeout defaults for long bulk ZIP downloads.
if [[ -f "$SOURCE_DIR/tools/configure-php-fpm.sh" ]]; then
    bash "$SOURCE_DIR/tools/configure-php-fpm.sh" "$PHP_PREFIX"
else
    echo "[ERROR] tools/configure-php-fpm.sh tidak ditemukan di source repository."
    exit 1
fi
if ! "$PHP_BIN" -r 'exit(function_exists("putenv") ? 0 : 1);'; then
    echo "[ERROR] putenv() masih dinonaktifkan pada PHP CLI. Periksa konfigurasi PHP."
    exit 1
fi
echo "[OK] Fungsi PHP putenv() tersedia."

# Collect system facts with CLI PHP; failures are non-fatal on fresh installations.
if [[ -f "$APP_DIR/tools/collect-system-info.php" ]]; then
    "$PHP_BIN" "$APP_DIR/tools/collect-system-info.php" || echo "[WARN] Snapshot informasi sistem dilewati."
    if [[ -f "$APP_DIR/writable/cache/system-info.json" ]]; then
        if id www >/dev/null 2>&1; then chown www:www "$APP_DIR/writable/cache/system-info.json"; chmod 640 "$APP_DIR/writable/cache/system-info.json"
        elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$APP_DIR/writable/cache/system-info.json"; chmod 640 "$APP_DIR/writable/cache/system-info.json"
        fi
    fi
}

echo
echo "[OK] Source Git : $SOURCE_DIR"
echo "[OK] Aplikasi   : $APP_DIR"
echo "[NEXT] Document root aaPanel: $APP_DIR/public"
echo "[NEXT] Pastikan database '$DB_NAME' sudah dibuat di aaPanel."
echo "[NEXT] Jalankan: cd '$APP_DIR' && '$PHP_BIN' spark migrate"
echo "[NEXT] Setelah migration, buka https://$DOMAIN/setup untuk membuat admin pertama."
echo "[NOTE] Jangan gunakan untuk data penting sebelum pengujian backup dan keamanan selesai."
; then
        PHP_BIN="$candidate"
        break
    fi
done
if [[ -z "$PHP_BIN" ]]; then
    echo "[ERROR] Tidak ditemukan PHP CLI versi 8.2+ dengan ekstensi mysqli, intl, mbstring, dan fileinfo."
    echo "[INFO] Periksa PHP aaPanel dan aktifkan ekstensi yang dibutuhkan."
    exit 1
fi
export PATH="$(dirname "$PHP_BIN"):$PATH"
hash -r
echo "[OK] PHP CLI: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

# CodeIgniter DotEnv requires putenv(); remove only this function from aaPanel
# disable_functions for the selected PHP version, preserving other restrictions.
PHP_BIN_DIR="$(dirname "$PHP_BIN")"
PHP_PREFIX="$(dirname "$PHP_BIN_DIR")"
PHP_VERSION_DIR="$(basename "$PHP_PREFIX")"
PHP_INI="$PHP_PREFIX/etc/php.ini"
remove_putenv_from_config() {
    local config="$1" tmp
    [[ -f "$config" ]] || return 0
    tmp="$(mktemp)"
    awk '
      /^[[:space:]]*disable_functions[[:space:]]*=/ ||
      /^[[:space:]]*php_admin_value\[disable_functions\][[:space:]]*=/ {
        key=$0; sub(/=.*/, "", key)
        value=$0; sub(/^[^=]*=[[:space:]]*/, "", value)
        n=split(value, items, ","); out=""
        for (i=1; i<=n; i++) {
          item=items[i]; gsub(/^[[:space:]]+|[[:space:]]+$/, "", item)
          if (tolower(item) == "putenv" || item == "") continue
          out=out (out=="" ? "" : ",") item
        }
        print key "=" out; next
      }
      { print }
    ' "$config" > "$tmp"
    if ! cmp -s "$config" "$tmp"; then
        cp -a "$config" "$config.pcd-backup.$(date +%Y%m%d%H%M%S)"
        cat "$tmp" > "$config"
        echo "[OK] putenv diaktifkan pada konfigurasi: $config"
    fi
    rm -f "$tmp"
}
remove_putenv_from_config "$PHP_INI"
if [[ -d "$PHP_PREFIX/etc/php-fpm.d" ]]; then
    while IFS= read -r -d '' pool_config; do
        remove_putenv_from_config "$pool_config"
    done < <(find "$PHP_PREFIX/etc/php-fpm.d" -maxdepth 1 -type f -name '*.conf' -print0)
fi

mkdir -p "$(dirname "$APP_DIR")" "$(dirname "$SOURCE_DIR")"
if [[ -d "$SOURCE_DIR/.git" ]]; then
    git -C "$SOURCE_DIR" pull --ff-only
else
    git clone "$REPO" "$SOURCE_DIR"
fi

# Create a clean CodeIgniter skeleton only if the target is empty/missing.
if [[ ! -f "$APP_DIR/spark" || ! -d "$APP_DIR/app" || ! -f "$APP_DIR/app/Config/Paths.php" || ! -f "$APP_DIR/public/index.php" ]]; then
    if [[ -e "$APP_DIR" && -n "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" && ! -f "$APP_DIR/spark" ]]; then
        echo "[ERROR] APP_DIR sudah berisi file tetapi bukan struktur CodeIgniter (spark tidak ditemukan). Tidak ada file yang dihapus."
        exit 1
    fi
    BOOTSTRAP_DIR="$(mktemp -d /tmp/pcd-ci4.XXXXXX)"
    trap 'rm -rf "$BOOTSTRAP_DIR"' EXIT
    composer create-project codeigniter4/appstarter "$BOOTSTRAP_DIR/app" --no-interaction
    mkdir -p "$APP_DIR"
    for item in spark composer.json composer.lock env preload.php phpunit.dist.xml; do
        [[ -e "$APP_DIR/$item" ]] || cp -a "$BOOTSTRAP_DIR/app/$item" "$APP_DIR/$item"
    done
    for dir in app public tests writable; do
        [[ -d "$APP_DIR/$dir" ]] || cp -a "$BOOTSTRAP_DIR/app/$dir" "$APP_DIR/$dir"
    done
    mkdir -p "$APP_DIR/public"
    for item in index.php .htaccess; do
        [[ -e "$APP_DIR/public/$item" ]] || cp -a "$BOOTSTRAP_DIR/app/public/$item" "$APP_DIR/public/$item"
    done
    rm -rf "$BOOTSTRAP_DIR"
    trap - EXIT
fi

[[ -f "$APP_DIR/spark" && -f "$APP_DIR/public/index.php" && -f "$APP_DIR/app/Config/Paths.php" ]] || {
    echo "[ERROR] Struktur CodeIgniter belum lengkap."; exit 1;
}

rsync -a "$SOURCE_DIR/app/" "$APP_DIR/app/"
if [[ -d "$SOURCE_DIR/tools" ]]; then
    mkdir -p "$APP_DIR/tools"
    rsync -a "$SOURCE_DIR/tools/" "$APP_DIR/tools/"
fi
mkdir -p "$APP_DIR/public/assets"
rsync -a "$SOURCE_DIR/public/assets/" "$APP_DIR/public/assets/"

# Deploy the official Pesona Cloud branding assets on every fresh installation.
for asset in favicon.ico favicon-32.png apple-touch-icon.png pesona-cloud-logo.png; do
    if [[ ! -f "$SOURCE_DIR/public/$asset" ]]; then
        echo "[ERROR] Aset branding wajib tidak ditemukan: public/$asset"
        exit 1
    fi
    install -m 644 "$SOURCE_DIR/public/$asset" "$APP_DIR/public/$asset"
done
# Views load the main logo from /assets/pesona-cloud-logo.png.
install -m 644 "$SOURCE_DIR/public/pesona-cloud-logo.png" "$APP_DIR/public/assets/pesona-cloud-logo.png"

cp -n "$SOURCE_DIR/.env.example" "$APP_DIR/.env.example"

cd "$APP_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader
[[ -f .env ]] || cp env .env

# Replace only deployment keys, preserving unrelated .env settings.
sed -i -E '/^[[:space:]]*#?[[:space:]]*app\.baseURL[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*app\.forceGlobalSecureRequests[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*database\.default\.(hostname|database|username|password|DBDriver|port)[[:space:]]*=/d; /^[[:space:]]*#?[[:space:]]*storage\.privatePath[[:space:]]*=/d' .env
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
    echo "[WARN] User web server tidak ditemukan; atur kepemilikan file secara manual."
fi

# Extend app-local open_basedir without removing existing allowed paths.
normalize_path() {
    local p="$1"
    [[ "$p" == */ ]] || p="$p/"
    printf '%s' "$p"
}
APP_DIR_ABS="$(cd "$APP_DIR" && pwd)"
STORAGE_ABS="$(cd "$STORAGE" && pwd)"
ALLOWED_REQUIRED="$(normalize_path "$APP_DIR_ABS"):$(normalize_path "$STORAGE_ABS"):/tmp/"
PUBLIC_INI="$APP_DIR/public/.user.ini"
[[ -e "$PUBLIC_INI" ]] || install -m 644 /dev/null "$PUBLIC_INI"
USER_INI_FILES=()
SITE_ROOT="$(dirname "$APP_DIR")"
for ini in "$SITE_ROOT/.user.ini" "$APP_DIR/.user.ini" "$PUBLIC_INI"; do
    [[ -f "$ini" ]] || continue
    duplicate=0
    for existing in "${USER_INI_FILES[@]}"; do
        [[ "$existing" == "$ini" ]] && duplicate=1
    done
    [[ "$duplicate" -eq 0 ]] && USER_INI_FILES+=("$ini")
done

for ini in "${USER_INI_FILES[@]}"; do
    tmp="$(mktemp)"
    awk -v required="$ALLOWED_REQUIRED" '
      BEGIN { updated=0 }
      /^[[:space:]]*open_basedir[[:space:]]*=/ {
        line=$0
        sub(/^[^=]*=[[:space:]]*/, "", line)
        gsub(/[[:space:]"]/ , "", line)
        n=split(line, paths, ":")
        for (i=1; i<=n; i++) if (paths[i] != "") allowed[paths[i]]=1
        m=split(required, extra, ":")
        for (i=1; i<=m; i++) if (extra[i] != "") allowed[extra[i]]=1
        value=""
        for (p in allowed) value=value (value=="" ? "" : ":") p
        print "open_basedir=" value
        updated=1
        next
      }
      { print }
      END { if (!updated) print "open_basedir=" required }
    ' "$ini" > "$tmp"
    immutable=0
    if command -v lsattr >/dev/null 2>&1 && lsattr -d "$ini" 2>/dev/null | awk 'NR==1 {exit(index($1,"i") ? 0 : 1)}'; then
        immutable=1
        chattr -i "$ini"
    fi
    cat "$tmp" > "$ini"
    rm -f "$tmp"
    chmod 644 "$ini" 2>/dev/null || true
    if id www >/dev/null 2>&1; then chown www:www "$ini" 2>/dev/null || true
    elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$ini" 2>/dev/null || true
    fi
    [[ "$immutable" -eq 1 ]] && chattr +i "$ini"
    echo "[OK] open_basedir disesuaikan: $ini"
done

# Apply safe PHP-FPM timeout defaults for long bulk ZIP downloads.
if [[ -f "$SOURCE_DIR/tools/configure-php-fpm.sh" ]]; then
    bash "$SOURCE_DIR/tools/configure-php-fpm.sh" "$PHP_PREFIX"
else
    echo "[ERROR] tools/configure-php-fpm.sh tidak ditemukan di source repository."
    exit 1
fi
if ! "$PHP_BIN" -r 'exit(function_exists("putenv") ? 0 : 1);'; then
    echo "[ERROR] putenv() masih dinonaktifkan pada PHP CLI. Periksa konfigurasi PHP."
    exit 1
fi
echo "[OK] Fungsi PHP putenv() tersedia."

# Collect system facts with CLI PHP; failures are non-fatal on fresh installations.
if [[ -f "$APP_DIR/tools/collect-system-info.php" ]]; then
    "$PHP_BIN" "$APP_DIR/tools/collect-system-info.php" || echo "[WARN] Snapshot informasi sistem dilewati."
    if [[ -f "$APP_DIR/writable/cache/system-info.json" ]]; then
        if id www >/dev/null 2>&1; then chown www:www "$APP_DIR/writable/cache/system-info.json"; chmod 640 "$APP_DIR/writable/cache/system-info.json"
        elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$APP_DIR/writable/cache/system-info.json"; chmod 640 "$APP_DIR/writable/cache/system-info.json"
        fi
    fi
}

echo
echo "[OK] Source Git : $SOURCE_DIR"
echo "[OK] Aplikasi   : $APP_DIR"
echo "[NEXT] Document root aaPanel: $APP_DIR/public"
echo "[NEXT] Pastikan database '$DB_NAME' sudah dibuat di aaPanel."
echo "[NEXT] Jalankan: cd '$APP_DIR' && '$PHP_BIN' spark migrate"
echo "[NEXT] Setelah migration, buka https://$DOMAIN/setup untuk membuat admin pertama."
echo "[NOTE] Jangan gunakan untuk data penting sebelum pengujian backup dan keamanan selesai."
