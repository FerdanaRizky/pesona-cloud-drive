<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$menuIcon = static function (string $name): string {
    $paths = [
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.7 10.7 6.6-4.4M8.7 13.3l6.6 4.4"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/>',
        'move' => '<path d="M3 7h7l2 2h9v11H3z"/><path d="M12 12v5m-3-3 3 3 3-3"/>',
        'trash' => '<path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/>',
        'rename' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'preview' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.1 0l3-3A5 5 0 0 0 13 2.9l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.1 0l-3 3A5 5 0 0 0 11 21.1l1.7-1.7"/>',
        'restore' => '<path d="M3 7v5h5"/><path d="M4.5 12a8 8 0 1 0 2-5.3L3 12"/>',
        'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
        'folder' => '<path d="M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
        'close' => '<path d="m18 6-12 12M6 6l12 12"/>',
    ];
    return '<svg class="menu-action-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name] ?? $paths['folder']).'</svg>';
};
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
  <div><div class="page-eyebrow">PENGELOLAAN DRIVE</div><h1 class="h3 mb-1">Sampah</h1><p class="text-secondary mb-0">Pulihkan item atau hapus permanen jika sudah tidak diperlukan.</p></div>
  <div class="d-flex align-items-center gap-2"><div class="view-switcher" role="group" aria-label="Tampilan sampah"><button type="button" class="view-switch-btn" data-trash-view="list" aria-pressed="true" title="Tampilan daftar">☷</button><button type="button" class="view-switch-btn" data-trash-view="grid" aria-pressed="false" title="Tampilan grid">▦</button></div><a class="btn btn-outline-secondary rounded-pill px-3" href="/files">← Kembali ke Drive</a></div>
</div>
<div class="trash-notice mb-4"><span class="trash-notice-icon">♲</span><div><strong>Item di sampah</strong><div class="small text-secondary">File masih menggunakan kuota sampai dihapus permanen. Penghapusan permanen tidak dapat dibatalkan.</div></div></div>

<?php $filters = $filters ?? ['type'=>'','history'=>'','location'=>'','date_from'=>'','date_to'=>'']; ?>
<form method="get" action="<?= site_url('trash') ?>" class="trash-filter-bar mb-4" id="trashFilters">
  <div class="trash-filter-control">
    <label class="visually-hidden" for="trashTypeFilter">Jenis item</label>
    <select class="form-select" id="trashTypeFilter" name="type">
      <option value="">Jenis</option>
      <option value="folder" <?= $filters['type']==='folder'?'selected':'' ?>>Folder</option>
      <option value="document" <?= $filters['type']==='document'?'selected':'' ?>>Dokumen</option>
      <option value="spreadsheet" <?= $filters['type']==='spreadsheet'?'selected':'' ?>>Spreadsheet</option>
      <option value="presentation" <?= $filters['type']==='presentation'?'selected':'' ?>>Presentasi</option>
      <option value="image" <?= $filters['type']==='image'?'selected':'' ?>>Pics</option>
      <option value="video" <?= $filters['type']==='video'?'selected':'' ?>>Video</option>
      <option value="audio" <?= $filters['type']==='audio'?'selected':'' ?>>Audio</option>
      <option value="archive" <?= $filters['type']==='archive'?'selected':'' ?>>Arsip</option>
      <option value="other" <?= $filters['type']==='other'?'selected':'' ?>>Lainnya</option>
    </select>
  </div>
  <div class="trash-filter-control">
    <label class="visually-hidden" for="trashHistoryFilter">Riwayat tanggal</label>
    <select class="form-select" id="trashHistoryFilter" name="history">
      <option value="">Dihapus / diubah</option>
      <option value="today" <?= $filters['history']==='today'?'selected':'' ?>>Hari ini</option>
      <option value="7days" <?= $filters['history']==='7days'?'selected':'' ?>>7 hari terakhir</option>
      <option value="30days" <?= $filters['history']==='30days'?'selected':'' ?>>30 hari terakhir</option>
      <option value="this_year" <?= $filters['history']==='this_year'?'selected':'' ?>>Tahun ini (<?= date('Y') ?>)</option>
      <option value="last_year" <?= $filters['history']==='last_year'?'selected':'' ?>>Tahun lalu (<?= date('Y')-1 ?>)</option>
      <option value="range" <?= $filters['history']==='range'?'selected':'' ?>>Rentang tanggal...</option>
    </select>
  </div>
  <div class="trash-filter-control trash-location-filter">
    <label class="visually-hidden" for="trashLocationFilter">Lokasi folder awal</label>
    <select class="form-select" id="trashLocationFilter" name="location">
      <option value="">Semua lokasi</option>
      <option value="0" <?= $filters['location']==='0'?'selected':'' ?>>Drive Saya (folder utama)</option>
      <?php foreach (($folderOptions ?? []) as $folderOption): ?>
        <option value="<?= (int)$folderOption['id'] ?>" <?= $filters['location']===(string)$folderOption['id']?'selected':'' ?>><?= esc($folderOption['name']) ?></option>
      <?php endforeach ?>
    </select>
  </div>
  <div class="trash-date-range <?= $filters['history']==='range'?'is-visible':'' ?>" id="trashDateRange">
    <label class="visually-hidden" for="trashDateFrom">Dari tanggal</label>
    <input class="form-control" type="date" id="trashDateFrom" name="date_from" value="<?= esc($filters['date_from']) ?>" aria-label="Dari tanggal">
    <span class="text-secondary">—</span>
    <label class="visually-hidden" for="trashDateTo">Sampai tanggal</label>
    <input class="form-control" type="date" id="trashDateTo" name="date_to" value="<?= esc($filters['date_to']) ?>" aria-label="Sampai tanggal">
  </div>
  <button type="submit" class="btn btn-primary trash-filter-apply">Terapkan</button>
  <?php if ($filters['type']!=='' || $filters['history']!=='' || $filters['location']!=='' || $filters['date_from']!=='' || $filters['date_to']!==''): ?>
    <a class="btn btn-outline-secondary trash-filter-reset" href="<?= site_url('trash') ?>">Hapus filter</a>
  <?php endif ?>
</form>

<div id="trashViewRoot" data-trash-view="list">
<form id="bulkPurgeForm" method="post" action="/trash/bulk-purge" class="bulk-selection-bar trash-bulk-selection mb-3" aria-hidden="true"><?= csrf_field() ?><div id="trashSelectedIds"></div><div class="bulk-selection-info"><button type="button" class="selection-clear-icon" id="clearTrashSelection" aria-label="Batalkan pilihan" title="Batalkan pilihan">×</button><strong id="trashSelectionCount">0 item dipilih</strong></div><div class="bulk-action-controls"><button type="button" class="selection-action-btn" data-trash-bulk-action="restore" title="Pulihkan" aria-label="Pulihkan"><?= $menuIcon('restore') ?></button><button type="button" class="selection-action-btn danger" data-trash-bulk-action="purge" title="Hapus permanen" aria-label="Hapus permanen"><?= $menuIcon('trash') ?></button><div class="dropdown floating-action-dropdown"><button type="button" class="btn action-menu-trigger bulk-menu-trigger" id="trashBulkMenu" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi item terpilih" disabled><?= $menuIcon('more') ?></button><div class="dropdown-menu dropdown-menu-end file-action-menu bulk-action-menu"><button type="button" class="dropdown-item" data-trash-bulk-action="restore"><?= $menuIcon('restore') ?>Pulihkan</button><div class="dropdown-divider"></div><button type="button" class="dropdown-item text-danger" data-trash-bulk-action="purge"><?= $menuIcon('trash') ?>Hapus permanen</button></div></div></div></form>
<div class="card p-3 p-lg-4 mb-4 trash-files-card">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">File</h2><span class="small text-secondary"><?= count($files) ?> file</span></div><span class="trash-section-icon">▤</span></div>
  <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="trash-selection-column"><input type="checkbox" class="form-check-input" id="selectAllTrashFiles" aria-label="Pilih semua file"></th><th>Nama file</th><th>Ukuran</th><th>Tanggal dihapus</th><th>Lokasi folder awal</th><th class="text-end">Aksi</th></tr></thead><tbody>
  <?php foreach ($files as $f): ?>
  <tr class="file-row">
    <td class="trash-selection-column"><input type="checkbox" class="form-check-input trash-item-select" data-item-type="file" value="<?= (int)$f['id'] ?>" aria-label="Pilih <?= esc($f['original_name'], 'attr') ?>"></td><td><div class="d-flex align-items-center gap-2"><span class="trash-item-icon">▧</span><div class="min-width-0"><div class="fw-semibold text-break"><a class="text-reset text-decoration-none" href="/files/preview/<?= (int)$f['id'] ?>?trash=1"><?= esc($f['original_name']) ?></a></div><small class="text-secondary">File · klik dua kali untuk membuka</small></div></div></td>
    <td class="text-nowrap"><?= number_format($f['size_bytes']/1048576,2) ?> MB</td><td class="text-nowrap"><?= esc($f['trashed_at'] ?? $f['updated_at'] ?? '-') ?></td><td class="trash-original-location" title="<?= esc($f['original_location'] ?? 'Drive Saya', 'attr') ?>"><span class="text-secondary"><?= $menuIcon('folder') ?></span><span><?= esc($f['original_location'] ?? 'Drive Saya') ?></span></td>
    <td class="text-end"><div class="dropdown file-actions-dropdown floating-action-dropdown">
      <button class="btn btn-sm btn-outline-secondary action-menu-trigger" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Pilihan aksi file"><?= $menuIcon('more') ?></button>
      <div class="dropdown-menu dropdown-menu-end file-action-menu">
        <form method="post" action="/trash/file/restore/<?= (int)$f['id'] ?>"><?= csrf_field() ?><button class="dropdown-item"><?= $menuIcon('restore') ?>Pulihkan</button></form>
        <div class="dropdown-divider"></div>
        <form method="post" action="/trash/file/purge/<?= (int)$f['id'] ?>" onsubmit="return confirm('Hapus file secara permanen? Tindakan ini tidak dapat dibatalkan.')"><?= csrf_field() ?><button class="dropdown-item text-danger"><?= $menuIcon('trash') ?>Hapus permanen</button></form>
      </div>
    </div></td>
  </tr>
  <?php endforeach ?>
  <?php if (!$files): ?><tr><td colspan="6"><div class="empty-trash"><span>▤</span><strong>Belum ada file di sampah</strong><small>File yang Anda hapus akan muncul di sini.</small></div></td></tr><?php endif ?>
  </tbody></table></div>
</div>
<div class="card p-3 p-lg-4 trash-folders-card">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Folder</h2><span class="small text-secondary"><?= count($folders) ?> folder</span></div><span class="trash-section-icon">📁</span></div>
  <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="trash-selection-column"><input type="checkbox" class="form-check-input" id="selectAllTrashFolders" aria-label="Pilih semua folder"></th><th>Nama folder</th><th>Tanggal dihapus</th><th>Lokasi folder awal</th><th class="text-end">Aksi</th></tr></thead><tbody>
  <?php foreach ($folders as $f): ?>
  <tr class="file-row"><td class="trash-selection-column"><input type="checkbox" class="form-check-input trash-item-select" data-item-type="folder" value="<?= (int)$f['id'] ?>" aria-label="Pilih <?= esc($f['name'], 'attr') ?>"></td><td><div class="d-flex align-items-center gap-2"><span class="trash-item-icon">📁</span><div class="fw-semibold text-break"><?= esc($f['name']) ?></div></div></td><td class="text-nowrap"><?= esc($f['trashed_at'] ?? $f['updated_at'] ?? '-') ?></td><td class="trash-original-location" title="<?= esc($f['original_location'] ?? 'Drive Saya', 'attr') ?>"><span class="text-secondary"><?= $menuIcon('folder') ?></span><span><?= esc($f['original_location'] ?? 'Drive Saya') ?></span></td>
    <td class="text-end"><div class="dropdown folder-actions floating-action-dropdown">
      <button class="btn btn-sm btn-outline-secondary action-menu-trigger" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Pilihan aksi folder"><?= $menuIcon('more') ?></button>
      <div class="dropdown-menu dropdown-menu-end file-action-menu">
        <form method="post" action="/trash/folder/restore/<?= (int)$f['id'] ?>"><?= csrf_field() ?><button class="dropdown-item"><?= $menuIcon('restore') ?>Pulihkan</button></form>
        <div class="dropdown-divider"></div>
        <form method="post" action="/trash/folder/purge/<?= (int)$f['id'] ?>" onsubmit="return confirm('Hapus folder dan seluruh isinya secara permanen? Tindakan ini tidak dapat dibatalkan.')"><?= csrf_field() ?><button class="dropdown-item text-danger"><?= $menuIcon('trash') ?>Hapus permanen</button></form>
      </div>
    </div></td>
  </tr>
  <?php endforeach ?>
  <?php if (!$folders): ?><tr><td colspan="5"><div class="empty-trash"><span>📁</span><strong>Belum ada folder di sampah</strong><small>Folder yang Anda hapus akan muncul di sini.</small></div></td></tr><?php endif ?>
  </tbody></table></div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const bulkForm=document.getElementById('bulkPurgeForm'),bulkCount=document.getElementById('trashSelectionCount'),bulkIds=document.getElementById('trashSelectedIds'),bulkButton=document.getElementById('trashBulkMenu');
 function refreshTrashSelection(){
  const selected=[...document.querySelectorAll('.trash-item-select:checked')],files=selected.filter(x=>x.dataset.itemType==='file'),folders=selected.filter(x=>x.dataset.itemType==='folder');
  bulkCount.textContent=selected.length+' item dipilih ('+files.length+' file, '+folders.length+' folder)';
  bulkIds.innerHTML='';
  selected.forEach(box=>{const input=document.createElement('input');input.type='hidden';input.name=box.dataset.itemType==='folder'?'folder_ids[]':'file_ids[]';input.value=box.value;bulkIds.appendChild(input);});
  bulkForm.classList.toggle('is-selection-active',selected.length>0);bulkForm.setAttribute('aria-hidden',selected.length?'false':'true');bulkButton.disabled=!selected.length;
  [['selectAllTrashFiles','file'],['selectAllTrashFolders','folder']].forEach(([id,type])=>{const all=[...document.querySelectorAll('.trash-item-select[data-item-type="'+type+'"]')],el=document.getElementById(id);if(el){el.checked=all.length>0&&all.every(x=>x.checked);el.indeterminate=all.some(x=>x.checked)&&!el.checked;}});
 }

 const trashRoot=document.getElementById('trashViewRoot');
 const trashIsControl=target=>!!target.closest('button,input,select,textarea,form,.dropdown-menu,[data-bs-toggle],.file-action-menu,label');
 let trashSelectionAnchor=null;
 const trashSelectRow=(row,event)=>{const box=row?.querySelector('.trash-item-select');if(!box)return;const all=[...trashRoot.querySelectorAll('.trash-item-select')],index=all.indexOf(box);if(event?.shiftKey&&trashSelectionAnchor?.checked){const anchor=all.indexOf(trashSelectionAnchor);if(anchor>=0){if(!event.ctrlKey&&!event.metaKey)all.forEach(item=>item.checked=false);all.slice(Math.min(anchor,index),Math.max(anchor,index)+1).forEach(item=>item.checked=true);}else{box.checked=true;trashSelectionAnchor=box;}}else if(event?.ctrlKey||event?.metaKey){box.checked=!box.checked;trashSelectionAnchor=box;}else{all.forEach(item=>item.checked=item===box);trashSelectionAnchor=box;}all.forEach(item=>item.dispatchEvent(new Event('change',{bubbles:true})));};
 trashRoot?.addEventListener('click',event=>{const row=event.target.closest('tr.file-row');if(!row||trashIsControl(event.target))return;const link=event.target.closest('a');if(link)event.preventDefault();trashSelectRow(row,event);});
 trashRoot?.addEventListener('dblclick',event=>{const row=event.target.closest('tr.file-row');if(!row||trashIsControl(event.target))return;const link=row.querySelector('td:nth-child(2) a[href]');if(link){event.preventDefault();window.location.assign(link.href);}});
 trashRoot?.addEventListener('change',event=>{const box=event.target.closest('.trash-item-select');if(!box)return;box.closest('tr.file-row')?.classList.toggle('is-drive-selected',box.checked);});

 document.querySelectorAll('.trash-item-select').forEach(el=>el.addEventListener('change',refreshTrashSelection));
 document.getElementById('selectAllTrashFiles')?.addEventListener('change',function(){document.querySelectorAll('.trash-item-select[data-item-type="file"]').forEach(x=>x.checked=this.checked);refreshTrashSelection();});
 document.getElementById('selectAllTrashFolders')?.addEventListener('change',function(){document.querySelectorAll('.trash-item-select[data-item-type="folder"]').forEach(x=>x.checked=this.checked);refreshTrashSelection();});
 document.getElementById('clearTrashSelection')?.addEventListener('click',()=>{document.querySelectorAll('.trash-item-select').forEach(x=>x.checked=false);refreshTrashSelection();});
 document.addEventListener('keydown',function(event){
  const isEscape=event.key==='Escape'||event.code==='Escape';
  const isDelete=event.key==='Delete'||event.code==='Delete';
  if(!isEscape&&!isDelete)return;
  const target=event.target,tag=(target?.tagName||'').toLowerCase();
  if(target?.isContentEditable||['textarea','select'].includes(tag)||(tag==='input'&&!['checkbox','radio'].includes(target.type))||document.querySelector('.modal.show'))return;
  const selected=[...document.querySelectorAll('.trash-item-select:checked')];
  if(!selected.length)return;
  if(isEscape){
   event.preventDefault();event.stopPropagation();
   document.querySelectorAll('.trash-item-select').forEach(x=>{x.checked=false;x.closest('tr.file-row')?.classList.remove('is-drive-selected');});
   trashSelectionAnchor=null;refreshTrashSelection();return;
  }
  event.preventDefault();event.stopPropagation();
  if(!confirm('Hapus permanen '+selected.length+' item terpilih? Isi folder terpilih juga akan dihapus. Tindakan ini tidak dapat dibatalkan.'))return;
  bulkForm.action='/trash/bulk-purge';bulkForm.submit();
 },true);
 document.querySelectorAll('[data-trash-bulk-action]').forEach(button=>button.addEventListener('click',function(){
  const action=this.dataset.trashBulkAction,count=document.querySelectorAll('.trash-item-select:checked').length;
  if(!count)return;
  if(action==='restore'){
   if(!confirm('Pulihkan semua item yang dipilih? File di dalam folder hanya dapat dipulihkan jika folder induknya sudah dipulihkan.'))return;
   bulkForm.action='/trash/bulk-restore';bulkForm.submit();return;
  }
  if(action==='purge'){
   if(!confirm('Hapus permanen semua item yang dipilih? Isi folder terpilih juga akan dihapus. Tindakan ini tidak dapat dibatalkan.'))return;
   bulkForm.action='/trash/bulk-purge';bulkForm.submit();
  }
 }));
 refreshTrashSelection();
 const historySelect=document.getElementById('trashHistoryFilter'),dateRange=document.getElementById('trashDateRange');
 function toggleTrashDateRange(){dateRange?.classList.toggle('is-visible',historySelect?.value==='range');}
 historySelect?.addEventListener('change',toggleTrashDateRange);toggleTrashDateRange();
 const root=document.getElementById('trashViewRoot'),buttons=document.querySelectorAll('[data-trash-view]');const saved=localStorage.getItem('pcd-trash-view')||'list';function setView(view){if(!root)return;root.dataset.trashView=view;buttons.forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.trashView===view)));localStorage.setItem('pcd-trash-view',view);}buttons.forEach(b=>b.addEventListener('click',()=>setView(b.dataset.trashView)));setView(saved);
 document.querySelectorAll('.floating-action-dropdown').forEach(function(dropdown){
  const trigger=dropdown.querySelector('[data-bs-toggle="dropdown"]'),menu=dropdown.querySelector('.file-action-menu');if(!trigger||!menu)return;let placeholder=null;
  dropdown.addEventListener('shown.bs.dropdown',function(){placeholder=document.createComment('trash-action-menu');menu.parentNode.insertBefore(placeholder,menu);document.body.appendChild(menu);const rect=trigger.getBoundingClientRect(),w=Math.min(Math.max(menu.offsetWidth,210),window.innerWidth-24),h=Math.min(menu.scrollHeight,window.innerHeight-24);let left=Math.max(12,Math.min(rect.right-w,window.innerWidth-w-12)),top=rect.bottom+8;if(top+h>window.innerHeight-12)top=Math.max(12,rect.top-h-8);menu.style.position='fixed';menu.style.left=left+'px';menu.style.top=top+'px';menu.style.right='auto';menu.style.bottom='auto';menu.style.transform='none';menu.style.zIndex='2000';});
  dropdown.addEventListener('hidden.bs.dropdown',function(){if(placeholder&&placeholder.parentNode){placeholder.parentNode.insertBefore(menu,placeholder);placeholder.remove();}menu.removeAttribute('style');placeholder=null;});
 });
});
</script>
</div><!-- /trashViewRoot -->
<?= $this->endSection() ?>