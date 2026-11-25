#!/usr/bin/env bash
set -Eeuo pipefail

PRIVATE_STORAGE="${1:-}"
DOMAIN="${2:-}"
[[ $EUID -eq 0 ]] || { echo "[ERROR] Pemeriksaan video harus dijalankan sebagai root."; exit 1; }

echo
echo "============================================================"
echo " PESONA CLOUD DRIVE - VIDEO PREFLIGHT (MP4 / MOV)"
echo "============================================================"

# FFmpeg supplies ffprobe for inspecting container and codec metadata.
if ! command -v ffmpeg >/dev/null 2>&1 || ! command -v ffprobe >/dev/null 2>&1; then
    echo "[INFO] FFmpeg/ffprobe belum lengkap. Mencoba instalasi otomatis..."
    if command -v apt-get >/dev/null 2>&1; then
        export DEBIAN_FRONTEND=noninteractive
        if apt-get update && apt-get install -y ffmpeg; then
            echo "[OK] FFmpeg berhasil dipasang."
        else
            echo "[WARN] Instalasi FFmpeg otomatis gagal. Installer tetap melanjutkan; pasang FFmpeg setelah memeriksa koneksi/repository apt."
        fi
    else
        echo "[WARN] Package manager apt tidak tersedia. Pasang FFmpeg secara manual."
    fi
fi

if command -v ffmpeg >/dev/null 2>&1 && command -v ffprobe >/dev/null 2>&1; then
    echo "[OK] FFmpeg : $(command -v ffmpeg)"
    ffmpeg -version 2>&1 | head -n 1 || true
    echo "[OK] ffprobe: $(command -v ffprobe)"
else
    echo "[WARN] FFmpeg/ffprobe belum tersedia; pemeriksaan codec dilewati."
fi

echo
echo "=== PEMERIKSAAN KONFIGURASI NGINX AKTIF ==="
NGINX_BIN="$(command -v nginx || true)"
[[ -n "$NGINX_BIN" ]] || NGINX_BIN="/www/server/nginx/sbin/nginx"
if [[ -x "$NGINX_BIN" ]]; then
    if "$NGINX_BIN" -t; then
        echo "[OK] Konfigurasi Nginx valid."
    else
        echo "[WARN] nginx -t gagal. Periksa konfigurasi Nginx sebelum digunakan."
    fi
    NGINX_DUMP="$(mktemp)"
    if "$NGINX_BIN" -T >"$NGINX_DUMP" 2>&1; then
        echo "[OK] Konfigurasi aktif berhasil dibaca dengan nginx -T."
        echo "--- Server block domain dan directive terkait media/private delivery ---"
        if [[ -n "$DOMAIN" ]]; then
            grep -niE -B 3 -A 8 "server_name[[:space:]].*\b${DOMAIN//./\.}\b|X-Accel|mp4;|mp4_|sendfile|aio[[:space:]]|location.*internal" "$NGINX_DUMP" | head -n 100 || true
        else
            grep -niE 'X-Accel|mp4;|mp4_|sendfile|aio[[:space:]]|location.*internal' "$NGINX_DUMP" | head -n 100 || true
        fi
        echo "[INFO] Periksa apakah mekanisme private-file delivery/X-Accel memang diperlukan oleh konfigurasi aplikasi."
    else
        echo "[WARN] Tidak dapat membaca konfigurasi aktif melalui nginx -T."
        tail -n 20 "$NGINX_DUMP" || true
    fi
    rm -f "$NGINX_DUMP"
else
    echo "[WARN] Binary Nginx tidak ditemukan; pemeriksaan konfigurasi dilewati."
fi

echo
echo "=== PEMERIKSAAN CODEC MP4 / MOV ==="
if command -v ffprobe >/dev/null 2>&1 && [[ -d "$PRIVATE_STORAGE" ]]; then
    FOUND=0
    while IFS= read -r -d '' video; do
        FOUND=$((FOUND + 1))
        echo
        echo "[VIDEO] $video"
        ffprobe -v error -show_entries stream=codec_type,codec_name,profile,width,height -of compact=p=0:nk=0 "$video" || echo "[WARN] Metadata video tidak dapat dibaca."
        CODECS="$(ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 "$video" 2>/dev/null | head -n 1 || true)"
        AUDIO="$(ffprobe -v error -select_streams a:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 "$video" 2>/dev/null | head -n 1 || true)"
        if [[ "$CODECS" == "h264" && ( "$AUDIO" == "aac" || -z "$AUDIO" ) ]]; then
            echo "[OK] Codec video kompatibilitas luas: H.264; audio: ${AUDIO:-tanpa audio}."
        else
            echo "[WARN] Codec video/audio (${CODECS:-tidak diketahui}/${AUDIO:-tanpa audio}) mungkin tidak didukung sebagian browser mobile. Target kompatibilitas: H.264 + AAC."
        fi
        [[ "$FOUND" -ge 5 ]] && break
    done < <(find "$PRIVATE_STORAGE" -type f \( -iname '*.mp4' -o -iname '*.mov' \) -print0 2>/dev/null)
    if [[ "$FOUND" -eq 0 ]]; then
        echo "[INFO] Belum ada file MP4/MOV di storage untuk diperiksa."
        echo "[INFO] ffprobe siap digunakan saat file video pertama diunggah."
    else
        echo "[OK] Sampel diperiksa: $FOUND file (maksimal 5 file)."
    fi
else
    echo "[WARN] ffprobe atau direktori private storage belum tersedia; codec tidak diperiksa."
fi

echo
echo "[SELESAI] Pemeriksaan video MP4/MOV selesai. Tidak ada file video yang diubah atau dikonversi."
