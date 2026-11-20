<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div><h1 class="h4 mb-1"><?= esc($file['original_name']) ?></h1><div class="small text-secondary"><?= esc($file['mime_type']) ?> · <?= number_format($file['size_bytes']/1048576,2) ?> MB</div></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="/files">Kembali</a><a class="btn btn-primary" href="/files/download/<?= (int)$file['id'] ?>">Unduh</a></div>
</div>
<div class="card p-3 preview-stage">
<?php if (!empty($officePreview)): ?>
  <iframe src="/files/preview-content/<?= (int)$file['id'] ?>" title="<?= esc($file['original_name']) ?>" style="width:100%;height:78vh;border:0;background:#fff"></iframe>
<?php elseif (str_starts_with($file['mime_type'], 'image/')): ?>
  <img src="/files/preview-content/<?= (int)$file['id'] ?>" alt="<?= esc($file['original_name']) ?>" class="img-fluid mx-auto" style="max-height:75vh;object-fit:contain">
<?php elseif ($file['mime_type'] === 'application/pdf'): ?>
  <iframe src="/files/preview-content/<?= (int)$file['id'] ?>" title="<?= esc($file['original_name']) ?>" style="width:100%;height:78vh;border:0;background:#fff"></iframe>
<?php elseif (str_starts_with($file['mime_type'], 'video/')): ?>
  <video controls playsinline preload="metadata" style="width:100%;max-height:78vh"><source src="/files/preview-content/<?= (int)$file['id'] ?>" type="<?= esc($file['mime_type']) ?>">Browser tidak mendukung preview video.</video>
<?php endif ?>
</div>
<?= $this->endSection() ?>
