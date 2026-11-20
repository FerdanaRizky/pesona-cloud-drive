<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 file-toolbar">
  <div>
    <h1 class="h3 mb-1">File Saya</h1>
    <p class="text-secondary mb-2">Kelola file dan folder pribadi Anda.</p>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="/files">Drive Saya</a></li><?php foreach ($breadcrumbs as $crumb): ?><li class="breadcrumb-item active text-secondary"><?= esc($crumb['name']) ?></li><?php endforeach ?></ol></nav>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <form action="/files/folders/create" method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><input class="form-control" name="name" placeholder="Nama folder baru" maxlength="120" required><button class="btn btn-outline-primary text-nowrap">+ Folder</button></form>
    <form action="/files/upload" method="post" enctype="multipart/form-data" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><input class="form-control" type="file" name="files[]" multiple required><button class="btn btn-primary">Upload</button></form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="admin-stat"><div class="muted-label">Penyimpanan digunakan</div><div class="fs-4 fw-semibold"><?= number_format(((int) ($user['used_bytes'] ?? 0)) / 1073741824, 2) ?> GB</div></div></div>
  <div class="col-md-4"><div class="admin-stat"><div class="muted-label">Kuota akun</div><div class="fs-4 fw-semibold"><?= number_format(((int) ($user['quota_bytes'] ?? 0)) / 1073741824, 1) ?> GB</div></div></div>
  <div class="col-md-4"><div class="admin-stat"><div class="muted-label">File dan folder di lokasi ini</div><div class="fs-4 fw-semibold"><?= count($folders) + count($files) ?></div></div></div>
</div>

<?php if ($folders): ?>
<div class="mb-4"><h2 class="h6 text-secondary mb-3">Folder</h2><div class="row g-2">
<?php foreach ($folders as $folder): ?>
<div class="col-sm-6 col-xl-4"><div class="folder-tile">
  <a class="flex-grow-1 text-decoration-none text-reset" href="/files?folder=<?= (int) $folder['id'] ?>">📁 <?= esc($folder['name']) ?></a>
  <div class="dropdown"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">⋮</button><div class="dropdown-menu dropdown-menu-end p-3" style="min-width:230px">
    <form method="post" action="/files/folders/rename/<?= (int) $folder['id'] ?>" class="mb-2"><?= csrf_field() ?><label class="form-label small">Ubah nama</label><input class="form-control form-control-sm mb-2" name="name" value="<?= esc($folder['name']) ?>" maxlength="120" required><button class="btn btn-sm btn-primary w-100">Simpan</button></form>
    <form method="post" action="/files/folders/delete/<?= (int) $folder['id'] ?>" onsubmit="return confirm('Hapus folder kosong ini?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger w-100">Hapus folder kosong</button></form>
  </div></div>
</div></div>
<?php endforeach ?></div></div>
<?php endif ?>

<div class="card p-3"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">File</h2><span class="small text-secondary"><?= count($files) ?> item</span></div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama file</th><th>Tipe</th><th>Ukuran</th><th>Diunggah</th><th class="text-end">Aksi</th></tr></thead><tbody>
<?php foreach ($files as $f): ?>
<tr class="file-row"><td><div class="fw-medium"><?= !empty($f['is_starred']) ? '★ ' : '' ?><?= esc($f['original_name']) ?></div><small class="text-secondary"><?= esc($f['mime_type']) ?></small></td><td><?= esc($f['extension'] ?: '—') ?></td><td><?= number_format(((int) $f['size_bytes']) / 1048576, 2) ?> MB</td><td><?= esc($f['created_at']) ?></td><td class="text-end text-nowrap">
<a class="btn btn-sm btn-outline-primary" href="/files/download/<?= (int) $f['id'] ?>">Unduh</a>
<div class="dropdown d-inline-block"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">⋮</button><div class="dropdown-menu dropdown-menu-end p-3" style="min-width:250px">
<form method="post" action="/files/rename/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><label class="form-label small">Nama file</label><input class="form-control form-control-sm mb-2" name="name" value="<?= esc($f['original_name']) ?>" maxlength="255" required><button class="btn btn-sm btn-primary w-100">Ubah nama</button></form>
<form method="post" action="/files/move/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><label class="form-label small">Pindahkan ke folder</label><select class="form-select form-select-sm mb-2" name="parent_id"><option value="0">Drive utama</option><?php foreach ($folders as $target): if ((int) $target['id'] !== (int) ($currentFolder['id'] ?? 0)): ?><option value="<?= (int) $target['id'] ?>"><?= esc($target['name']) ?></option><?php endif; endforeach ?></select><button class="btn btn-sm btn-outline-primary w-100">Pindahkan</button></form>
<form method="post" action="/files/star/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning w-100"><?= !empty($f['is_starred']) ? 'Hapus favorit' : 'Tambahkan favorit' ?></button></form>
<form method="post" action="/files/delete/<?= (int) $f['id'] ?>" onsubmit="return confirm('Pindahkan file ke sampah?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger w-100">Pindahkan ke sampah</button></form>
</div></div></td></tr>
<?php endforeach ?>
<?php if (!$files): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada file di folder ini.</td></tr><?php endif ?>
</tbody></table></div></div>
<?= $this->endSection() ?>
