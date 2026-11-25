#!/usr/bin/env bash
set -Eeuo pipefail

[[ $EUID -eq 0 ]] || { echo "[ERROR] Pemeriksaan FFmpeg harus dijalankan sebagai root."; exit 1; }

echo
echo "============================================================"
echo " PESONA CLOUD DRIVE - FFMPEG CHECK"
echo "============================================================"

# FFmpeg is required for video-related server support. Install it only
# when either ffmpeg or ffprobe is missing.
if command -v ffmpeg >/dev/null 2>&1 && command -v ffprobe >/dev/null 2>&1; then
    echo "[OK] FFmpeg dan ffprobe sudah terpasang; instalasi dilewati."
else
    echo "[INFO] FFmpeg/ffprobe belum lengkap. Mencoba instalasi otomatis..."
    if ! command -v apt-get >/dev/null 2>&1; then
        echo "[ERROR] apt-get tidak tersedia. Instal FFmpeg secara manual sebelum melanjutkan."
        exit 1
    fi

    export DEBIAN_FRONTEND=noninteractive
    apt-get update
    apt-get install -y ffmpeg

    if ! command -v ffmpeg >/dev/null 2>&1 || ! command -v ffprobe >/dev/null 2>&1; then
        echo "[ERROR] FFmpeg/ffprobe masih belum tersedia setelah instalasi. Periksa repository dan koneksi server."
        exit 1
    fi
    echo "[OK] FFmpeg dan ffprobe berhasil dipasang."
fi

echo "[OK] FFmpeg : $(command -v ffmpeg)"
ffmpeg -version 2>&1 | head -n 1 || true
echo "[OK] ffprobe: $(command -v ffprobe)"
echo "[SELESAI] Pemeriksaan FFmpeg selesai."
