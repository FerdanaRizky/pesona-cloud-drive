<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
  <div><div class="page-eyebrow">PENGELOLAAN DRIVE</div><h1 class="h3 mb-1">Sampah</h1><p class="text-secondary mb-0">Pulihkan item atau hapus permanen jika sudah tidak diperlukan.</p></div>
  <a class="btn btn-outline-secondary rounded-pill px-3" href="/files">← Kembali ke Drive</a>
</div>
<div class="trash-notice mb-4"><span class="trash-notice-icon">♲</span><div><strong>Item di sampah</strong><div class="small text-secondary">File masih menggunakan kuota sampai dihapus permanen. Penghapusan permanen tidak dapat dibatalkan.</div></div></div>
<div class="card p-3 p-lg-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">File</h2><span class="small text-secondary"><?= count($files) ?> file</span></div><span class="trash-section-icon">▤</span></div>
  <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama file</th><th>Ukuran</th><th>Dihapus / diubah</th><th class="text-end">Aksi</th></tr></thead><tbody>
  <?php foreach ($files as $f): ?>
  <tr class="file-row">
    <td><div class="d-flex align-items-center gap-2"><span class="trash-item-icon">▧</span><div class="min-width-0"><div class="fw-semibold text-break"><?= esc($f['original_name']) ?></div><small class="text-secondary">File</small></div></div></td>
    <td class="text-nowrap"><?= number_format($f['size_bytes']/1048576,2) ?> MB</td><td class="text-nowrap"><?= esc($f['updated_at']) ?></td>
    <td class="text-end"><div class="dropdown file-actions-dropdown floating-action-dropdown">
      <button class="btn btn-sm btn-outline-secondary action-menu-trigger" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Pilihan aksi file">⋮</button>
      <div class="dropdown-menu dropdown-menu-end file-action-menu">
        <form method="post" action="/trash/file/restore/<?= (int)$f['id'] ?>"><?= csrf_field() ?><button class="dropdown-item">↶ Pulihkan</button></form>
        <div class="dropdown-divider"></div>
        <form method="post" action="/trash/file/purge/<?= (int)$f['id'] ?>" onsubmit="return confirm('Hapus file secara permanen? Tindakan ini tidak dapat dibatalkan.')"><?= csrf_field() ?><button class="dropdown-item text-danger">⌫ Hapus permanen</button></form>
      </div>
    </div></td>
  </tr>
  <?php endforeach ?>
  <?php if (!$files): ?><tr><td colspan="4"><div class="empty-trash"><span>▤</span><strong>Belum ada file di sampah</strong><small>File yang Anda hapus akan muncul di sini.</small></div></td></tr><?php endif ?>
  </tbody></table></div>
</div>
<div class="card p-3 p-lg-4">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Folder</h2><span class="small text-secondary"><?= count($folders) ?> folder</span></div><span class="trash-section-icon">📁</span></div>
  <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama folder</th><th>Dihapus / diubah</th><th class="text-end">Aksi</th></tr></thead><tbody>
  <?php foreach ($folders as $f): ?>
  <tr class="file-row"><td><div class="d-flex align-items-center gap-2"><span class="trash-item-icon">📁</span><div class="fw-semibold text-break"><?= esc($f['name']) ?></div></div></td><td class="text-nowrap"><?= esc($f['updated_at']) ?></td>
    <td class="text-end"><div class="dropdown folder-actions floating-action-dropdown">
      <button class="btn btn-sm btn-outline-secondary action-menu-trigger" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Pilihan aksi folder">⋮</button>
      <div class="dropdown-menu dropdown-menu-end file-action-menu">
        <form method="post" action="/trash/folder/restore/<?= (int)$f['id'] ?>"><?= csrf_field() ?><button class="dropdown-item">↶ Pulihkan</button></form>
        <div class="dropdown-divider"></div>
        <form method="post" action="/trash/folder/purge/<?= (int)$f['id'] ?>" onsubmit="return confirm('Hapus folder dan seluruh isinya secara permanen? Tindakan ini tidak dapat dibatalkan.')"><?= csrf_field() ?><button class="dropdown-item text-danger">⌫ Hapus permanen</button></form>
      </div>
    </div></td>
  </tr>
  <?php endforeach ?>
  <?php if (!$folders): ?><tr><td colspan="3"><div class="empty-trash"><span>📁</span><strong>Belum ada folder di sampah</strong><small>Folder yang Anda hapus akan muncul di sini.</small></div></td></tr><?php endif ?>
  </tbody></table></div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('.floating-action-dropdown').forEach(function(dropdown){
  const trigger=dropdown.querySelector('[data-bs-toggle="dropdown"]'),menu=dropdown.querySelector('.file-action-menu');if(!trigger||!menu)return;let placeholder=null;
  dropdown.addEventListener('shown.bs.dropdown',function(){placeholder=document.createComment('trash-action-menu');menu.parentNode.insertBefore(placeholder,menu);document.body.appendChild(menu);const rect=trigger.getBoundingClientRect(),w=Math.min(Math.max(menu.offsetWidth,210),window.innerWidth-24),h=Math.min(menu.scrollHeight,window.innerHeight-24);let left=Math.max(12,Math.min(rect.right-w,window.innerWidth-w-12)),top=rect.bottom+8;if(top+h>window.innerHeight-12)top=Math.max(12,rect.top-h-8);menu.style.position='fixed';menu.style.left=left+'px';menu.style.top=top+'px';menu.style.right='auto';menu.style.bottom='auto';menu.style.transform='none';menu.style.zIndex='2000';});
  dropdown.addEventListener('hidden.bs.dropdown',function(){if(placeholder&&placeholder.parentNode){placeholder.parentNode.insertBefore(menu,placeholder);placeholder.remove();}menu.removeAttribute('style');placeholder=null;});
 });
});
</script>
<?= $this->endSection() ?>