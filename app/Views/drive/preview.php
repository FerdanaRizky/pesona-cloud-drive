<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$fileName = (string) ($file['original_name'] ?? '');
$fileExtension = strtolower((string) ($file['extension'] ?? '')) ?: strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
$videoMimeTypes = [
    'mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'mov' => 'video/quicktime',
    'webm' => 'video/webm', 'ogv' => 'video/ogg', 'ogg' => 'video/ogg',
    '3gp' => 'video/3gpp', '3g2' => 'video/3gpp2',
    'avi' => 'video/x-msvideo', 'mkv' => 'video/x-matroska',
    'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
    'wmv' => 'video/x-ms-wmv', 'flv' => 'video/x-flv',
    'ts' => 'video/mp2t', 'mts' => 'video/mp2t', 'm2ts' => 'video/mp2t',
    'vob' => 'video/dvd', 'asf' => 'video/x-ms-asf', 'divx' => 'video/divx',
];
$storedMime = strtolower((string) ($file['mime_type'] ?? ''));
$isVideo = str_starts_with($storedMime, 'video/') || isset($videoMimeTypes[$fileExtension]);
$videoSourceMime = str_starts_with($storedMime, 'video/') ? $storedMime : ($videoMimeTypes[$fileExtension] ?? '');
?>
<style>
.preview-stage { min-width: 0; }
.preview-video-shell {
  width: 100%;
  min-width: 0;
  min-height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: .75rem;
  background: #000;
}
.preview-video {
  display: block;
  width: 100%;
  height: auto;
  max-height: min(78vh, 900px);
  object-fit: contain;
  background: #000;
  touch-action: manipulation;
}
@supports (height: 100dvh) {
  .preview-video { max-height: min(78dvh, 900px); }
}
@media (max-width: 767.98px) {
  .preview-stage { padding: .5rem !important; }
  .preview-video-shell { min-height: 160px; border-radius: .5rem; }
  .preview-video { width: 100%; max-height: calc(100vh - 190px); }
  @supports (height: 100dvh) {
    .preview-video { max-height: calc(100dvh - 190px); }
  }
  .preview-header { align-items: flex-start !important; }
  .preview-title { min-width: 0; overflow-wrap: anywhere; }
  .preview-actions { flex-shrink: 0; }
}
@media (max-width: 420px) {
  .preview-video { max-height: calc(100vh - 165px); }
  @supports (height: 100dvh) {
    .preview-video { max-height: calc(100dvh - 165px); }
  }
}
@media (orientation: landscape) and (max-height: 600px) {
  .preview-video { max-height: 68vh; }
  @supports (height: 100dvh) {
    .preview-video { max-height: 68dvh; }
  }
}
</style>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 preview-header">
  <div class="preview-title"><h1 class="h4 mb-1"><?= esc($fileName) ?></h1><div class="small text-secondary"><?= esc($file['mime_type']) ?> · <?= number_format($file['size_bytes']/1048576,2) ?> MB</div></div>
  <div class="d-flex gap-2 preview-actions"><a class="btn btn-outline-secondary" href="<?= !empty($trashMode) ? '/trash' : '/files' ?>">Kembali</a><?php if (empty($trashMode)): ?><a class="btn btn-primary" href="/files/download/<?= (int)$file['id'] ?>">Unduh</a><?php endif ?></div>
</div>
<div class="card p-3 preview-stage">
<?php if (!empty($officePreview)): ?>
  <iframe src="/files/preview-content/<?= (int)$file['id'] ?><?= !empty($trashMode) ? '?trash=1' : '' ?>" title="<?= esc($fileName) ?>" style="width:100%;height:78vh;border:0;background:#fff"></iframe>
<?php elseif (str_starts_with($storedMime, 'image/')): ?>
  <img src="/files/preview-content/<?= (int)$file['id'] ?><?= !empty($trashMode) ? '?trash=1' : '' ?>" alt="<?= esc($fileName) ?>" class="img-fluid mx-auto" style="max-height:75vh;object-fit:contain">
<?php elseif ($storedMime === 'application/pdf'): ?>
  <iframe src="/files/preview-content/<?= (int)$file['id'] ?><?= !empty($trashMode) ? '?trash=1' : '' ?>" title="<?= esc($fileName) ?>" style="width:100%;height:78vh;border:0;background:#fff"></iframe>
<?php elseif ($isVideo): ?>
  <div class="preview-video-shell">
    <video class="preview-video" controls playsinline webkit-playsinline preload="metadata" x-webkit-airplay="allow" aria-label="Pemutar video: <?= esc($fileName, 'attr') ?>">
      <source src="/files/preview-content/<?= (int) $file['id'] ?><?= !empty($trashMode) ? '?trash=1' : '' ?>"<?= $videoSourceMime !== '' ? ' type="' . esc($videoSourceMime, 'attr') . '"' : '' ?>>
      Browser Anda tidak mendukung pemutar video atau codec file ini. Coba unduh file untuk memutarnya dengan aplikasi pemutar video.
    </video>
  </div>
  <p class="small text-secondary mt-2 mb-0">Jika video tidak dapat diputar di iPhone atau Android, kemungkinan format atau codec video belum didukung oleh browser perangkat.</p>
<?php endif ?>
</div>
<?= $this->endSection() ?>
