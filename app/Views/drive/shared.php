<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{min-height:100vh;background:#0b1220;color:#e7edf7}.share-shell{max-width:1100px;margin:auto;padding:32px 18px}.share-card{background:#111d30;border:1px solid #26364f;border-radius:18px}.preview{width:100%;max-height:76vh;object-fit:contain;background:#080e18;border-radius:12px}
</style>
</head>
<body>
<main class="share-shell">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div><div class="small text-secondary mb-1">PESONA CLOUD DRIVE · FILE DIBAGIKAN</div><h1 class="h4 mb-1"><?= esc($share['original_name']) ?></h1><div class="small text-secondary"><?= esc($share['mime_type']) ?> · <?= number_format($share['size_bytes']/1048576,2) ?> MB</div></div>
    <a class="btn btn-primary" href="/s/<?= esc($token) ?>/download" download="<?= esc($share['original_name']) ?>">Unduh file</a>
  </div>
  <section class="share-card p-3">
    <?php if ($canPreview && str_starts_with($share['mime_type'], 'image/')): ?>
      <img class="preview d-block mx-auto" src="/s/<?= esc($token) ?>/content" alt="<?= esc($share['original_name']) ?>">
    <?php elseif ($share['mime_type'] === 'application/pdf'): ?>
      <iframe class="preview" style="height:76vh;border:0" src="/s/<?= esc($token) ?>/content" title="<?= esc($share['original_name']) ?>"></iframe>
    <?php elseif (str_starts_with($share['mime_type'], 'video/')): ?>
      <video class="preview" style="height:76vh" controls playsinline preload="metadata"><source src="/s/<?= esc($token) ?>/content" type="<?= esc($share['mime_type']) ?>"></video>
    <?php else: ?>
      <div class="text-center py-5"><div class="display-5 mb-3">📄</div><p class="text-secondary">Preview tidak tersedia untuk tipe file ini.</p><a class="btn btn-primary" href="/s/<?= esc($token) ?>/download" download>Unduh file</a></div>
    <?php endif ?>
  </section>
  <p class="text-center text-secondary small mt-4">Tautan ini dibuat oleh pemilik file. Akses dapat dicabut atau kedaluwarsa kapan saja.</p>
</main>
</body>
</html>
