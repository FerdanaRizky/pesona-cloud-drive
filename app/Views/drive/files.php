<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 file-toolbar">
  <div><h1 class="h3 mb-1">File Saya</h1><p class="text-secondary mb-2">Kelola file dan folder pribadi Anda.</p>
  <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="/files">Drive Saya</a></li><?php foreach ($breadcrumbs as $crumb): ?><li class="breadcrumb-item active text-secondary"><?= esc($crumb['name']) ?></li><?php endforeach ?></ol></nav></div>
  <div class="d-flex flex-wrap gap-2 align-items-center"><div class="view-switcher" role="group" aria-label="Tampilan file"><button type="button" class="view-switch-btn" data-drive-view="list" aria-pressed="true" title="Tampilan daftar">☷</button><button type="button" class="view-switch-btn" data-drive-view="grid" aria-pressed="false" title="Tampilan grid">▦</button></div><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createFolderModal">＋ Tambah Folder</button><button type="button" class="btn btn-primary" id="openUploadPicker">↑ Upload</button></div>
</div>
<div class="modal fade" id="createFolderModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="/files/folders/create" method="post"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><div class="modal-header"><h5 class="modal-title">Tambah Folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label" for="newFolderName">Nama folder</label><input class="form-control" id="newFolderName" name="name" placeholder="Masukkan nama folder" maxlength="120" required></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Buat Folder</button></div></form></div></div></div>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="/files/upload" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><div class="modal-header"><h5 class="modal-title">Upload File</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label" for="modalUploadInput">Pilih file dari perangkat</label><input class="form-control" type="file" id="modalUploadInput" name="files[]" multiple required><div class="form-text" id="uploadSelectionInfo">Anda dapat memilih lebih dari satu file.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Mulai Upload</button></div></form></div></div></div>
<div class="modal fade" id="fileActionModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="fileActionForm" method="post"><?= csrf_field() ?><div class="modal-header"><h5 class="modal-title" id="fileActionTitle">Aksi File</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div id="renameField"><label class="form-label" for="actionFileName">Nama file baru</label><input class="form-control" id="actionFileName" name="name" maxlength="255"></div><div id="moveField" class="d-none"><label class="form-label" for="actionFolder">Pilih folder tujuan</label><select class="form-select" id="actionFolder" name="parent_id"></select><div class="form-text">Pilih Drive utama jika ingin memindahkan file ke luar folder.</div></div><div id="shareField" class="d-none"><label class="form-label" for="actionExpiry">Masa berlaku tautan</label><select class="form-select" id="actionExpiry" name="expires_days"><option value="1">1 hari</option><option value="7" selected>7 hari</option><option value="30">30 hari</option><option value="never">Tanpa kedaluwarsa</option></select><div class="form-text">Siapa pun yang memiliki tautan dapat melihat file.</div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="fileActionSubmit" type="submit">Simpan</button></div></form></div></div></div>
<form method="get" action="/files" class="d-flex gap-2 mb-4" role="search"><input type="hidden" name="folder" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><input class="form-control" name="q" value="<?= esc($term ?? '') ?>" placeholder="Cari nama file atau folder..."><button class="btn btn-outline-secondary">Cari</button><?php if (!empty($term)): ?><a class="btn btn-outline-secondary" href="<?= !empty($currentFolder) ? '/files?folder=' . (int) $currentFolder['id'] : '/files' ?>">Reset</a><?php endif ?></form>
<script>document.addEventListener('DOMContentLoaded',function(){const viewRoot=document.getElementById('driveViewRoot');const viewButtons=document.querySelectorAll('[data-drive-view]');const savedView=localStorage.getItem('pcd-drive-view')||'list';function setDriveView(view){if(!viewRoot)return;viewRoot.dataset.driveView=view;viewButtons.forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.driveView===view)));localStorage.setItem('pcd-drive-view',view);}viewButtons.forEach(b=>b.addEventListener('click',()=>setDriveView(b.dataset.driveView)));setDriveView(savedView);
const selectionForm=document.getElementById('bulkActionForm'),selectionCount=document.getElementById('bulkSelectionCount'),selectAll=document.getElementById('selectAllFiles'),selectAllFolders=document.getElementById('selectAllFolders'),hiddenIds=document.getElementById('bulkSelectedIds'),menuTrigger=document.getElementById('bulkActionsMenu'),hint=document.getElementById('bulkSelectionHint');
function refreshSelection(){
 const selected=[...document.querySelectorAll('.drive-item-select:checked')],fileCount=selected.filter(x=>x.dataset.itemType==='file').length,folderCount=selected.length-fileCount;
 selectionCount.textContent=selected.length+' item dipilih ('+fileCount+' file, '+folderCount+' folder)';
 hint.textContent='Pilih aksi melalui menu tiga titik. Aksi file akan diterapkan pada file yang dipilih.';
 hiddenIds.innerHTML='';
 selected.forEach(box=>{const hidden=document.createElement('input');hidden.type='hidden';hidden.name=box.dataset.itemType==='folder'?'folder_ids[]':'file_ids[]';hidden.value=box.value;hiddenIds.appendChild(hidden);});
 if(menuTrigger)menuTrigger.disabled=!selected.length;
 const selectionBar=document.querySelector(".bulk-selection-bar");
 if(selectionBar){selectionBar.classList.toggle("is-selection-active",selected.length>0);selectionBar.setAttribute("aria-hidden",selected.length===0?"true":"false");}
 if(selectAll){const all=[...document.querySelectorAll('.drive-item-select[data-item-type="file"]')];selectAll.checked=all.length>0&&all.every(x=>x.checked);selectAll.indeterminate=all.some(x=>x.checked)&&!selectAll.checked;}
 if(selectAllFolders){const all=[...document.querySelectorAll('.drive-item-select[data-item-type="folder"]')];selectAllFolders.checked=all.length>0&&all.every(x=>x.checked);selectAllFolders.indeterminate=all.some(x=>x.checked)&&!selectAllFolders.checked;}
}
document.querySelectorAll('.drive-item-select').forEach(box=>box.addEventListener('change',refreshSelection));
selectAll?.addEventListener('change',()=>{document.querySelectorAll('.drive-item-select[data-item-type="file"]').forEach(box=>box.checked=selectAll.checked);refreshSelection();});
selectAllFolders?.addEventListener('change',()=>{document.querySelectorAll('.drive-item-select[data-item-type="folder"]').forEach(box=>box.checked=selectAllFolders.checked);refreshSelection();});
document.getElementById('clearDriveSelection')?.addEventListener('click',()=>{document.querySelectorAll('.drive-item-select').forEach(box=>box.checked=false);refreshSelection();});
const bulkModal=document.getElementById('bulkActionModal');
document.querySelectorAll('[data-bulk-action]').forEach(button=>button.addEventListener('click',function(){
 const action=this.dataset.bulkAction,count=document.querySelectorAll('.drive-item-select:checked').length;
 if(!count)return;
 const shareField=document.getElementById('bulkShareField'),moveField=document.getElementById('bulkMoveField'),title=document.getElementById('bulkActionModalTitle'),confirmButton=document.getElementById('bulkActionConfirm');
 shareField.classList.toggle('d-none',action!=='share');moveField.classList.toggle('d-none',action!=='move');
 if(action==='share'||action==='move'){
  title.textContent=action==='share'?'Bagikan file terpilih':'Pindahkan file terpilih';
  confirmButton.textContent=action==='share'?'Buat tautan':'Pindahkan file';
  selectionForm.dataset.pendingAction=action;
  bootstrap.Modal.getOrCreateInstance(bulkModal).show();
  return;
 }
 if(action==='download'){
  if(!confirm('Unduh file yang dipilih? Folder yang dipilih akan dilewati.'))return;
  selectionForm.action='/files/bulk-download';selectionForm.submit();return;
 }
 if(action==='trash'){
  if(!confirm('Pindahkan semua file dan folder terpilih ke Sampah?'))return;
  selectionForm.action='/files/bulk-trash';selectionForm.submit();
 }
}));
selectionForm?.addEventListener('submit',function(event){
 const action=this.dataset.pendingAction;
 if(action==='share')this.action='/files/bulk-share';
 if(action==='move')this.action='/files/bulk-move';
 this.dataset.pendingAction='';
});
bulkModal?.addEventListener('hidden.bs.modal',()=>{selectionForm.dataset.pendingAction='';});
document.getElementById('bulkActionConfirm')?.addEventListener('click',function(){const action=selectionForm.dataset.pendingAction;if(action==='share')selectionForm.action='/files/bulk-share';if(action==='move')selectionForm.action='/files/bulk-move';});
refreshSelection();
const modalEl=document.getElementById('uploadModal');document.getElementById('openUploadPicker')?.addEventListener('click',function(){bootstrap.Modal.getOrCreateInstance(modalEl).show();});document.getElementById('modalUploadInput')?.addEventListener('change',function(){const n=this.files?this.files.length:0;document.getElementById('uploadSelectionInfo').textContent=n?n+' file dipilih. Klik “Mulai Upload” untuk mengunggah.':'Anda dapat memilih lebih dari satu file.';});document.querySelectorAll('.floating-action-dropdown').forEach(function(dropdown){const trigger=dropdown.querySelector('[data-bs-toggle="dropdown"]'),menu=dropdown.querySelector('.file-action-menu');if(!trigger||!menu)return;let placeholder=null;dropdown.addEventListener('shown.bs.dropdown',function(){placeholder=document.createComment('action-menu-position');menu.parentNode.insertBefore(placeholder,menu);document.body.appendChild(menu);const rect=trigger.getBoundingClientRect(),menuWidth=Math.min(Math.max(menu.offsetWidth,210),window.innerWidth-24),menuHeight=Math.min(menu.scrollHeight,window.innerHeight-24);let left=Math.max(12,Math.min(rect.right-menuWidth,window.innerWidth-menuWidth-12));let top=rect.bottom+8;if(top+menuHeight>window.innerHeight-12)top=Math.max(12,rect.top-menuHeight-8);menu.style.position='fixed';menu.style.left=left+'px';menu.style.top=top+'px';menu.style.right='auto';menu.style.bottom='auto';menu.style.transform='none';menu.style.zIndex='2000';});dropdown.addEventListener('hidden.bs.dropdown',function(){if(placeholder&&placeholder.parentNode){placeholder.parentNode.insertBefore(menu,placeholder);placeholder.remove();}menu.removeAttribute('style');placeholder=null;});});document.querySelectorAll('[data-file-action]').forEach(function(button){button.addEventListener('click',function(){const kind=this.dataset.fileAction,form=document.getElementById('fileActionForm'),title=document.getElementById('fileActionTitle'),submit=document.getElementById('fileActionSubmit'),rename=document.getElementById('renameField'),move=document.getElementById('moveField'),share=document.getElementById('shareField');form.action=this.dataset.url;rename.classList.toggle('d-none',kind!=='rename');move.classList.toggle('d-none',kind!=='move');share.classList.toggle('d-none',kind!=='share');if(kind==='rename'){title.textContent='Ganti Nama File';submit.textContent='Simpan Nama';document.getElementById('actionFileName').value=this.dataset.name||'';document.getElementById('actionFileName').maxLength=Number(this.dataset.maxlength||255);}if(kind==='move'){title.textContent='Pindah Folder';submit.textContent='Pindahkan File';const select=document.getElementById('actionFolder');select.innerHTML='<option value="0">Drive utama</option>';(JSON.parse(this.dataset.folders||'[]')).forEach(function(folder){const option=document.createElement('option');option.value=folder.id;option.textContent=folder.name;select.appendChild(option);});}if(kind==='share'){title.textContent='Bagi Tautan';submit.textContent='Buat Tautan';}bootstrap.Modal.getOrCreateInstance(document.getElementById('fileActionModal')).show();});});});</script>

<div id="driveViewRoot" data-drive-view="list">

<form id="bulkActionForm" method="post" action="/files/bulk-trash" class="bulk-selection-bar mb-3" aria-hidden="true">
<?= csrf_field() ?>
<div id="bulkSelectedIds"></div>
<div class="bulk-selection-info"><span class="bulk-selection-icon">✓</span><div><strong id="bulkSelectionCount">0 item dipilih</strong><small id="bulkSelectionHint" class="text-secondary d-block">Pilih file atau folder, lalu buka menu aksi.</small></div></div>
<div class="bulk-action-controls">
  <div class="dropdown floating-action-dropdown">
    <button type="button" class="btn action-menu-trigger bulk-menu-trigger" id="bulkActionsMenu" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi item terpilih" disabled>⋮</button>
    <div class="dropdown-menu dropdown-menu-end file-action-menu bulk-action-menu">
      <button type="button" class="dropdown-item" data-bulk-action="share">♧ Bagikan</button>
      <button type="button" class="dropdown-item" data-bulk-action="download">↓ Download</button>
      <button type="button" class="dropdown-item" data-bulk-action="move">↗ Pindah ke folder</button>
      <div class="dropdown-divider"></div>
      <button type="button" class="dropdown-item text-danger" data-bulk-action="trash">⌫ Pindahkan ke Sampah</button>
    </div>
  </div>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="clearDriveSelection">Batal pilih</button>
</div>
<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="bulkActionModalTitle">Aksi item terpilih</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
  <div class="modal-body">
   <div id="bulkShareField" class="d-none"><label class="form-label" for="bulkShareExpiry">Masa berlaku tautan</label><select class="form-select" name="expires_days" id="bulkShareExpiry"><option value="1">1 hari</option><option value="7" selected>7 hari</option><option value="30">30 hari</option><option value="never">Tanpa kedaluwarsa</option></select><div class="form-text">Tautan akan dibuat untuk file yang dipilih. Folder yang dipilih tidak diproses sebagai file.</div></div>
   <div id="bulkMoveField" class="d-none"><label class="form-label" for="bulkMoveFolder">Pilih folder tujuan</label><select class="form-select" name="parent_id" id="bulkMoveFolder"><option value="0">Drive utama</option><?php foreach (($allFolders ?? []) as $targetFolder): ?><option value="<?= (int)$targetFolder['id'] ?>"><?= esc($targetFolder['name']) ?></option><?php endforeach ?></select><div class="form-text">File yang dipilih akan dipindahkan ke folder tujuan. Folder yang dipilih tidak dipindahkan.</div></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" id="bulkActionConfirm" class="btn btn-primary">Lanjutkan</button></div>
 </div></div>
</div>
</form>



<?php if ($folders): ?>
<div class="mb-4 drive-folders-section"><div class="d-flex align-items-center gap-2 mb-3"><h2 class="h6 text-secondary mb-0">Folder<?= !empty($term) ? " — hasil pencarian" : "" ?></h2><label class="small text-secondary d-inline-flex align-items-center gap-2 ms-auto"><input type="checkbox" class="form-check-input m-0" id="selectAllFolders"> Pilih semua folder</label></div><div class="row g-2">
<?php foreach ($folders as $folder): ?>
<div class="col-sm-6 col-xl-4"><div class="folder-tile">
  <label class="multi-select-control" title="Pilih folder"><input type="checkbox" class="drive-item-select" data-item-type="folder" value="<?= (int) $folder['id'] ?>"><span></span></label>
  <a class="flex-grow-1 text-decoration-none text-reset" href="/files?folder=<?= (int) $folder['id'] ?>">📁 <?= esc($folder['name']) ?></a>
  <div class="dropdown folder-actions floating-action-dropdown"><button class="btn btn-sm btn-outline-secondary action-menu-trigger" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Pilihan aksi folder">⋮</button><div class="dropdown-menu dropdown-menu-end file-action-menu">
    <button type="button" class="dropdown-item" data-file-action="rename" data-url="/files/folders/rename/<?= (int) $folder['id'] ?>" data-name="<?= esc($folder['name'], 'attr') ?>" data-maxlength="120">✎ Ganti nama</button>
    <form method="post" action="/files/folders/delete/<?= (int) $folder['id'] ?>" onsubmit="return confirm('Hapus folder kosong ini?')"><?= csrf_field() ?><button class="dropdown-item text-danger">⌫ Hapus</button></form>
  </div></div>
</div></div>
<?php endforeach ?></div></div>
<?php endif ?>

<div class="card p-3 drive-files-card"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">File<?= !empty($term) ? " — hasil pencarian" : "" ?></h2><span class="small text-secondary"><?= count($files) ?> item</span></div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="selection-column"><input type="checkbox" class="form-check-input" id="selectAllFiles" aria-label="Pilih semua file"></th><th>Nama file</th><th>Tipe</th><th>Ukuran</th><th>Diunggah</th><th class="text-end">Aksi</th></tr></thead><tbody>
<?php foreach ($files as $f): ?>
<tr class="file-row"><td class="selection-column"><input type="checkbox" class="form-check-input drive-item-select" data-item-type="file" value="<?= (int) $f['id'] ?>" aria-label="Pilih <?= esc($f['original_name'], 'attr') ?>"></td><td><div class="d-flex align-items-center gap-2"><div class="file-thumbnail flex-shrink-0"><?php if (in_array($f['mime_type'], ['image/jpeg','image/png','image/gif','image/webp'], true)): ?><img src="/files/preview-content/<?= (int)$f['id'] ?>" alt="" loading="lazy"><?php else: ?><span><?= in_array($f['extension'], ['doc','docx','odt']) ? 'DOC' : (in_array($f['extension'], ['xls','xlsx','ods']) ? 'XLS' : (in_array($f['extension'], ['ppt','pptx','odp']) ? 'PPT' : ($f['mime_type'] === 'application/pdf' ? 'PDF' : 'FILE'))) ?></span><?php endif ?></div><div class="min-width-0"><div class="fw-medium"><?= !empty($f['is_starred']) ? '★ ' : '' ?><?php if (in_array($f['mime_type'], ['image/jpeg','image/png','image/gif','image/webp','application/pdf','video/mp4','video/webm','video/ogg'], true) || in_array($f['extension'], ['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp'], true)): ?><a class="text-reset text-decoration-none" href="/files/preview/<?= (int) $f['id'] ?>"><?= esc($f['original_name']) ?></a><?php else: ?><a class="text-reset text-decoration-none" href="/files/download/<?= (int) $f['id'] ?>"><?= esc($f['original_name']) ?></a><?php endif ?></div><small class="text-secondary"><?= esc($f['mime_type']) ?></small></div></div></td><td><?= esc($f['extension'] ?: '—') ?></td><td><?= number_format(((int) $f['size_bytes']) / 1048576, 2) ?> MB</td><td><?= esc($f['created_at']) ?></td><td class="text-end"><div class="dropdown file-actions-dropdown floating-action-dropdown">
<button class="btn btn-sm btn-outline-secondary action-menu-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Pilihan aksi file">⋮</button>
<div class="dropdown-menu dropdown-menu-end file-action-menu">
<?php if (in_array($f['mime_type'], ['image/jpeg','image/png','image/gif','image/webp','application/pdf','video/mp4','video/webm','video/ogg'], true)): ?><a class="dropdown-item" href="/files/preview/<?= (int) $f['id'] ?>">◉ Preview</a><?php endif ?>
<a class="dropdown-item" href="/files/download/<?= (int) $f['id'] ?>">↓ Unduh</a>
<button type="button" class="dropdown-item" data-file-action="rename" data-url="/files/rename/<?= (int) $f['id'] ?>" data-name="<?= esc($f['original_name'], 'attr') ?>">✎ Ganti nama</button>
<button type="button" class="dropdown-item" data-file-action="move" data-url="/files/move/<?= (int) $f['id'] ?>" data-folders="<?= esc(json_encode($allFolders ?? $folders), 'attr') ?>">↗ Pindah folder</button>
<button type="button" class="dropdown-item" data-file-action="share" data-url="/files/share/<?= (int) $f['id'] ?>">♧ Bagi tautan</button>
<form method="post" action="/files/star/<?= (int) $f['id'] ?>"><?= csrf_field() ?><button class="dropdown-item <?= !empty($f['is_starred']) ? 'text-warning' : '' ?>"><?= !empty($f['is_starred']) ? '★ Hapus favorit' : '★ Tambahkan favorit' ?></button></form>
<?php if (!empty($f['has_share'])): ?><form method="post" action="/files/share/revoke/<?= (int) $f['id'] ?>" onsubmit="return confirm('Cabut semua tautan berbagi untuk file ini?')"><?= csrf_field() ?><button class="dropdown-item text-info">⛓ Cabut tautan berbagi</button></form><?php endif ?>
<div class="dropdown-divider"></div>
<form method="post" action="/files/delete/<?= (int) $f['id'] ?>" onsubmit="return confirm('Hapus file ini? File akan dipindahkan ke Sampah.')"><?= csrf_field() ?><button class="dropdown-item text-danger">⌫ Hapus</button></form>
</div></div>
<?php endforeach ?>
<?php if (!$files): ?><tr><td colspan="6" class="text-center text-secondary py-4">Belum ada file di folder ini.</td></tr><?php endif ?>
</tbody></table></div></div>
</div><!-- /driveViewRoot -->
<?= $this->endSection() ?>
