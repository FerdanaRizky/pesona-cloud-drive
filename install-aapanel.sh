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

# Ensure the CodeIgniter framework skeleton exists. This site may already
# contain the application, .env, and vendor but have no public/ directory.
# Restore only missing framework entry-point files; never overwrite user data.
if [[ ! -f "$APP_DIR/spark" || ! -d "$APP_DIR/app" || ! -f "$APP_DIR/app/Config/Paths.php" || ! -f "$APP_DIR/public/index.php" ]]; then
    if [[ -e "$APP_DIR" && -n "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" && ! -f "$APP_DIR/spark" ]]; then
        echo "[ERROR] APP_DIR sudah berisi file tetapi bukan struktur CodeIgniter (spark tidak ditemukan). Tidak ada file yang dihapus."
        echo "[NEXT] Gunakan direktori aplikasi kosong atau pindahkan file lama terlebih dahulu: $APP_DIR"
        exit 1
    fi
    BOOTSTRAP_DIR="$(mktemp -d /tmp/pcd-ci4.XXXXXX)"
    trap 'rm -rf "$BOOTSTRAP_DIR"' EXIT
    composer create-project codeigniter4/appstarter "$BOOTSTRAP_DIR/app" --no-interaction

    # Fill missing top-level framework files only. Existing app code/config,
    # .env, vendor, writable data, and uploads are left untouched.
    mkdir -p "$APP_DIR"
    for item in spark composer.json composer.lock env preload.php phpunit.dist.xml; do
        if [[ ! -e "$APP_DIR/$item" && -e "$BOOTSTRAP_DIR/app/$item" ]]; then
            cp -a "$BOOTSTRAP_DIR/app/$item" "$APP_DIR/$item"
        fi
    done
    for dir in app public tests writable; do
        if [[ ! -d "$APP_DIR/$dir" ]]; then
            cp -a "$BOOTSTRAP_DIR/app/$dir" "$APP_DIR/$dir"
        fi
    done
    # A partially-created public directory may exist but lack the front
    # controller. Copy only the missing entry-point files from appstarter.
    mkdir -p "$APP_DIR/public"
    for item in index.php .htaccess; do
        if [[ ! -e "$APP_DIR/public/$item" && -e "$BOOTSTRAP_DIR/app/public/$item" ]]; then
            cp -a "$BOOTSTRAP_DIR/app/public/$item" "$APP_DIR/public/$item"
        fi
    done
    rm -rf "$BOOTSTRAP_DIR"
    trap - EXIT
fi

[[ -f "$APP_DIR/spark" && -f "$APP_DIR/public/index.php" && -f "$APP_DIR/app/Config/Paths.php" ]] || {
    echo "[ERROR] Struktur CodeIgniter masih belum lengkap setelah pemulihan."
    echo "[INFO] File yang wajib tersedia: spark, app/Config/Paths.php, public/index.php"
    echo "[INFO] Periksa akses tulis direktori: $APP_DIR"
    exit 1
}

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


# aaPanel commonly creates an open_basedir restriction that only allows the
# website document root (public/) and /tmp. CodeIgniter must also read app/,
# vendor/, writable/, and its .env file. Extend existing .user.ini directives
# without removing any paths already allowed by aaPanel.
normalize_path() {
    local p="$1"
    [[ "$p" == */ ]] || p="$p/"
    printf '%s' "$p"
}
APP_DIR_ABS="$(cd "$APP_DIR" && pwd)"
STORAGE_ABS="$(mkdir -p "$STORAGE" && cd "$STORAGE" && pwd)"
ALLOWED_REQUIRED="$(normalize_path "$APP_DIR_ABS"):$(normalize_path "$STORAGE_ABS"):/tmp/"
# Configure app-local ini files only. aaPanel may mark .user.ini immutable
# (chattr +i). Temporarily remove that flag when present, update safely, then
# restore it so aaPanel's protection is preserved. Never touch website-level ini.
PUBLIC_INI="$APP_DIR/public/.user.ini"
if [[ ! -e "$PUBLIC_INI" ]]; then
    if ! install -m 644 /dev/null "$PUBLIC_INI"; then
        echo "[WARN] Tidak dapat membuat $PUBLIC_INI. Atur open_basedir manual melalui aaPanel."
        PUBLIC_INI=""
    fi
fi

USER_INI_FILES=()
for ini in "$APP_DIR/.user.ini" "$PUBLIC_INI"; do
    [[ -n "$ini" && -f "$ini" ]] || continue
    duplicate=0
    for existing in "${USER_INI_FILES[@]}"; do
        [[ "$existing" == "$ini" ]] && duplicate=1
    done
    [[ "$duplicate" -eq 0 ]] && USER_INI_FILES+=("$ini")
done

for ini in "${USER_INI_FILES[@]}"; do
    tmp="$(mktemp)"
    if ! awk -v required="$ALLOWED_REQUIRED" '
      BEGIN { updated=0 }
      /^[[:space:]]*open_basedir[[:space:]]*=/ {
        line=$0
        sub(/^[^=]*=[[:space:]]*/, "", line)
        gsub(/[[:space:]"]/, "", line)
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
    ' "$ini" > "$tmp"; then
        echo "[WARN] Gagal membaca konfigurasi open_basedir: $ini"
        rm -f "$tmp"
        continue
    fi

    immutable=0
    if command -v lsattr >/dev/null 2>&1 && lsattr -d "$ini" 2>/dev/null | awk 'NR==1 {exit(index($1,"i") ? 0 : 1)}'; then
        immutable=1
        if ! chattr -i "$ini"; then
            echo "[WARN] Tidak dapat melepas atribut immutable pada $ini. Atur open_basedir manual melalui aaPanel."
            rm -f "$tmp"
            continue
        fi
    fi

    if ! cat "$tmp" > "$ini"; then
        echo "[WARN] Tidak dapat menulis $ini (izin/ACL). Dilewati; pastikan open_basedir mengizinkan $APP_DIR_ABS dan $STORAGE_ABS."
        [[ "$immutable" -eq 1 ]] && chattr +i "$ini" 2>/dev/null || true
        rm -f "$tmp"
        continue
    fi
    rm -f "$tmp"
    chmod 644 "$ini" 2>/dev/null || true
    if id www >/dev/null 2>&1; then chown www:www "$ini" 2>/dev/null || true
    elif id www-data >/dev/null 2>&1; then chown www-data:www-data "$ini" 2>/dev/null || true
    fi
    if [[ "$immutable" -eq 1 ]]; then
        chattr +i "$ini" || echo "[WARN] Gagal mengembalikan atribut immutable pada $ini."
    fi
    echo "[OK] open_basedir disesuaikan: $ini"
done

if [[ -z "$PUBLIC_INI" ]]; then
    echo "[WARN] File public/.user.ini belum tersedia; konfigurasi open_basedir wajib diselesaikan melalui aaPanel."
fi


# Configure the existing aaPanel Nginx vhost: document root, front-controller
# rewrite, and large-upload limits. Edit only the matching site's server blocks.
NGINX_SITE="/www/server/panel/vhost/nginx/$DOMAIN.conf"
if [[ -f "$NGINX_SITE" ]]; then
    if command -v python3 >/dev/null 2>&1; then
        python3 - "$NGINX_SITE" "$DOMAIN" "$APP_DIR_ABS/public" <<'PYNGINX'
import re, sys
from pathlib import Path
path, domain, docroot = sys.argv[1:]
text = Path(path).read_text()
lines = text.splitlines()
out = []
i = 0
changed = False
while i < len(lines):
    line = lines[i]
    if re.match(r'^\s*server\s*\{', line):
        block = [line]
        depth = line.count('{') - line.count('}')
        i += 1
        while i < len(lines) and depth > 0:
            block.append(lines[i])
            depth += lines[i].count('{') - lines[i].count('}')
            i += 1
        body = '\n'.join(block)
        if re.search(r'^\s*server_name\s+[^;]*\b' + re.escape(domain) + r'\b', body, re.M):
            root_re = re.compile(r'^(\s*root\s+)[^;]+;', re.M)
            if root_re.search(body):
                body = root_re.sub(lambda m: m.group(1) + docroot + ';', body, count=1)
            else:
                body = re.sub(r'(\bserver\s*\{)', r'\1\n    root ' + docroot + ';', body, count=1)
            for directive, value in (('client_max_body_size', '1100m'), ('client_body_timeout', '3600s')):
                pattern = re.compile(r'^\s*' + directive + r'\s+[^;]+;', re.M)
                if pattern.search(body):
                    body = pattern.sub('    ' + directive + ' ' + value + ';', body, count=1)
                else:
                    body = re.sub(r'(\bserver\s*\{)', r'\1\n    ' + directive + ' ' + value + ';', body, count=1)
            loc = re.search(r'(^\s*location\s+/\s*\{)(.*?)(^\s*\})', body, re.M | re.S)
            if loc:
                loc_body = loc.group(2)
                if re.search(r'^\s*try_files\s+[^;]+;', loc_body, re.M):
                    loc_body = re.sub(r'^\s*try_files\s+[^;]+;', '        try_files $uri $uri/ /index.php?$query_string;', loc_body, count=1, flags=re.M)
                else:
                    loc_body = '\n        try_files $uri $uri/ /index.php?$query_string;' + loc_body
                body = body[:loc.start(2)] + loc_body + body[loc.end(2):]
            else:
                pos = body.rfind('}')
                body = body[:pos] + '\n    location / {\n        try_files $uri $uri/ /index.php?$query_string;\n    }\n' + body[pos:]
            changed = True
        out.extend(body.splitlines())
    else:
        out.append(line)
        i += 1
if changed:
    Path(path).write_text('\n'.join(out) + '\n')
    print('[OK] Document root, rewrite, and upload limits updated in', path)
else:
    print('[WARN] Tidak menemukan server_name untuk', domain, 'di', path)
PYNGINX
        if nginx -t; then
            /etc/init.d/nginx reload
            echo "[OK] Konfigurasi Nginx diuji dan dimuat ulang."
        else
            echo "[WARN] nginx -t gagal. Periksa konfigurasi: $NGINX_SITE"
        fi
    else
        echo "[WARN] python3 tidak tersedia; konfigurasi Nginx perlu diperiksa manual."
    fi
else
    echo "[WARN] Vhost Nginx aaPanel tidak ditemukan: $NGINX_SITE"
    echo "[NEXT] Atur document root ke $APP_DIR_ABS/public dan tambahkan rewrite serta upload limits secara manual."
fi

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
PHP_INI="/www/server/php/$PHP_VERSION/etc/php.ini"
if [[ -f "$PHP_INI" ]]; then
    cp -a "$PHP_INI" "$PHP_INI.backup-pcd-$(date +%Y%m%d-%H%M%S)"
    set_php_ini() {
        local key="$1" value="$2"
        if grep -Eq "^[[:space:]]*\${key}[[:space:]]*=" "$PHP_INI"; then
            sed -i -E "s|^[[:space:]]*\${key}[[:space:]]*=.*|\${key} = \${value}|" "$PHP_INI"
        else
            printf '\n%s = %s\n' "$key" "$value" >> "$PHP_INI"
        fi
    }
    set_php_ini upload_max_filesize 1024M
    set_php_ini post_max_size 1100M
    set_php_ini memory_limit 512M
    set_php_ini max_execution_time 3600
    set_php_ini max_input_time 3600
    set_php_ini max_file_uploads 100
    echo "[OK] Batas upload PHP $PHP_VERSION disesuaikan:"
    grep -E '^(upload_max_filesize|post_max_size|memory_limit|max_execution_time|max_input_time|max_file_uploads)' "$PHP_INI"
    PHP_FPM_SERVICE="/etc/init.d/php-fpm-$PHP_VERSION"
    if [[ -x "$PHP_FPM_SERVICE" ]]; then
        "$PHP_FPM_SERVICE" restart || echo "[WARN] Restart PHP-FPM gagal; restart melalui aaPanel."
    else
        echo "[WARN] Service $PHP_FPM_SERVICE tidak ditemukan; restart PHP-FPM melalui aaPanel."
    fi
else
    echo "[WARN] php.ini aaPanel tidak ditemukan: $PHP_INI"
fi

echo
echo "[OK] Source Git : $SOURCE_DIR"
echo "[OK] Aplikasi   : $APP_DIR"
echo "[NEXT] Document root aaPanel: $APP_DIR/public"
echo "[NEXT] Pastikan database '$DB_NAME' sudah dibuat di aaPanel."
echo "[NEXT] Jalankan: cd '$APP_DIR' && php spark migrate"
echo "[NEXT] Setelah migration, buka https://$DOMAIN/setup untuk membuat admin pertama."
echo "[NOTE] Jangan gunakan untuk data penting sebelum pengujian backup dan keamanan selesai."
