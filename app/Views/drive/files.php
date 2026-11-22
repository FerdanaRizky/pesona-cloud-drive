<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $isStarredPage = !empty($isStarredPage); ?>
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
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 file-toolbar">
  <div><h1 class="h3 mb-1"><?= $isStarredPage ? 'Favorit' : 'File Saya' ?></h1><p class="text-secondary mb-2"><?= $isStarredPage ? 'Semua file yang Anda tandai sebagai favorit, terkumpul di satu tempat.' : 'Kelola file dan folder pribadi Anda.' ?></p>
  <?php if (!$isStarredPage): ?><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="/files">Drive Saya</a></li><?php foreach ($breadcrumbs as $crumb): ?><li class="breadcrumb-item active text-secondary"><?= esc($crumb['name']) ?></li><?php endforeach ?></ol></nav><?php endif ?></div>
  <div class="d-flex flex-wrap gap-2 align-items-center"><?php if (!$isStarredPage): ?><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createFolderModal">＋ Tambah Folder</button><button type="button" class="btn btn-primary" id="openUploadPicker">↑ Upload</button><?php endif ?></div>
</div>
<div class="modal fade" id="createFolderModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="/files/folders/create" method="post"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><div class="modal-header"><h5 class="modal-title">Tambah Folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label" for="newFolderName">Nama folder</label><input class="form-control" id="newFolderName" name="name" placeholder="Masukkan nama folder" maxlength="120" required></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Buat Folder</button></div></form></div></div></div>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="/files/upload" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><div class="modal-header"><h5 class="modal-title">Upload File</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label" for="modalUploadInput">Pilih file dari perangkat</label><input class="form-control" type="file" id="modalUploadInput" name="files[]" multiple required><div class="form-text" id="uploadSelectionInfo">iPhone: pilih foto, lalu tekan Tambah di kanan atas. Setelah kembali ke aplikasi, upload dimulai otomatis.</div><div class="upload-progress-wrap d-none mt-3" id="uploadProgressWrap"><div class="d-flex justify-content-between align-items-center mb-2"><span class="small fw-semibold" id="uploadProgressLabel">Mengunggah file...</span><strong class="small tabular-nums" id="uploadProgressPercent">0%</strong></div><div class="progress upload-progress-track" role="progressbar" aria-label="Progres upload" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="uploadProgressBar"><div class="progress-bar" id="uploadProgressFill" style="width:0%"></div></div><div class="d-flex justify-content-between gap-2 mt-2"><small class="text-secondary" id="uploadProgressDetail">Menyiapkan file...</small><small class="text-secondary" id="uploadProgressSize"></small></div><div class="upload-file-progress-list mt-3" id="uploadFileProgressList"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" id="uploadCancelButton" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="startUploadButton" type="submit">Mulai Upload</button></div></form></div></div></div>
<div class="modal fade" id="fileActionModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="fileActionForm" method="post"><?= csrf_field() ?><div class="modal-header"><h5 class="modal-title" id="fileActionTitle">Aksi File</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div id="renameField"><label class="form-label" for="actionFileName">Nama file baru</label><input class="form-control" id="actionFileName" name="name" maxlength="255"></div><div id="moveField" class="d-none"><label class="form-label" for="actionFolder">Pilih folder tujuan</label><select class="form-select" id="actionFolder" name="parent_id"></select><div class="form-text">Pilih Drive utama jika ingin memindahkan file ke luar folder.</div></div><div id="shareField" class="d-none"><label class="form-label" for="actionExpiry">Masa berlaku tautan</label><select class="form-select" id="actionExpiry" name="expires_days"><option value="1">1 hari</option><option value="7" selected>7 hari</option><option value="30">30 hari</option><option value="never">Tanpa kedaluwarsa</option></select><div class="form-text">Siapa pun yang memiliki tautan dapat melihat file.</div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="fileActionSubmit" type="submit">Simpan</button></div></form></div></div></div>
<form method="get" action="<?= $isStarredPage ? '/starred' : '/files' ?>" class="d-flex gap-2 mb-4" role="search"><?php if (!$isStarredPage): ?><input type="hidden" name="folder" value="<?= (int) ($currentFolder['id'] ?? 0) ?>"><?php endif ?><input class="form-control" name="q" value="<?= esc($term ?? '') ?>" placeholder="Cari nama file atau folder..."><button class="btn btn-outline-secondary">Cari</button><?php if (!empty($term)): ?><a class="btn btn-outline-secondary" href="<?= $isStarredPage ? '/starred' : (!empty($currentFolder) ? '/files?folder=' . (int) $currentFolder['id'] : '/files') ?>">Reset</a><?php endif ?></form>
<script>document.addEventListener('DOMContentLoaded',function(){const viewRoot=document.getElementById('driveViewRoot');const viewButtons=document.querySelectorAll('[data-drive-view]');const savedView=localStorage.getItem('pcd-drive-view')||'list';function setDriveView(view){if(!viewRoot)return;viewRoot.dataset.driveView=view;viewButtons.forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.driveView===view)));localStorage.setItem('pcd-drive-view',view);}viewButtons.forEach(b=>b.addEventListener('click',()=>setDriveView(b.dataset.driveView)));setDriveView(savedView);
const selectionForm=document.getElementById('bulkActionForm'),selectionCount=document.getElementById('bulkSelectionCount'),selectAll=document.getElementById('selectAllFiles'),selectAllFolders=document.getElementById('selectAllFolders'),hiddenIds=document.getElementById('bulkSelectedIds'),menuTrigger=document.getElementById('bulkActionsMenu'),hint=document.getElementById('bulkSelectionHint');
function selectedUnique(){
 const map=new Map();
 document.querySelectorAll('.drive-item-select:checked').forEach(box=>map.set(box.dataset.itemType+':'+box.value,box));
 return [...map.values()];
}
function refreshSelection(){
 const selected=selectedUnique(),fileCount=selected.filter(x=>x.dataset.itemType==='file').length,folderCount=selected.length-fileCount;
 selectionCount.textContent=selected.length+' item dipilih ('+fileCount+' file, '+folderCount+' folder)';
 hiddenIds.innerHTML='';
 selected.forEach(box=>{const hidden=document.createElement('input');hidden.type='hidden';hidden.name=box.dataset.itemType==='folder'?'folder_ids[]':'file_ids[]';hidden.value=box.value;hiddenIds.appendChild(hidden);});
 if(menuTrigger)menuTrigger.disabled=!selected.length;
 const selectionBar=document.querySelector(".bulk-selection-bar");
 if(selectionBar){selectionBar.classList.toggle("is-selection-active",selected.length>0);selectionBar.setAttribute("aria-hidden",selected.length===0?"true":"false");}
 document.querySelectorAll('.file-manager-list-row,.file-manager-file-card,.file-manager-folder-card').forEach(item=>{
   const box=item.querySelector('.drive-item-select');if(!box)return;
   item.classList.toggle('is-dashboard-selected',box.checked);
 });
 if(selectAll){const all=[...document.querySelectorAll('.drive-item-select[data-item-type="file"]')].filter(x=>x.getClientRects().length>0);selectAll.checked=all.length>0&&all.every(x=>x.checked);selectAll.indeterminate=all.some(x=>x.checked)&&!selectAll.checked;}
 if(selectAllFolders){const all=[...document.querySelectorAll('.drive-item-select[data-item-type="folder"]')].filter(x=>x.getClientRects().length>0);selectAllFolders.checked=all.length>0&&all.every(x=>x.checked);selectAllFolders.indeterminate=all.some(x=>x.checked)&&!selectAllFolders.checked;}
}
document.querySelectorAll('.drive-item-select').forEach(box=>box.addEventListener('change',function(){
 const key=this.dataset.itemType+':'+this.value;
 document.querySelectorAll('.drive-item-select').forEach(other=>{if(other.dataset.itemType+':'+other.value===key)other.checked=this.checked;});
 refreshSelection();
}));
const driveRoot=document.getElementById('driveViewRoot');
let selectionAnchor=null;
const getSelectableItems=()=>[...driveRoot.querySelectorAll('.file-manager-list-row,.file-manager-file-card,.file-manager-folder-card')].filter(item=>item.getClientRects().length>0);
const isItemControl=target=>!!target.closest('button,form,.dropdown,.dropdown-menu,[data-bs-toggle],input,select,textarea');
function setItemChecked(item,checked){
 const box=item.querySelector('.drive-item-select');if(!box)return;
 const key=box.dataset.itemType+':'+box.value;
 document.querySelectorAll('.drive-item-select').forEach(other=>{if(other.dataset.itemType+':'+other.value===key)other.checked=checked;});
}
driveRoot.addEventListener('click',event=>{
 const item=event.target.closest('.file-manager-list-row,.file-manager-file-card,.file-manager-folder-card');
 if(!item||isItemControl(event.target))return;
 const mainLink=event.target.closest('.dashboard-folder-main,.dashboard-file-title,.dashboard-file-preview,.dashboard-list-name');
 if(mainLink)event.preventDefault();
 const items=getSelectableItems();
 if(event.shiftKey&&selectionAnchor&&items.includes(selectionAnchor)){
   if(!event.ctrlKey&&!event.metaKey)document.querySelectorAll('.drive-item-select').forEach(box=>box.checked=false);
   const a=items.indexOf(selectionAnchor),b=items.indexOf(item);
   items.slice(Math.min(a,b),Math.max(a,b)+1).forEach(entry=>setItemChecked(entry,true));
 }else{
   const box=item.querySelector('.drive-item-select');if(!box)return;
   const next=(event.ctrlKey||event.metaKey)?!box.checked:true;
   if(!event.ctrlKey&&!event.metaKey)document.querySelectorAll('.drive-item-select').forEach(other=>other.checked=false);
   setItemChecked(item,next);selectionAnchor=item;
 }
 refreshSelection();
});
driveRoot.addEventListener('dblclick',event=>{
 const item=event.target.closest('.file-manager-list-row,.file-manager-file-card,.file-manager-folder-card');
 if(!item||isItemControl(event.target))return;
 const link=item.querySelector('.dashboard-folder-main[href],.dashboard-file-title[href],.dashboard-file-preview[href],.dashboard-list-name[href]');
 if(link){event.preventDefault();window.location.assign(link.href);}
});
selectAll?.addEventListener('change',()=>{document.querySelectorAll('.drive-item-select').forEach(box=>box.checked=selectAll.checked);refreshSelection();});
selectAllFolders?.addEventListener('change',()=>{document.querySelectorAll('.drive-item-select[data-item-type="folder"]').forEach(box=>box.checked=selectAllFolders.checked);refreshSelection();});
document.getElementById('clearDriveSelection')?.addEventListener('click',()=>{document.querySelectorAll('.drive-item-select').forEach(box=>box.checked=false);refreshSelection();});
const bulkModal=document.getElementById('bulkActionModal');
const bulkActionUrl={
 share:<?= json_encode(site_url('files/bulk-share')) ?>,
 move:<?= json_encode(site_url('files/bulk-move')) ?>,
 download:<?= json_encode(site_url('files/bulk-download')) ?>,
 trash:<?= json_encode(site_url('files/bulk-trash')) ?>
};
function checkedBulkItems(){return selectedUnique();}
function submitBulkAction(action){
 if(!selectionForm)return;
 selectionForm.action=bulkActionUrl[action]||bulkActionUrl.trash;
 selectionForm.dataset.pendingAction='';
 if(typeof selectionForm.requestSubmit==='function')selectionForm.requestSubmit();
 else HTMLFormElement.prototype.submit.call(selectionForm);
}
// Delegated handler: keeps bulk actions working even if the toolbar is re-rendered or its menu is moved.
document.addEventListener('click',function(event){
 const button=event.target.closest('[data-bulk-action]');
 if(!button)return;
 event.preventDefault();
 event.stopPropagation();
 const action=button.dataset.bulkAction;
 const selected=checkedBulkItems();
 if(!selected.length){window.alert('Pilih file atau folder terlebih dahulu.');return;}
 const files=selected.filter(box=>box.dataset.itemType==='file');
 if((action==='share'||action==='move')&&!files.length){window.alert('Pilih minimal satu file untuk '+(action==='share'?'dibagikan.':'dipindahkan.'));return;}
 if(action==='share'||action==='move'){
  const shareField=document.getElementById('bulkShareField');
  const moveField=document.getElementById('bulkMoveField');
  const title=document.getElementById('bulkActionModalTitle');
  const confirmButton=document.getElementById('bulkActionConfirm');
  if(!bulkModal||!shareField||!moveField||!title||!confirmButton){window.alert('Dialog aksi belum tersedia. Muat ulang halaman lalu coba lagi.');return;}
  shareField.classList.toggle('d-none',action!=='share');
  moveField.classList.toggle('d-none',action!=='move');
  title.textContent=action==='share'?'Bagikan file terpilih':'Pindahkan file terpilih';
  confirmButton.textContent=action==='share'?'Buat tautan':'Pindahkan file';
  selectionForm.dataset.pendingAction=action;
  if(!window.bootstrap?.Modal){window.alert('Komponen dialog belum termuat. Muat ulang halaman lalu coba lagi.');return;}
  window.bootstrap.Modal.getOrCreateInstance(bulkModal).show();
  return;
 }
 if(action==='download'){
  if(!files.length){window.alert('Pilihan Anda tidak berisi file yang dapat diunduh.');return;}
  if(!window.confirm('Unduh '+files.length+' file terpilih?'))return;
  submitBulkAction('download');
  return;
 }
 if(action==='trash'){
  if(!window.confirm('Pindahkan '+selected.length+' item terpilih ke Sampah?'))return;
  submitBulkAction('trash');
 }
},true);
document.getElementById('bulkActionConfirm')?.addEventListener('click',function(){
 const action=selectionForm?.dataset.pendingAction;
 if(!action)return;
 const files=checkedBulkItems().filter(box=>box.dataset.itemType==='file');
 if(!files.length){alert('Pilih minimal satu file.');return;}
 if(action==='move'){
  const target=document.getElementById('bulkMoveFolder');
  let hidden=selectionForm.querySelector('input[name="parent_id"]');
  if(!hidden){hidden=document.createElement('input');hidden.type='hidden';hidden.name='parent_id';selectionForm.appendChild(hidden);}
  hidden.value=target.value;
 }else if(action==='share'){
  let expiry=selectionForm.querySelector('input[name="expires_days"]');
  if(!expiry){expiry=document.createElement('input');expiry.type='hidden';expiry.name='expires_days';selectionForm.appendChild(expiry);}
  expiry.value=document.getElementById('bulkShareExpiry').value;
 }
 submitBulkAction(action);
 bootstrap.Modal.getOrCreateInstance(bulkModal).hide();
});
bulkModal?.addEventListener('hidden.bs.modal',()=>{if(selectionForm)selectionForm.dataset.pendingAction='';});
refreshSelection();
const modalEl=document.getElementById('uploadModal');document.getElementById('openUploadPicker')?.addEventListener('click',function(){bootstrap.Modal.getOrCreateInstance(modalEl).show();});
const uploadForm=document.querySelector('#uploadModal form'),uploadInput=document.getElementById('modalUploadInput'),uploadWrap=document.getElementById('uploadProgressWrap'),uploadFill=document.getElementById('uploadProgressFill'),uploadPercent=document.getElementById('uploadProgressPercent'),uploadLabel=document.getElementById('uploadProgressLabel'),uploadDetail=document.getElementById('uploadProgressDetail'),uploadSize=document.getElementById('uploadProgressSize'),uploadBar=document.getElementById('uploadProgressBar'),uploadStart=document.getElementById('startUploadButton'),uploadCancel=document.getElementById('uploadCancelButton');
const uploadFileList=document.getElementById('uploadFileProgressList'),uploadPickerLabel=document.querySelector('label[for="modalUploadInput"]');let uploadRunning=false,uploadAbort=null;const isMobileUploadDevice=(()=>{const ua=navigator.userAgent||'';return /iPhone|iPad|iPod|Android/i.test(ua)||((navigator.platform==='MacIntel')&&navigator.maxTouchPoints>1);})();
function formatUploadBytes(bytes){if(bytes<1024)return bytes+' B';const units=['KB','MB','GB','TB'];let value=bytes/1024,index=0;while(value>=1024&&index<units.length-1){value/=1024;index++;}return value.toFixed(value>=10?1:2)+' '+units[index];}
function refreshCsrfFromResponse(html){try{const doc=new DOMParser().parseFromString(html,'text/html');const fresh=doc.querySelector('input[type="hidden"][name*="csrf"]');if(!fresh||!fresh.value)return false;document.querySelectorAll('input[type="hidden"][name="'+CSS.escape(fresh.name)+'"]').forEach(input=>input.value=fresh.value);return true;}catch(e){return false;}}
function setFileState(row,state,detail){row.dataset.state=state;const status=row.querySelector('.upload-file-state');status.textContent=state==='done'?'Selesai':state==='failed'?'Gagal':state==='cancelled'?'Dibatalkan':state==='uploading'?'Mengunggah':'Menunggu';if(detail)row.querySelector('.upload-file-detail').textContent=detail;row.querySelector('.upload-file-fill').classList.toggle('bg-danger',state==='failed');}
function uploadOneFile(file,index,total,done){const row=uploadFileList.children[index],fd=new FormData();const csrf=uploadForm.querySelector('input[type="hidden"][name*="csrf"]');if(csrf)fd.append(csrf.name,csrf.value);fd.append('parent_id',uploadForm.querySelector('[name="parent_id"]').value);fd.append('files[]',file,file.name);setFileState(row,'uploading');const xhr=new XMLHttpRequest();uploadAbort=xhr;xhr.open('POST',uploadForm.action,true);xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');if(csrf?.value)xhr.setRequestHeader('X-CSRF-TOKEN',csrf.value);xhr.upload.addEventListener('progress',e=>{if(!e.lengthComputable)return;const p=Math.min(100,Math.round(e.loaded/e.total*100));const fill=row.querySelector('.upload-file-fill');fill.style.width=p+'%';row.querySelector('.upload-file-percent').textContent=p+'%';row.querySelector('.upload-file-detail').textContent=formatUploadBytes(e.loaded)+' / '+formatUploadBytes(file.size);uploadLabel.textContent='Mengunggah file '+(index+1)+' dari '+total;});xhr.addEventListener('load',()=>{let payload=null;try{payload=JSON.parse(xhr.responseText);}catch(e){}if(payload?.csrf?.name&&payload?.csrf?.hash){document.querySelectorAll('input[type="hidden"][name="'+CSS.escape(payload.csrf.name)+'"]').forEach(input=>input.value=payload.csrf.hash);}if(xhr.status>=200&&xhr.status<300&&payload?.success===true){setFileState(row,'done','Tersimpan · '+formatUploadBytes(file.size));row.querySelector('.upload-file-fill').style.width='100%';row.querySelector('.upload-file-percent').textContent='100%';done(true);return;}const message=payload?.message||payload?.errors?.join(' ')||('HTTP '+xhr.status+(xhr.status===403?' · Token keamanan ditolak':' · Server menolak file'));setFileState(row,'failed',message);done(false,xhr.status===403);});xhr.addEventListener('error',()=>{setFileState(row,'failed','Koneksi terputus');done(false);});xhr.addEventListener('abort',()=>{setFileState(row,'cancelled','Upload dibatalkan');done(false,true);});xhr.send(fd);}
uploadInput?.addEventListener('change',function(){const files=[...this.files];document.getElementById('uploadSelectionInfo').textContent=files.length?files.length+' file dipilih. Upload akan dimulai otomatis satu per satu.':'Anda dapat memilih lebih dari satu file.';if(files.length&&isMobileUploadDevice&&!uploadRunning){uploadLabel.textContent='File diterima. Memulai upload...';if(typeof uploadForm.requestSubmit==='function')uploadForm.requestSubmit();}});
uploadForm?.addEventListener('submit',function(event){event.preventDefault();if(uploadRunning||!uploadInput.files.length)return;const files=[...uploadInput.files],totalBytes=files.reduce((n,f)=>n+f.size,0);uploadRunning=true;if(uploadPickerLabel)uploadPickerLabel.classList.add('d-none');uploadInput.classList.add('d-none');document.getElementById('uploadSelectionInfo').classList.add('d-none');uploadWrap.classList.remove('d-none');uploadFileList.innerHTML='';uploadFill.classList.remove('bg-danger');uploadFill.style.width='0%';uploadPercent.textContent='0%';uploadBar.setAttribute('aria-valuenow','0');uploadLabel.textContent='Menyiapkan '+files.length+' file...';uploadDetail.textContent='0 dari '+files.length+' file selesai';uploadSize.textContent=formatUploadBytes(totalBytes);uploadStart.disabled=true;uploadStart.textContent='Mengunggah...';uploadCancel.disabled=false;uploadInput.disabled=true;
files.forEach((file,i)=>{const row=document.createElement('div');row.className='upload-file-row';row.innerHTML='<div class="d-flex justify-content-between gap-2 align-items-center"><span class="upload-file-name"></span><strong class="upload-file-state">Menunggu</strong></div><div class="progress upload-file-track mt-2"><div class="progress-bar upload-file-fill" style="width:0%"></div></div><div class="d-flex justify-content-between gap-2 mt-1"><small class="text-secondary upload-file-detail">'+formatUploadBytes(file.size)+'</small><small class="text-secondary upload-file-percent">0%</small></div>';row.querySelector('.upload-file-name').textContent=file.name;uploadFileList.appendChild(row);});
let index=0,success=0,failed=0,finishedBytes=0;function next(){if(index>=files.length){uploadRunning=false;uploadAbort=null;uploadInput.disabled=false;uploadStart.disabled=false;uploadStart.textContent='Upload selesai';uploadCancel.disabled=false;uploadLabel.textContent=failed?'Selesai dengan '+failed+' kegagalan':'Semua file berhasil diunggah';uploadDetail.textContent=success+' berhasil · '+failed+' gagal';uploadFill.style.width='100%';uploadPercent.textContent='100%';uploadBar.setAttribute('aria-valuenow','100');if(success>0)setTimeout(()=>{window.location.href=uploadForm.action.replace('/upload','')+(uploadForm.querySelector('[name="parent_id"]').value!=='0'?'?folder='+uploadForm.querySelector('[name="parent_id"]').value:'');},1300);else{uploadStart.textContent='Coba lagi';}return;}uploadOneFile(files[index],index,files.length,(ok,stop)=>{if(ok){success++;finishedBytes+=files[index].size;}else failed++;const percent=Math.round(((index+1)/files.length)*100);uploadFill.style.width=percent+'%';uploadPercent.textContent=percent+'%';uploadBar.setAttribute('aria-valuenow',String(percent));uploadDetail.textContent=(index+1)+' dari '+files.length+' file diproses · '+success+' berhasil · '+failed+' gagal';index++;if(stop){for(let k=index;k<files.length;k++){setFileState(uploadFileList.children[k],'cancelled','Dihentikan karena token keamanan tidak valid');failed++;}index=files.length;}next();});}next();});
uploadCancel?.addEventListener('click',function(){if(uploadAbort&&uploadRunning)uploadAbort.abort();});
modalEl?.addEventListener('hidden.bs.modal',function(){if(uploadRunning&&uploadAbort)uploadAbort.abort();if(!uploadRunning){uploadForm.reset();uploadInput.disabled=false;uploadInput.classList.remove('d-none');if(uploadPickerLabel)uploadPickerLabel.classList.remove('d-none');document.getElementById('uploadSelectionInfo').classList.remove('d-none');uploadStart.disabled=false;uploadStart.textContent='Mulai Upload';uploadWrap.classList.add('d-none');uploadFileList.innerHTML='';}});
document.querySelectorAll('.floating-action-dropdown').forEach(function(dropdown){const trigger=dropdown.querySelector('[data-bs-toggle="dropdown"]'),menu=dropdown.querySelector('.file-action-menu');if(!trigger||!menu)return;let placeholder=null;dropdown.addEventListener('shown.bs.dropdown',function(){placeholder=document.createComment('action-menu-position');menu.parentNode.insertBefore(placeholder,menu);document.body.appendChild(menu);const rect=trigger.getBoundingClientRect(),menuWidth=Math.min(Math.max(menu.offsetWidth,210),window.innerWidth-24),menuHeight=Math.min(menu.scrollHeight,window.innerHeight-24);let left=Math.max(12,Math.min(rect.right-menuWidth,window.innerWidth-menuWidth-12));let top=rect.bottom+8;if(top+menuHeight>window.innerHeight-12)top=Math.max(12,rect.top-menuHeight-8);menu.style.position='fixed';menu.style.left=left+'px';menu.style.top=top+'px';menu.style.right='auto';menu.style.bottom='auto';menu.style.transform='none';menu.style.zIndex='2000';});dropdown.addEventListener('hidden.bs.dropdown',function(){if(placeholder&&placeholder.parentNode){placeholder.parentNode.insertBefore(menu,placeholder);placeholder.remove();}menu.removeAttribute('style');placeholder=null;});});document.querySelectorAll('[data-file-action]').forEach(function(button){button.addEventListener('click',function(){const kind=this.dataset.fileAction,form=document.getElementById('fileActionForm'),title=document.getElementById('fileActionTitle'),submit=document.getElementById('fileActionSubmit'),rename=document.getElementById('renameField'),move=document.getElementById('moveField'),share=document.getElementById('shareField');form.action=this.dataset.url;rename.classList.toggle('d-none',kind!=='rename');move.classList.toggle('d-none',kind!=='move');share.classList.toggle('d-none',kind!=='share');if(kind==='rename'){title.textContent='Ganti Nama File';submit.textContent='Simpan Nama';document.getElementById('actionFileName').value=this.dataset.name||'';document.getElementById('actionFileName').maxLength=Number(this.dataset.maxlength||255);}if(kind==='move'){title.textContent='Pindah Folder';submit.textContent='Pindahkan File';const select=document.getElementById('actionFolder');select.innerHTML='<option value="0">Drive utama</option>';(JSON.parse(this.dataset.folders||'[]')).forEach(function(folder){const option=document.createElement('option');option.value=folder.id;option.textContent=folder.name;select.appendChild(option);});}if(kind==='share'){title.textContent='Bagi Tautan';submit.textContent='Buat Tautan';}bootstrap.Modal.getOrCreateInstance(document.getElementById('fileActionModal')).show();});});});</script>

<div id="driveViewRoot" data-drive-view="list">
<form id="bulkActionForm" method="post" action="<?= esc(site_url('files/bulk-trash'), 'attr') ?>" class="bulk-selection-bar mb-3" aria-hidden="true">
<?= csrf_field() ?><div id="bulkSelectedIds"></div>
<div class="bulk-selection-info"><button type="button" class="selection-clear-icon" id="clearDriveSelection" aria-label="Batalkan pilihan" title="Batalkan pilihan">×</button><strong id="bulkSelectionCount">0 item dipilih</strong></div>
<div class="bulk-action-controls">
<button type="button" class="selection-action-btn" data-bulk-action="share" title="Bagikan" aria-label="Bagikan"><?= $menuIcon('share') ?></button>
<button type="button" class="selection-action-btn" data-bulk-action="download" title="Download" aria-label="Download"><?= $menuIcon('download') ?></button>
<button type="button" class="selection-action-btn" data-bulk-action="move" title="Pindahkan" aria-label="Pindahkan"><?= $menuIcon('move') ?></button>
<button type="button" class="selection-action-btn danger" data-bulk-action="trash" title="Pindahkan ke Sampah" aria-label="Pindahkan ke Sampah"><?= $menuIcon('trash') ?></button>
<div class="dropdown floating-action-dropdown"><button type="button" class="btn action-menu-trigger bulk-menu-trigger" id="bulkActionsMenu" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi item terpilih" disabled><?= $menuIcon('more') ?></button>
<div class="dropdown-menu dropdown-menu-end file-action-menu bulk-action-menu">
<button type="button" class="dropdown-item" data-bulk-action="share"><?= $menuIcon('share') ?>Bagikan</button><button type="button" class="dropdown-item" data-bulk-action="download"><?= $menuIcon('download') ?>Download</button><button type="button" class="dropdown-item" data-bulk-action="move"><?= $menuIcon('move') ?>Pindah ke folder</button><div class="dropdown-divider"></div><button type="button" class="dropdown-item text-danger" data-bulk-action="trash"><?= $menuIcon('trash') ?>Pindahkan ke Sampah</button>
</div></div></div></form>

<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkActionModalTitle">Aksi item terpilih</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="bulkShareField" class="d-none">
          <label class="form-label" for="bulkShareExpiry">Masa berlaku tautan</label>
          <select class="form-select" id="bulkShareExpiry">
            <option value="1">1 hari</option><option value="7" selected>7 hari</option><option value="30">30 hari</option><option value="never">Tanpa kedaluwarsa</option>
          </select>
          <div class="form-text">Tautan hanya dibuat untuk file yang dipilih.</div>
        </div>
        <div id="bulkMoveField" class="d-none">
          <label class="form-label" for="bulkMoveFolder">Pilih folder tujuan</label>
          <select class="form-select" id="bulkMoveFolder">
            <option value="0">Drive utama</option>
            <?php foreach (($allFolders ?? []) as $folderOption): ?>
              <option value="<?= (int) $folderOption['id'] ?>"><?= esc($folderOption['name']) ?></option>
            <?php endforeach ?>
          </select>
          <div class="form-text">File yang dipilih akan dipindahkan ke folder tujuan. Folder yang dipilih tidak ikut dipindahkan.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="bulkActionConfirm">Lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<?php if (!$isStarredPage): ?>
<section class="dashboard-section dashboard-folder-section file-manager-folder-section">
<div class="dashboard-section-heading"><h2><span aria-hidden="true">⌄</span> Folder</h2><span class="small text-secondary"><?= count($folders) ?> folder</span></div>
<?php if (!empty($folders)): ?><div class="dashboard-folder-grid">
<?php foreach ($folders as $folder): ?>
<article class="dashboard-folder-card dashboard-selectable-item file-manager-folder-card">
<input type="checkbox" class="form-check-input drive-item-select file-manager-card-select" data-item-type="folder" value="<?= (int) $folder['id'] ?>" aria-label="Pilih folder <?= esc($folder['name'], 'attr') ?>">
<a class="dashboard-folder-main" href="/files?folder=<?= (int) $folder['id'] ?>"><span class="dashboard-folder-icon" aria-hidden="true">📁</span><span class="dashboard-card-name" title="<?= esc($folder['name'], 'attr') ?>"><?= esc($folder['name']) ?></span></a>
<div class="dropdown floating-action-dropdown"><button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi folder <?= esc($folder['name'], 'attr') ?>"><?= $menuIcon('more') ?></button>
<div class="dropdown-menu dropdown-menu-end file-action-menu"><a class="dropdown-item" href="/files?folder=<?= (int) $folder['id'] ?>">Buka folder</a><button type="button" class="dropdown-item" data-file-action="rename" data-url="/files/folders/rename/<?= (int) $folder['id'] ?>" data-name="<?= esc($folder['name'], 'attr') ?>" data-maxlength="120"><?= $menuIcon('rename') ?>Ganti nama</button><form method="post" action="/files/folders/delete/<?= (int) $folder['id'] ?>" onsubmit="return confirm('Hapus folder kosong ini?')"><?= csrf_field() ?><button class="dropdown-item text-danger"><?= $menuIcon('trash') ?>Hapus</button></form></div></div>
</article>
<?php endforeach ?></div><?php else: ?><div class="dashboard-empty">Belum ada folder di lokasi ini.</div><?php endif ?>
</section>
<?php endif ?>

<section class="dashboard-section dashboard-files-section file-manager-files-section">
<div class="dashboard-section-heading dashboard-files-heading"><h2><span aria-hidden="true">⌄</span> <?= $isStarredPage ? 'File favorit' : 'File' ?><?= !empty($term) ? ' — hasil pencarian' : '' ?></h2><div class="dashboard-heading-actions"><span class="small text-secondary"><?= count($files) ?> file</span><div class="view-switcher dashboard-view-switcher" role="group" aria-label="Tampilan file"><button type="button" class="view-switch-btn" data-drive-view="list" aria-pressed="true" title="Tampilan daftar">☰</button><button type="button" class="view-switch-btn" data-drive-view="grid" aria-pressed="false" title="Tampilan grid">▦</button></div></div></div>

<?php if (!empty($files)): ?>
<div class="dashboard-file-list-wrap file-manager-list-wrap"><table class="dashboard-file-list file-manager-list"><thead><tr><th class="file-manager-select-heading"><input type="checkbox" class="form-check-input" id="selectAllFiles" aria-label="Pilih semua file"></th><th>Nama</th><th>Jenis file</th><th>History perubahan</th><th>Lokasi folder</th><th>Ukuran file</th><th class="dashboard-list-action-heading">Aksi</th></tr></thead><tbody>
<?php foreach ($files as $f):
$fileExt=strtolower((string)($f['extension']??''));$fileMime=strtolower((string)($f['mime_type']??''));$isImage=str_starts_with($fileMime,'image/')&&in_array($fileExt,['jpg','jpeg','png','gif','webp'],true);$isPdf=$fileMime==='application/pdf'||$fileExt==='pdf';$isOffice=in_array($fileExt,['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp'],true);$previewable=$isImage||$isPdf||$isOffice;$kind='file';$kindLabel=strtoupper($fileExt?:'FILE');
if(in_array($fileExt,['doc','docx','odt'],true)){$kind='word';$kindLabel='DOC';}elseif(in_array($fileExt,['xls','xlsx','ods','csv'],true)){$kind='sheet';$kindLabel='XLS';}elseif(in_array($fileExt,['ppt','pptx','odp'],true)){$kind='slides';$kindLabel='PPT';}elseif($isPdf){$kind='pdf';$kindLabel='PDF';}elseif($isImage){$kind='image';$kindLabel='IMG';}elseif(in_array($fileExt,['mp4','m4v','mov','webm','ogv','avi','mkv'],true)){$kind='video';$kindLabel='VIDEO';}elseif(in_array($fileExt,['mp3','wav','ogg','m4a','flac','aac'],true)){$kind='audio';$kindLabel='AUDIO';}elseif(in_array($fileExt,['zip','rar','7z','tar','gz','bz2'],true)){$kind='archive';$kindLabel='ZIP';}elseif(in_array($fileExt,['php','js','ts','html','css','json','xml','py','sh','sql'],true)){$kind='code';$kindLabel='CODE';}
$previewUrl='/files/preview/'.(int)$f['id'];$openUrl=$previewable?$previewUrl:'/files/download/'.(int)$f['id'];
?>
<tr class="dashboard-file-list-row file-manager-list-row"><td class="file-manager-select-cell"><input type="checkbox" class="form-check-input drive-item-select" data-item-type="file" value="<?= (int)$f['id'] ?>" aria-label="Pilih <?= esc($f['original_name'],'attr') ?>"></td>
<td><a class="dashboard-list-name" href="<?= esc($openUrl,'attr') ?>"><span class="dashboard-file-kind dashboard-file-kind--<?= esc($kind,'attr') ?>" aria-hidden="true"><?= esc($kindLabel) ?></span><span class="dashboard-list-filename" title="<?= esc($f['original_name'],'attr') ?>"><?= !empty($f['is_starred'])?'★ ':'' ?><?= esc($f['original_name']) ?></span></a></td>
<td><span class="drive-type-label" title="<?= esc($f['mime_type'],'attr') ?>"><?= esc(strtoupper($fileExt?:'FILE')) ?><small class="d-block text-secondary"><?= esc($fileMime?:'Tipe tidak diketahui') ?></small></span></td>
<td><span class="dashboard-list-history"><?= !empty($f['updated_at'])?esc(date('d-m-Y H:i',strtotime($f['updated_at']))):'Belum ada perubahan' ?><small class="d-block text-secondary"><?= !empty($f['created_at'])&&!empty($f['updated_at'])&&$f['created_at']===$f['updated_at']?'Diunggah':'Terakhir diperbarui' ?></small></span></td>
<td><span class="dashboard-list-location" title="<?= esc($currentLocation??'Drive Saya','attr') ?>">📁 <span><?= esc($currentLocation??'Drive Saya') ?></span></span></td>
<td><span class="drive-size-label"><?= number_format(((int)$f['size_bytes'])/1048576,2) ?> MB</span></td>
<td class="dashboard-list-action-cell"><div class="dropdown floating-action-dropdown"><button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi file"><?= $menuIcon('more') ?></button><div class="dropdown-menu dropdown-menu-end file-action-menu">
<?php if($previewable): ?><a class="dropdown-item" href="<?= esc($previewUrl,'attr') ?>"><?= $menuIcon('preview') ?>Preview</a><?php endif ?><a class="dropdown-item" href="/files/download/<?= (int)$f['id'] ?>"><?= $menuIcon('download') ?>Unduh</a><button type="button" class="dropdown-item" data-file-action="rename" data-url="/files/rename/<?= (int)$f['id'] ?>" data-name="<?= esc($f['original_name'],'attr') ?>"><?= $menuIcon('rename') ?>Ganti nama</button><button type="button" class="dropdown-item" data-file-action="move" data-url="/files/move/<?= (int)$f['id'] ?>" data-folders="<?= esc(json_encode($allFolders??$folders),'attr') ?>"><?= $menuIcon('move') ?>Pindah folder</button><button type="button" class="dropdown-item" data-file-action="share" data-url="/files/share/<?= (int)$f['id'] ?>"><?= $menuIcon('share') ?>Bagi tautan</button><form method="post" action="/files/star/<?= (int)$f['id'] ?>"><?= csrf_field() ?><button class="dropdown-item <?= !empty($f['is_starred'])?'text-warning':'' ?>"><?= !empty($f['is_starred'])?'★ Hapus favorit':'★ Tambahkan favorit' ?></button></form><?php if(!empty($f['has_share'])): ?><form method="post" action="/files/share/revoke/<?= (int)$f['id'] ?>" onsubmit="return confirm('Cabut semua tautan berbagi untuk file ini?')"><?= csrf_field() ?><button class="dropdown-item text-info"><?= $menuIcon('link') ?>Cabut tautan berbagi</button></form><?php endif ?><div class="dropdown-divider"></div><form method="post" action="/files/delete/<?= (int)$f['id'] ?>" onsubmit="return confirm('Hapus file ini? File akan dipindahkan ke Sampah.')"><?= csrf_field() ?><button class="dropdown-item text-danger"><?= $menuIcon('trash') ?>Hapus</button></form>
</div></div></td></tr>
<?php endforeach ?>
</tbody></table></div>

<div class="dashboard-file-grid file-manager-grid">
<?php foreach ($files as $f):
$fileExt=strtolower((string)($f['extension']??''));$fileMime=strtolower((string)($f['mime_type']??''));$isImage=str_starts_with($fileMime,'image/')&&in_array($fileExt,['jpg','jpeg','png','gif','webp'],true);$isPdf=$fileMime==='application/pdf'||$fileExt==='pdf';$isOffice=in_array($fileExt,['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp'],true);$previewable=$isImage||$isPdf||$isOffice;$kind='file';$kindLabel=strtoupper($fileExt?:'FILE');
if(in_array($fileExt,['doc','docx','odt'],true)){$kind='word';$kindLabel='DOC';}elseif(in_array($fileExt,['xls','xlsx','ods','csv'],true)){$kind='sheet';$kindLabel='XLS';}elseif(in_array($fileExt,['ppt','pptx','odp'],true)){$kind='slides';$kindLabel='PPT';}elseif($isPdf){$kind='pdf';$kindLabel='PDF';}elseif($isImage){$kind='image';$kindLabel='IMG';}elseif(in_array($fileExt,['mp4','m4v','mov','webm','ogv','avi','mkv'],true)){$kind='video';$kindLabel='VIDEO';}elseif(in_array($fileExt,['mp3','wav','ogg','m4a','flac','aac'],true)){$kind='audio';$kindLabel='AUDIO';}elseif(in_array($fileExt,['zip','rar','7z','tar','gz','bz2'],true)){$kind='archive';$kindLabel='ZIP';}elseif(in_array($fileExt,['php','js','ts','html','css','json','xml','py','sh','sql'],true)){$kind='code';$kindLabel='CODE';}
$openUrl=($isImage||$isPdf||$isOffice)?'/files/preview/'.(int)$f['id']:'/files/download/'.(int)$f['id'];
?>
<article class="dashboard-file-card dashboard-selectable-item file-manager-file-card">
<input type="checkbox" class="form-check-input drive-item-select file-manager-card-select" data-item-type="file" value="<?= (int)$f['id'] ?>" aria-label="Pilih <?= esc($f['original_name'],'attr') ?>">
<div class="dashboard-file-card-head"><span class="dashboard-file-kind dashboard-file-kind--<?= esc($kind,'attr') ?>" aria-hidden="true"><?= esc($kindLabel) ?></span><a class="dashboard-file-title" href="<?= esc($openUrl,'attr') ?>" title="<?= esc($f['original_name'],'attr') ?>"><?= esc($f['original_name']) ?></a><div class="dropdown floating-action-dropdown"><button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi file"><?= $menuIcon('more') ?></button><div class="dropdown-menu dropdown-menu-end file-action-menu">
<?php if($previewable): ?><a class="dropdown-item" href="/files/preview/<?= (int)$f['id'] ?>"><?= $menuIcon('preview') ?>Preview</a><?php endif ?><a class="dropdown-item" href="/files/download/<?= (int)$f['id'] ?>"><?= $menuIcon('download') ?>Unduh</a><button type="button" class="dropdown-item" data-file-action="rename" data-url="/files/rename/<?= (int)$f['id'] ?>" data-name="<?= esc($f['original_name'],'attr') ?>"><?= $menuIcon('rename') ?>Ganti nama</button><button type="button" class="dropdown-item" data-file-action="move" data-url="/files/move/<?= (int)$f['id'] ?>" data-folders="<?= esc(json_encode($allFolders??$folders),'attr') ?>"><?= $menuIcon('move') ?>Pindah folder</button><button type="button" class="dropdown-item" data-file-action="share" data-url="/files/share/<?= (int)$f['id'] ?>"><?= $menuIcon('share') ?>Bagi tautan</button><form method="post" action="/files/star/<?= (int)$f['id'] ?>"><?= csrf_field() ?><button class="dropdown-item <?= !empty($f['is_starred'])?'text-warning':'' ?>"><?= !empty($f['is_starred'])?'★ Hapus favorit':'★ Tambahkan favorit' ?></button></form><?php if(!empty($f['has_share'])): ?><form method="post" action="/files/share/revoke/<?= (int)$f['id'] ?>" onsubmit="return confirm('Cabut semua tautan berbagi untuk file ini?')"><?= csrf_field() ?><button class="dropdown-item text-info"><?= $menuIcon('link') ?>Cabut tautan berbagi</button></form><?php endif ?><div class="dropdown-divider"></div><form method="post" action="/files/delete/<?= (int)$f['id'] ?>" onsubmit="return confirm('Hapus file ini? File akan dipindahkan ke Sampah.')"><?= csrf_field() ?><button class="dropdown-item text-danger"><?= $menuIcon('trash') ?>Hapus</button></form>
</div></div></div>
<a class="dashboard-file-preview dashboard-file-preview--<?= esc($kind,'attr') ?>" href="<?= esc($openUrl,'attr') ?>" aria-label="Buka <?= esc($f['original_name'],'attr') ?>">
<?php if($previewable): ?><img src="/files/thumbnail/<?= (int)$f['id'] ?>" alt="Preview <?= esc($f['original_name'],'attr') ?>" loading="lazy" onerror="this.closest('.dashboard-file-preview').classList.add('is-fallback');this.remove()"><span class="dashboard-preview-fallback"><?= esc($kindLabel) ?></span><?php else: ?><span class="dashboard-file-art-icon"><?= $kind==='video'?'▷':($kind==='audio'?'♫':($kind==='archive'?'▣':'▤')) ?></span><span class="dashboard-file-art-label"><?= esc($kindLabel) ?></span><?php endif ?>
</a><div class="dashboard-file-activity"><span class="dashboard-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string)(session()->get('name')?:'A'),0,1))) ?></span><span><?= esc(session()->get('name')?:'Anda') ?> mengubahnya · <?= !empty($f['updated_at'])?esc(date('j M',strtotime($f['updated_at']))):'Baru saja' ?></span></div></article>
<?php endforeach ?></div>
<?php else: ?><div class="dashboard-empty">Belum ada file di folder ini.</div><?php endif ?>
</section>
</div><!-- /driveViewRoot -->
<?= $this->endSection() ?>
