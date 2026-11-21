<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $generatedShareId = (int) (session()->getFlashdata('share_id') ?? 0); $generatedShareLink = session()->getFlashdata('share_link'); $generatedShareIds = array_map('intval', (array) (session()->getFlashdata('share_ids') ?? [])); ?>
<div class="share-page-heading d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
 <div><div class="share-page-eyebrow">BERBAGI FILE</div><h1 class="h3 mb-1">Manajemen Link Berbagi</h1><p class="text-secondary mb-0">Kelola tautan, tampilkan kembali URL, perbarui masa berlaku, atau cabut akses.</p></div>
 <a class="btn btn-outline-secondary share-back-button" href="/files"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6M9 12h12"/></svg>Kembali ke File</a>
</div>

<form id="bulkShareForm" method="post" class="share-bulk-toolbar selection-toolbar mb-3" aria-hidden="true">
<?= csrf_field() ?><div id="bulkShareIds"></div>
<div class="share-bulk-info"><button type="button" class="selection-clear-icon" id="clearShareSelection" aria-label="Batalkan pilihan" title="Batalkan pilihan">×</button><strong id="bulkShareCount">0 link dipilih</strong></div>
<div class="share-bulk-actions"><button type="button" class="selection-action-btn" id="shareUpdateIcon" title="Perbarui masa berlaku" aria-label="Perbarui masa berlaku">↻</button><button type="button" class="selection-action-btn danger" id="shareRevokeIcon" title="Cabut link" aria-label="Cabut link">⌫</button><div class="share-expiry-inline">
<select name="expires_days" id="bulkShareExpiry" class="form-select form-select-sm" aria-label="Masa berlaku baru"><option value="1">1 hari</option><option value="7" selected>7 hari</option><option value="30">30 hari</option><option value="never">Selamanya</option></select></div>
<button type="button" class="btn btn-sm btn-primary" id="bulkShareUpdate" disabled><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M5.5 9A7 7 0 0 1 18 6l2 2M4 16l2 2a7 7 0 0 0 12.5-3"/></svg>Perbarui</button>

</div></form>
<div class="card share-management-card p-3 p-lg-4"><div class="table-responsive share-table-wrap"><table class="table align-middle share-management-table"><thead><tr><th class="share-select-column"><input type="checkbox" class="form-check-input" id="selectAllShares" aria-label="Pilih semua link"></th><th>File</th><th>Dibuat</th><th>Masa berlaku</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
<?php foreach ($shares as $share): ?><tr class="share-row"><td class="share-select-column"><input type="checkbox" class="form-check-input share-row-select" value="<?= (int)$share['share_id'] ?>" aria-label="Pilih link <?= esc($share['original_name'], 'attr') ?>"></td><td><div class="share-file-name"><span class="share-file-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg></span><span class="fw-semibold"><?= esc($share['original_name']) ?></span></div><div class="share-link-panel mt-2" id="shareLinkPanel<?= (int)$share['share_id'] ?>" hidden><?php $rowShareUrl = !empty($share['share_url']) ? $share['share_url'] : (($generatedShareId === (int)$share['share_id'] && is_string($generatedShareLink)) ? $generatedShareLink : null); ?><?php if ($rowShareUrl): ?><div class="input-group input-group-sm"><input class="form-control" readonly value="<?= esc($rowShareUrl) ?>"><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value).then(()=>this.textContent='Tersalin')">Salin</button></div><?php else: ?><small class="text-warning">URL lama tidak tersimpan. Untuk file ini, buat tautan baru dari menu File Saya agar URL dapat disimpan dan ditampilkan.</small><?php endif ?></div><small class="text-secondary d-block mt-1"><?= esc($share['mime_type']) ?> · <?= number_format($share['size_bytes']/1048576,2) ?> MB</small></td><td class="text-nowrap"><?= esc($share['created_at']) ?></td><td><?= $share['expires_at'] ? esc($share['expires_at']) : 'Tidak kedaluwarsa' ?></td><td><?php if ($share['expired']): ?><span class="badge text-bg-secondary">Kedaluwarsa</span><?php else: ?><span class="badge text-bg-success">Aktif</span><?php endif ?></td><td class="text-end"><div class="dropdown share-actions-dropdown"><button class="btn btn-outline-secondary share-actions-trigger" id="shareActionsTrigger<?= (int)$share['share_id'] ?>" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi link berbagi" title="Aksi lainnya"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg></button><ul class="dropdown-menu dropdown-menu-end share-actions-menu" data-owner-trigger="shareActionsTrigger<?= (int)$share['share_id'] ?>"><li><?php if (!empty($share['share_url']) || $generatedShareId === (int)$share['share_id']): ?><button class="dropdown-item" type="button" data-toggle-share-link="#shareLinkPanel<?= (int)$share['share_id'] ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.07 0l3-3A5 5 0 0 0 13 2.93l-1.72 1.72"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3A5 5 0 0 0 11 21.07l1.72-1.72"/></svg><span>Tampilkan link kembali</span></button><?php else: ?><button class="dropdown-item" type="button" disabled title="URL lama tidak tersimpan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.07 0l3-3A5 5 0 0 0 13 2.93l-1.72 1.72"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3A5 5 0 0 0 11 21.07l1.72-1.72"/></svg><span>Link tidak tersedia</span></button><?php endif ?></li><li><button class="dropdown-item" type="button" data-bs-toggle="collapse" data-bs-target="#shareExpiry<?= (int)$share['share_id'] ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>Perbarui masa berlaku</span></button></li><li><hr class="dropdown-divider"></li><li><form method="post" action="/shares/revoke/<?= (int)$share['share_id'] ?>" onsubmit="return confirm('Cabut link ini? Tautan tidak dapat digunakan lagi.')"><?= csrf_field() ?><button class="dropdown-item text-danger" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 11v5m4-5v5"/></svg><span>Cabut link</span></button></form></li></ul></div><div class="collapse share-expiry-panel mt-2" id="shareExpiry<?= (int)$share['share_id'] ?>"><form method="post" action="/shares/update/<?= (int)$share['share_id'] ?>" class="d-flex gap-1"><?= csrf_field() ?><select name="expires_days" class="form-select form-select-sm" aria-label="Ubah masa berlaku"><option value="1" <?= $share['expiry_option']==='1'?'selected':'' ?>>1 hari</option><option value="7" <?= $share['expiry_option']==='7'?'selected':'' ?>>7 hari</option><option value="30" <?= $share['expiry_option']==='30'?'selected':'' ?>>30 hari</option><option value="never" <?= $share['expiry_option']==='never'?'selected':'' ?>>Selamanya</option></select><button class="btn btn-sm btn-primary share-expiry-save"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>Simpan</button></form></div></td></tr><?php endforeach ?>
<?php if (!$shares): ?><tr><td colspan="6" class="text-center text-secondary py-5">Belum ada link berbagi. Buat dari menu aksi pada file.</td></tr><?php endif ?></tbody></table></div></div>
<script>
// Keep only one share URL expanded at a time, while remembering the last opened row.
(function(){
 const storageKey='pesona-cloud-open-share-panels';
 function readOpenPanel(){
  try {
   const value=JSON.parse(sessionStorage.getItem(storageKey)||'[]');
   return Array.isArray(value)&&value.length ? String(value[value.length-1]) : '';
  } catch(e){ return ''; }
 }
 function saveOpenPanel(panelId){
  try { sessionStorage.setItem(storageKey,JSON.stringify(panelId?[panelId]:[])); } catch(e) {}
 }
 function setPanelState(panel,open){
  panel.hidden=!open;
  const button=document.querySelector('[data-toggle-share-link="#'+panel.id+'"]');
  if(button){
   button.setAttribute('aria-expanded',open?'true':'false');
   const label=button.querySelector('span');
   if(label)label.textContent=open?'Sembunyikan link':'Tampilkan link kembali';
  }
 }
 document.addEventListener('DOMContentLoaded',function(){
  const remembered=readOpenPanel();
  document.querySelectorAll('.share-link-panel[id]').forEach(function(panel){
   setPanelState(panel,panel.id===remembered);
  });
  saveOpenPanel(remembered);
 });
 // Opening an expiry editor closes the visible URL and any other expiry editor.
 document.addEventListener('show.bs.collapse',function(event){
  const target=event.target;
  if(!target.classList.contains('share-expiry-panel'))return;
  document.querySelectorAll('.share-link-panel[id]').forEach(function(panel){setPanelState(panel,false);});
  saveOpenPanel('');
  document.querySelectorAll('.share-expiry-panel.show').forEach(function(other){
   if(other!==target && window.bootstrap?.Collapse){
    window.bootstrap.Collapse.getOrCreateInstance(other,{toggle:false}).hide();
   }
  });
 });
 document.addEventListener('click',function(event){
  const button=event.target.closest('[data-toggle-share-link]');
  if(!button)return;
  event.preventDefault();
  const selector=button.getAttribute('data-toggle-share-link');
  const panel=selector ? document.querySelector(selector) : null;
  if(!panel)return;
  const willShow=panel.hidden;
  // Showing a URL closes every expiry editor first.
  if(willShow){
   document.querySelectorAll('.share-expiry-panel.show').forEach(function(expiryPanel){
    if(window.bootstrap?.Collapse)window.bootstrap.Collapse.getOrCreateInstance(expiryPanel,{toggle:false}).hide();
   });
  }
  // Close every other row before opening the requested URL.
  document.querySelectorAll('.share-link-panel[id]').forEach(function(other){
   setPanelState(other,willShow && other.id===panel.id);
  });
  saveOpenPanel(willShow?panel.id:'');
  const menu=button.closest('.share-actions-menu');
  if(menu){
   const triggerId=menu.getAttribute('data-owner-trigger');
   const trigger=triggerId ? document.getElementById(triggerId) : null;
   if(trigger && window.bootstrap?.Dropdown){
    const instance=window.bootstrap.Dropdown.getInstance(trigger);
    if(instance)instance.hide();
   }
  }
 });
})();
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('.share-actions-dropdown').forEach(function(dropdown){
  const trigger=dropdown.querySelector('[data-bs-toggle="dropdown"]');
  const menu=dropdown.querySelector('.share-actions-menu');
  if(!trigger||!menu)return;
  let placeholder=null;
  dropdown.addEventListener('shown.bs.dropdown',function(){
   placeholder=document.createComment('share-action-menu-position');
   menu.parentNode.insertBefore(placeholder,menu);
   document.body.appendChild(menu);
   const rect=trigger.getBoundingClientRect();
   const width=Math.min(Math.max(menu.offsetWidth,220),window.innerWidth-24);
   const height=Math.min(menu.scrollHeight,window.innerHeight-24);
   const left=Math.max(12,Math.min(rect.right-width,window.innerWidth-width-12));
   let top=rect.bottom+8;
   if(top+height>window.innerHeight-12)top=Math.max(12,rect.top-height-8);
   menu.style.position='fixed';menu.style.left=left+'px';menu.style.top=top+'px';
   menu.style.right='auto';menu.style.bottom='auto';menu.style.transform='none';menu.style.zIndex='3000';
  });
  dropdown.addEventListener('hidden.bs.dropdown',function(){
   if(placeholder&&placeholder.parentNode){placeholder.parentNode.insertBefore(menu,placeholder);placeholder.remove();}
   menu.removeAttribute('style');placeholder=null;
  });
 });
});
document.addEventListener('DOMContentLoaded',function(){
 const form=document.getElementById('bulkShareForm'),ids=document.getElementById('bulkShareIds'),count=document.getElementById('bulkShareCount'),all=document.getElementById('selectAllShares');
 const update=document.getElementById('bulkShareUpdate'),revoke=document.getElementById('bulkShareRevoke');
 if(!form||!ids)return;
 const boxes=()=>Array.from(document.querySelectorAll('.share-row-select'));
 function refresh(){const selected=boxes().filter(x=>x.checked);count.textContent=selected.length+' link dipilih';ids.innerHTML='';selected.forEach(x=>{const input=document.createElement('input');input.type='hidden';input.name='share_ids[]';input.value=x.value;ids.appendChild(input)});update.disabled=revoke.disabled=selected.length===0;if(all){all.checked=boxes().length>0&&selected.length===boxes().length;all.indeterminate=selected.length>0&&selected.length<boxes().length;}}
 boxes().forEach(x=>x.addEventListener('change',refresh));
 all?.addEventListener('change',()=>{boxes().forEach(x=>x.checked=all.checked);refresh()});
 update.addEventListener('click',()=>{if(!boxes().some(x=>x.checked))return;form.action='/shares/bulk-update';form.submit()});
 revoke.addEventListener('click',()=>{if(!boxes().some(x=>x.checked))return;if(!confirm('Cabut semua link yang dipilih? Tautan tidak dapat digunakan lagi.'))return;form.action='/shares/bulk-revoke';form.submit()});

 // Row click selects a share; double click opens its saved URL panel.
 const shareRoot=document.querySelector('.share-management-card');
 const shareIsControl=target=>!!target.closest('button,input,select,textarea,form,a,.dropdown-menu,[data-bs-toggle],.share-actions-menu,.share-link-panel,.share-expiry-panel');
 const selectShareRow=(row,event)=>{const box=row?.querySelector('.share-row-select');if(!box)return;const all=Array.from(shareRoot.querySelectorAll('.share-row-select'));if(event?.ctrlKey||event?.metaKey){box.checked=!box.checked;}else{all.forEach(item=>item.checked=item===box);}all.forEach(item=>item.dispatchEvent(new Event('change',{bubbles:true})));};
 shareRoot?.addEventListener('click',event=>{const row=event.target.closest('tr.share-row');if(!row||shareIsControl(event.target))return;selectShareRow(row,event);});
 shareRoot?.addEventListener('dblclick',event=>{const row=event.target.closest('tr.share-row');if(!row||shareIsControl(event.target))return;const toggle=row.querySelector('[data-toggle-share-link]');if(toggle){event.preventDefault();toggle.click();}});
 shareRoot?.addEventListener('change',event=>{const box=event.target.closest('.share-row-select');if(!box)return;box.closest('tr.share-row')?.classList.toggle('is-drive-selected',box.checked);});

 refresh();
});
</script>
<?= $this->endSection() ?>