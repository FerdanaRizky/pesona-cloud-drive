(()=>{const root=document.documentElement;const saved=localStorage.getItem('pcd-theme');if(saved)root.setAttribute('data-bs-theme',saved);const themeButton=document.getElementById('themeToggle');const themeLabel=document.getElementById('themeModeLabel');const syncThemeLabel=()=>{const mode=root.getAttribute('data-bs-theme')==='light'?'Terang':'Dark';if(themeLabel)themeLabel.textContent=mode;if(themeButton)themeButton.setAttribute('aria-label','Tema saat ini '+mode+'. Klik untuk mengganti tema.');};syncThemeLabel();themeButton?.addEventListener('click',()=>{const next=root.getAttribute('data-bs-theme')==='dark'?'light':'dark';root.setAttribute('data-bs-theme',next);localStorage.setItem('pcd-theme',next);syncThemeLabel();});const eye='<svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';const eyeOff='<svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-3.2 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 1.9-.2 2.8-.5"/></svg>';const togglePasswordVisibility=button=>{const input=document.getElementById(button.dataset.target);if(!input)return;const reveal=input.type==='password';input.type=reveal?'text':'password';button.innerHTML=reveal?eyeOff:eye;button.setAttribute('aria-pressed',String(reveal));const label=(button.getAttribute('aria-label')||'password').replace(/^(Tampilkan|Sembunyikan) /,'');button.setAttribute('aria-label',(reveal?'Sembunyikan ':'Tampilkan ')+label);};document.addEventListener('click',event=>{const button=event.target.closest('button.toggle-password[data-target]');if(!button)return;event.preventDefault();event.stopPropagation();togglePasswordVisibility(button);});
const sidebarToggle=document.getElementById('sidebarToggle'),sidebarBackdrop=document.getElementById('sidebarBackdrop'),sidebar=document.getElementById('appSidebar');
const closeSidebar=()=>{document.body.classList.remove('sidebar-open');sidebarToggle?.setAttribute('aria-expanded','false');sidebarToggle?.setAttribute('aria-label','Buka navigasi');};
sidebarToggle?.addEventListener('click',()=>{const open=!document.body.classList.contains('sidebar-open');document.body.classList.toggle('sidebar-open',open);sidebarToggle.setAttribute('aria-expanded',String(open));sidebarToggle.setAttribute('aria-label',open?'Tutup navigasi':'Buka navigasi');});
sidebarBackdrop?.addEventListener('click',closeSidebar);
sidebar?.querySelectorAll('a.nav-link').forEach(link=>link.addEventListener('click',closeSidebar));
document.addEventListener('keydown',event=>{if(event.key==='Escape')closeSidebar();});
window.addEventListener('resize',()=>{if(window.innerWidth>991)closeSidebar();});
/* Drive interaction: single click selects, double click opens (list and grid). */
const driveRoot=document.getElementById('driveViewRoot');
if(driveRoot){
 const getItemTarget=target=>target.closest('tr.file-row, .folder-tile');
 const isControl=target=>!!target.closest('button,input,select,textarea,form,.dropdown-menu,[data-bs-toggle],.file-action-menu,label.multi-select-control');
 const selectDriveItem=(item,event)=>{
  const checkbox=item?.querySelector('.drive-item-select');
  if(!checkbox)return;
  const all=[...driveRoot.querySelectorAll('.drive-item-select')];
  if(event?.ctrlKey||event?.metaKey){
   checkbox.checked=!checkbox.checked;
  }else{
   all.forEach(box=>{box.checked=box===checkbox;});
  }
  all.forEach(box=>box.dispatchEvent(new Event('change',{bubbles:true})));
 };
 driveRoot.addEventListener('click',event=>{
  const item=getItemTarget(event.target);
  if(!item||isControl(event.target))return;
  const link=event.target.closest('a');
  if(link&&item.contains(link))event.preventDefault();
  selectDriveItem(item,event);
 });
 driveRoot.addEventListener('dblclick',event=>{
  const item=getItemTarget(event.target);
  if(!item||isControl(event.target))return;
  const link=item.querySelector('a[href]');
  if(!link)return;
  event.preventDefault();
  window.location.assign(link.href);
 });
 driveRoot.addEventListener('change',event=>{
  const checkbox=event.target.closest('.drive-item-select');
  if(!checkbox)return;
  const item=checkbox.closest('tr.file-row, .folder-tile');
  item?.classList.toggle('is-drive-selected',checkbox.checked);
 });
}

})();