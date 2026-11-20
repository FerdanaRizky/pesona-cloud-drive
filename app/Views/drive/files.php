<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 file-toolbar">
  <div><h1 class="h3 mb-1">File Saya</h1><p class="text-secondary mb-2">Kelola file dan folder pribadi Anda.</p>
  <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="/files">Drive Saya</a></li><?php foreach ($breadcrumbs as $crumb): ?><li class="breadcrumb-item active text-secondary"><?= esc($crumb['name']) ?></li><?php endforeach ?></ol></nav></div>
  <div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createFolderModal">＋ Tambah Folder</button><button type="button" class="btn btn-primary" id="openUploadPicker">↑ Upload</button></div>
</div>
<div class="modal fade" id="createFolderModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="/files/folders/create" method="post"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><div class="modal-header"><h5 class="modal-title">Tambah Folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label" for="newFolderName">Nama folder</label><input class="form-control" id="newFolderName" name="name" placeholder="Masukkan nama folder" maxlength="120" required></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Buat Folder</button></div></form></div></div></div>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="/files/upload" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><div class="modal-header"><h5 class="modal-title">Upload File</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label" for="modalUploadInput">Pilih file dari perangkat</label><input class="form-control" type="file" id="modalUploadInput" name="files[]" multiple required><div class="form-text" id="uploadSelectionInfo">Anda dapat memilih lebih dari satu file.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Mulai Upload</button></div></form></div></div></div>
<form method="get" action="/files" class="d-flex gap-2 mb-4" role="search"><input type="hidden" name="folder" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><input class="form-control" name="q" value="<?= esc($term ?? '') ?>" placeholder="Cari nama file atau folder..."><button class="btn btn-outline-secondary">Cari</button><?php if (!empty($term)): ?><a class="btn btn-outline-secondary" href="<?= !empty($currentFolder) ? '/files?folder=' . (int) $currentFolder['id'] : '/files' ?>">Reset</a><?php endif ?></form>
<script>document.addEventListener('DOMContentLoaded',function(){const modalEl=document.getElementById('uploadModal');document.getElementById('openUploadPicker')?.addEventListener('click',function(){bootstrap.Modal.getOrCreateInstance(modalEl).show();});document.getElementById('modalUploadInput')?.addEventListener('change',function(){const n=this.files?this.files.length:0;document.getElementById('uploadSelectionInfo').textContent=n?n+' file dipilih. Klik “Mulai Upload” untuk mengunggah.':'Anda dapat memilih lebih dari satu file.';});});</script>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="admin-stat"><div class="muted-label">Penyimpanan digunakan</div><div class="fs-4 fw-semibold"><?= number_format(((int) ($user['used_bytes'] ?? 0)) / 1073741824, 2) ?> GB</div></div></div>
  <div class="col-md-4"><div class="admin-stat"><div class="muted-label">Kuota akun</div><div class="fs-4 fw-semibold"><?= number_format(((int) ($user['quota_bytes'] ?? 0)) / 1073741824, 1) ?> GB</div></div></div>
  <div class="col-md-4"><div class="admin-stat"><div class="muted-label">File dan folder di lokasi ini</div><div class="fs-4 fw-semibold"><?= count($folders) + count($files) ?></div></div></div>
</div>

<?php if ($folders): ?>
<div class="mb-4"><h2 class="h6 text-secondary mb-3">Folder<?= !empty($term) ? " — hasil pencarian" : "" ?></h2><div class="row g-2">
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

<div class="card p-3"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">File<?= !empty($term) ? " — hasil pencarian" : "" ?></h2><span class="small text-secondary"><?= count($files) ?> item</span></div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama file</th><th>Tipe</th><th>Ukuran</th><th>Diunggah</th><th class="text-end">Aksi</th></tr></thead><tbody>
<?php foreach ($files as $f): ?>
<tr class="file-row"><td><div class="fw-medium"><?= !empty($f['is_starred']) ? '★ ' : '' ?><?php if (in_array($f['mime_type'], ['image/jpeg','image/png','image/gif','image/webp','application/pdf','video/mp4','video/webm','video/ogg'], true)): ?><a class="text-reset text-decoration-none" href="/files/preview/<?= (int) $f['id'] ?>"><?= esc($f['original_name']) ?></a><?php else: ?><a class="text-reset text-decoration-none" href="/files/download/<?= (int) $f['id'] ?>"><?= esc($f['original_name']) ?></a><?php endif ?></div><small class="text-secondary"><?= esc($f['mime_type']) ?></small></td><td><?= esc($f['extension'] ?: '—') ?></td><td><?= number_format(((int) $f['size_bytes']) / 1048576, 2) ?> MB</td><td><?= esc($f['created_at']) ?></td><td class="text-end text-nowrap">
<a class="btn btn-sm btn-outline-primary" href="/files/download/<?= (int) $f['id'] ?>">Unduh</a>
<div class="dropdown d-inline-block"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">⋮</button><div class="dropdown-menu dropdown-menu-end p-3" style="min-width:250px">
<form method="post" action="/files/rename/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><label class="form-label small">Nama file</label><input class="form-control form-control-sm mb-2" name="name" value="<?= esc($f['original_name']) ?>" maxlength="255" required><button class="btn btn-sm btn-primary w-100">Ubah nama</button></form>
<form method="post" action="/files/move/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><label class="form-label small">Pindahkan ke folder</label><select class="form-select form-select-sm mb-2" name="parent_id"><option value="0">Drive utama</option><?php foreach ($folders as $target): if ((int) $target['id'] !== (int) ($currentFolder['id'] ?? 0)): ?><option value="<?= (int) $target['id'] ?>"><?= esc($target['name']) ?></option><?php endif; endforeach ?></select><button class="btn btn-sm btn-outline-primary w-100">Pindahkan</button></form>
<form method="post" action="/files/share/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><label class="form-label small">Bagikan tautan (hanya lihat)</label><select class="form-select form-select-sm mb-2" name="expires_days"><option value="7">Berlaku 7 hari</option><option value="1">1 hari</option><option value="30">30 hari</option><option value="never">Tanpa kedaluwarsa</option></select><button class="btn btn-sm btn-outline-info w-100">Buat link berbagi</button></form>
<form method="post" action="/files/share/revoke/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger w-100">Cabut semua link berbagi</button></form>
<form method="post" action="/files/star/<?= (int) $f['id'] ?>" class="mb-2"><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning w-100"><?= !empty($f['is_starred']) ? 'Hapus favorit' : 'Tambahkan favorit' ?></button></form>
<form method="post" action="/files/delete/<?= (int) $f['id'] ?>" onsubmit="return confirm('Pindahkan file ke sampah?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger w-100">Pindahkan ke sampah</button></form>
</div></div></td></tr>
<?php endforeach ?>
<?php if (!$files): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada file di folder ini.</td></tr><?php endif ?>
</tbody></table></div></div>
<?= $this->endSection() ?>
