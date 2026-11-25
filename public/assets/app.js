(()=>{const root=document.documentElement;let saved=null;try{saved=localStorage.getItem('pcd-theme');}catch(error){}if(saved==='light'||saved==='dark')root.setAttribute('data-bs-theme',saved);const themeButton=document.getElementById('themeToggle');const themeIcon=document.getElementById('themeToggleIcon');const syncThemeLabel=()=>{const isLight=root.getAttribute('data-bs-theme')==='light';const mode=isLight?'Terang':'Gelap';if(themeIcon)themeIcon.textContent=isLight?'☀':'☾';if(themeButton){themeButton.setAttribute('aria-label','Tema saat ini '+mode+'. Klik untuk mengganti tema.');themeButton.setAttribute('title','Beralih ke tema '+(isLight?'gelap':'terang'));}};const toggleTheme=event=>{if(event)event.preventDefault();const next=root.getAttribute('data-bs-theme')==='dark'?'light':'dark';root.setAttribute('data-bs-theme',next);try{localStorage.setItem('pcd-theme',next);}catch(error){}syncThemeLabel();};syncThemeLabel();const eye='<svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';const eyeOff='<svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-3.2 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 1.9-.2 2.8-.5"/></svg>';const togglePasswordVisibility=button=>{const input=document.getElementById(button.dataset.target);if(!input)return;const reveal=input.type==='password';input.type=reveal?'text':'password';button.innerHTML=reveal?eyeOff:eye;button.setAttribute('aria-pressed',String(reveal));const label=(button.getAttribute('aria-label')||'password').replace(/^(Tampilkan|Sembunyikan) /,'');button.setAttribute('aria-label',(reveal?'Sembunyikan ':'Tampilkan ')+label);};document.addEventListener('click',event=>{const button=event.target.closest('button.toggle-password[data-target]');if(!button)return;event.preventDefault();event.stopPropagation();togglePasswordVisibility(button);});
const sidebarToggle=document.getElementById('sidebarToggle'),sidebarBackdrop=document.getElementById('sidebarBackdrop'),sidebar=document.getElementById('appSidebar');
const closeSidebar=()=>{document.body.classList.remove('sidebar-open');sidebarToggle?.setAttribute('aria-expanded','false');sidebarToggle?.setAttribute('aria-label','Buka navigasi');};
sidebarToggle?.addEventListener('click',()=>{const open=!document.body.classList.contains('sidebar-open');document.body.classList.toggle('sidebar-open',open);sidebarToggle.setAttribute('aria-expanded',String(open));sidebarToggle.setAttribute('aria-label',open?'Tutup navigasi':'Buka navigasi');});
sidebarBackdrop?.addEventListener('click',closeSidebar);
sidebar?.querySelectorAll('a.nav-link').forEach(link=>link.addEventListener('click',closeSidebar));
document.addEventListener('keydown',event=>{if(event.key==='Escape')closeSidebar();});
window.addEventListener('resize',()=>{if(window.innerWidth>991)closeSidebar();});
/* Unified mobile touch selection for Dashboard, File Saya/Favorit, Sampah and Share links.
   One document-level manager owns long-press, click suppression and selection-mode taps. */
(()=>{
 const isTouchDevice=()=>('ontouchstart' in window)||navigator.maxTouchPoints>0;
 const configs=[
  {id:'dashboardDriveRoot',items:'[data-dashboard-selectable]',kind:'dashboard'},
  {id:'driveViewRoot',items:'.file-manager-list-row,.file-manager-file-card,.file-manager-folder-card',box:'.drive-item-select'},
  {id:'trashViewRoot',items:'tr.file-row',box:'.trash-item-select'},
  {id:'shareViewRoot',items:'tr.share-row',box:'.share-row-select'}
 ];
 const roots=configs.map(c=>({...c,root:document.getElementById(c.id)})).filter(c=>c.root);
 const controlSelector='button,input,select,textarea,form,label,.dropdown,.dropdown-menu,[data-bs-toggle],.multi-select-control,.bulk-action-toolbar,.selection-toolbar,.dashboard-more-btn';
 const findEntry=target=>roots.map(c=>({config:c,item:target.closest(c.items)})).find(x=>x.item&&x.config.root.contains(x.item));
 const isControl=target=>!!target.closest(controlSelector);
 const style=document.createElement('style');
 style.id='pcd-unified-mobile-touch-style';
 style.textContent=`
  #dashboardDriveRoot [data-dashboard-selectable],
  #dashboardDriveRoot [data-dashboard-selectable] *,
  #driveViewRoot .file-manager-list-row, #driveViewRoot .file-manager-list-row *,
  #driveViewRoot .file-manager-file-card, #driveViewRoot .file-manager-file-card *,
  #driveViewRoot .file-manager-folder-card, #driveViewRoot .file-manager-folder-card *,
  #trashViewRoot tr.file-row, #trashViewRoot tr.file-row *,
  #shareViewRoot tr.share-row, #shareViewRoot tr.share-row * {
    -webkit-touch-callout:none !important;
    -webkit-user-select:none !important;
    user-select:none !important;
  }`;
 document.head.appendChild(style);
 let timer=null, startX=0, startY=0, pending=null, suppressClick=false, suppressReset=null;
 const clearLongPress=()=>{if(timer!==null)clearTimeout(timer);timer=null;pending=null;};
 const sendDashboard=(root,item,mode)=>{
  root.dispatchEvent(new CustomEvent('pcd:mobile-toggle',{bubbles:false,detail:{item,mode}}));
 };
 const setSelected=(entry,checked)=>{
  const {config,item}=entry;
  if(config.kind==='dashboard'){sendDashboard(config.root,item,checked?'add':'remove');return;}
  const box=item.querySelector(config.box);
  if(!box)return;
  box.checked=checked;
  box.dispatchEvent(new Event('change',{bubbles:true}));
  item.classList.toggle('is-drive-selected',checked);
 };
 const hasSelection=config=>{
  if(config.kind==='dashboard')return Number(config.root.dataset.mobileSelectedCount||0)>0;
  return [...config.root.querySelectorAll(config.box)].some(box=>box.checked);
 };
 const enterSelection=(entry)=>{
  const {config,item}=entry;
  config.root.dataset.mobileSelectionMode='true';
  setSelected(entry,true);
  if(navigator.vibrate)try{navigator.vibrate(25);}catch(e){}
 };
 const start=(event)=>{
  if(!isTouchDevice()||event.touches?.length!==1)return;
  const entry=findEntry(event.target);
  if(!entry||isControl(event.target))return;
  clearLongPress();
  pending=entry;
  startX=event.touches[0].clientX;startY=event.touches[0].clientY;
  timer=setTimeout(()=>{
   timer=null;
   if(!pending)return;
   enterSelection(pending);
   suppressClick=true;
   if(suppressReset!==null)clearTimeout(suppressReset);
   suppressReset=setTimeout(()=>{suppressClick=false;suppressReset=null;},1100);
  },550);
 };
 document.addEventListener('touchstart',start,{capture:true,passive:true});
 document.addEventListener('touchmove',event=>{
  if(timer===null||!event.touches?.length)return;
  const t=event.touches[0];
  if(Math.abs(t.clientX-startX)>12||Math.abs(t.clientY-startY)>12)clearLongPress();
 },{capture:true,passive:true});
 document.addEventListener('touchend',clearLongPress,{capture:true,passive:true});
 document.addEventListener('touchcancel',clearLongPress,{capture:true,passive:true});
 document.addEventListener('contextmenu',event=>{
  if(isTouchDevice()&&findEntry(event.target)){event.preventDefault();event.stopPropagation();}
 },true);
 document.addEventListener('click',event=>{
  if(!isTouchDevice())return;
  const entry=findEntry(event.target);
  if(suppressClick&&entry){
   suppressClick=false;
   if(suppressReset!==null)clearTimeout(suppressReset);
   suppressReset=null;
   event.preventDefault();event.stopImmediatePropagation();return;
  }
  const active=roots.find(c=>c.root.dataset.mobileSelectionMode==='true');
  if(active){
   if(entry&&entry.config===active&&!isControl(event.target)){
    event.preventDefault();event.stopImmediatePropagation();
    const {config,item}=entry;
    const selected=config.kind==='dashboard'
      ? item.classList.contains('is-dashboard-selected')
      : !!item.querySelector(config.box)?.checked;
    setSelected(entry,!selected);
    if(!hasSelection(config)){
     delete config.root.dataset.mobileSelectionMode;
     if(config.kind==='dashboard')config.root.dataset.mobileSelectedCount='0';
    }
    return;
   }
   if(event.target.closest('#'+active.id+' '+controlSelector))return;
   if(active.root.contains(event.target)){
    const link=event.target.closest('a[href]');
    if(link){event.preventDefault();event.stopImmediatePropagation();}
    return;
   }
  }
  if(entry&&!isControl(event.target)){
   const {config,item}=entry;
   if(config.kind==='dashboard'){
    const link=event.target.closest('a[href]')||item.querySelector('.dashboard-folder-main[href],.dashboard-file-title[href],.dashboard-file-preview[href],.dashboard-list-name[href]');
    if(link&&item.contains(link)&&!event.target.closest('a[href]')){
     event.preventDefault();window.location.assign(link.href);
    }
   }
  }
 },true);
})(); 

/* Drive interaction: single click selects, double click opens (list and grid). */
const driveRoot=document.getElementById('driveViewRoot');
if(driveRoot){
 const getItemTarget=target=>target.closest('tr.file-row, .folder-tile');
 const isControl=target=>!!target.closest('button,input,select,textarea,form,.dropdown-menu,[data-bs-toggle],.file-action-menu,label.multi-select-control');
 let driveSelectionAnchor=null;
 const syncDriveSelection=all=>all.forEach(box=>box.dispatchEvent(new Event('change',{bubbles:true})));
 const selectDriveItem=(item,event)=>{
  const checkbox=item?.querySelector('.drive-item-select');
  if(!checkbox)return;
  const all=[...driveRoot.querySelectorAll('.drive-item-select')];
  const index=all.indexOf(checkbox);
  if(event?.shiftKey&&driveSelectionAnchor?.checked){
   const anchor=all.indexOf(driveSelectionAnchor);
   if(anchor>=0){
    if(!event.ctrlKey&&!event.metaKey)all.forEach(box=>box.checked=false);
    const start=Math.min(anchor,index),end=Math.max(anchor,index);
    all.slice(start,end+1).forEach(box=>box.checked=true);
   }else{checkbox.checked=true;driveSelectionAnchor=checkbox;}
  }else if(event?.ctrlKey||event?.metaKey){
   checkbox.checked=!checkbox.checked;
   driveSelectionAnchor=checkbox;
  }else{
   all.forEach(box=>{box.checked=box===checkbox;});
   driveSelectionAnchor=checkbox;
  }
  syncDriveSelection(all);
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
/* Prevent browser text highlighting while Shift-clicking items for range selection on every Drive page. */
document.addEventListener('mousedown',event=>{
 if(!event.shiftKey)return;
 const selectable=event.target.closest('[data-dashboard-selectable],.file-manager-list-row,.file-manager-file-card,.file-manager-folder-card,tr.file-row,.folder-tile,tr.file-row,.folder-tile');
 if(!selectable)return;
 event.preventDefault();
 const selection=window.getSelection?.();
 selection?.removeAllRanges();
},true);
document.addEventListener('keydown',event=>{
 if(event.key==='Shift')document.documentElement.classList.add('drive-shift-selecting');
});
const clearShiftSelectionMode=()=>document.documentElement.classList.remove('drive-shift-selecting');
document.addEventListener('keyup',event=>{if(event.key==='Shift')clearShiftSelectionMode();});
window.addEventListener('blur',clearShiftSelectionMode);


/* Keep the currently open sidebar section highlighted on every route. */
(()=>{
 const sidebar=document.getElementById('appSidebar');
 if(!sidebar)return;
 const normalize=value=>('/'+String(value||'').replace(/^\/+|\/+$/g,''))
   .replace(/\/index\.php(?=\/|$)/,'')
   .replace(/\/+$/,'')||'/';
 const current=normalize(window.location.pathname);
 const links=[...sidebar.querySelectorAll('.nav-link[href]')];
 let activeLink=null;
 links.forEach(link=>{
   const target=normalize(new URL(link.href,window.location.origin).pathname);
   const matches=target==='/'?current==='/':(current===target||current.startsWith(target+'/'));
   link.classList.toggle('active',matches);
   if(matches&&(!activeLink||target.length>normalize(new URL(activeLink.href,window.location.origin).pathname).length))activeLink=link;
 });
 links.forEach(link=>{if(link!==activeLink)link.classList.remove('active');});
})();


})();