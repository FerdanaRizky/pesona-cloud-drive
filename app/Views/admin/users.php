<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="users-page-head mb-4">
  <div><div class="page-eyebrow">ADMINISTRASI WORKSPACE</div><h1 class="h3 mb-1">Manajemen Pengguna</h1><p class="text-secondary mb-0">Kelola akun, kapasitas penyimpanan, status akses, dan keamanan pengguna.</p></div>
  <div class="users-head-icon">♙</div>
</div>
<div class="row g-3 mb-4 users-metrics-grid">
  <div class="col-6 col-xl-3"><div class="user-metric-card"><span class="metric-icon metric-blue">♙</span><div><div class="muted-label">Total akun</div><div class="metric-value"><?= (int)$stats['total'] ?></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="user-metric-card"><span class="metric-icon metric-green">✓</span><div><div class="muted-label">Akun aktif</div><div class="metric-value"><?= (int)$stats['active'] ?></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="user-metric-card"><span class="metric-icon metric-orange">⏸</span><div><div class="muted-label">Akun nonaktif</div><div class="metric-value"><?= (int)$stats['inactive'] ?></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="user-metric-card"><span class="metric-icon metric-purple">▤</span><div><div class="muted-label">Penyimpanan digunakan</div><div class="metric-value"><?= number_format($stats['storage']/1073741824,2) ?> <small>GB</small></div></div></div></div>
</div>
<div class="d-flex justify-content-end mb-3"><button class="btn create-user-open-button" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal"><span class="create-user-open-icon">＋</span> Tambah pengguna</button></div>
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:620px">
    <div class="modal-content user-create-modal-content">
      <div class="modal-header border-0 pb-0 px-4 pt-4">
        <div class="d-flex align-items-center gap-3"><div class="create-user-icon">＋</div><div><h2 class="h5 mb-1" id="createUserModalLabel">Tambah pengguna baru</h2><p class="small text-secondary mb-0">Buat akun dan tentukan kuota penyimpanan awal.</p></div></div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-4">
        <?php if (session()->getFlashdata('errors')): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ((array) session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
        <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
        
  <form method="post" action="/admin/users/create" class="create-user-form">
    <?= csrf_field() ?>
    <div class="create-user-field"><label class="form-label" for="new_user_name">Nama lengkap</label><input class="form-control" id="new_user_name" name="name" maxlength="120" value="<?= esc(old('name')) ?>" placeholder="Nama pengguna" required></div>
    <div class="create-user-field"><label class="form-label" for="new_user_email">Alamat email</label><input class="form-control" id="new_user_email" type="email" name="email" maxlength="190" value="<?= esc(old('email')) ?>" placeholder="nama@email.com" required></div>
    <div class="create-user-field"><label class="form-label" for="new_user_phone">Nomor WhatsApp</label><input class="form-control" id="new_user_phone" type="tel" name="phone" inputmode="numeric" maxlength="20" value="<?= esc(old('phone')) ?>" placeholder="6281234567890" required><div class="form-text">Format internasional.</div></div>
    <div class="create-user-field"><label class="form-label" for="new_user_password">Password sementara</label><div class="input-group"><input class="form-control" id="new_user_password" type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Minimal 12 karakter" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_user_password" aria-label="Tampilkan password sementara" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
    <div class="create-user-field"><label class="form-label" for="new_user_quota">Kuota penyimpanan (GB)</label><input class="form-control quota-number-input" id="new_user_quota" type="text" inputmode="numeric" pattern="[0-9]{1,5}" name="quota_gb" maxlength="5" autocomplete="off" value="<?= esc(old('quota_gb') ?: '10') ?>" required><div class="form-text">Masukkan angka 1–10.000 GB.</div></div>
    <div class="create-user-actions"><button class="btn create-user-submit w-100" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Simpan</span></button></div>
  </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
 const modalElement=document.getElementById('createUserModal');
 if(!modalElement)return;
 const hasCreateErrors=<?= (session()->has('errors') || session()->has('error') || old('name') !== null) ? 'true' : 'false' ?>;
 if(hasCreateErrors && window.bootstrap?.Modal){bootstrap.Modal.getOrCreateInstance(modalElement).show();}
});
</script>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h2 class="h5 mb-1">Daftar pengguna</h2><p class="small text-secondary mb-0">Atur akses dan keamanan setiap akun.</p></div><span class="users-count-pill"><?= count($users) ?> akun terdaftar</span></div>
<div class="users-search-wrap mb-3">
  <div class="users-search-box">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
    <input type="search" class="form-control" id="userSearchInput" placeholder="Cari nama, email, nomor WhatsApp, atau ID pengguna..." autocomplete="off" aria-label="Cari pengguna">
    <button type="button" class="users-search-clear" id="clearUserSearch" aria-label="Hapus pencarian" hidden>×</button>
  </div>
  <span class="users-search-result small text-secondary" id="userSearchResult"><?= count($users) ?> pengguna</span>
</div>
<div class="row g-3" id="userCardsGrid">
<?php foreach ($users as $u): ?>
<div class="col-12 col-xl-6">
  <article class="user-management-card h-100" data-user-search="<?= esc(strtolower(implode(' ', [(string)$u['name'], (string)$u['email'], (string)($u['phone'] ?? ''), (string)$u['id'], (string)$u['role'], (string)$u['status']])), 'attr') ?>">
    <div class="user-card-heading">
      <div class="user-identity">
        <div class="user-card-avatar <?= $u['role']==='admin'?'avatar-admin':'' ?>"><?= esc(strtoupper(substr((string)$u['name'],0,1))) ?></div>
        <div class="min-width-0"><div class="d-flex align-items-center flex-wrap gap-2"><strong class="user-card-name"><?= esc($u['name']) ?></strong><?php if ((int)$u['id']===(int)session()->get('user_id')): ?><span class="badge text-bg-primary">Anda</span><?php endif ?></div><div class="small text-secondary text-break"><?= esc($u['email']) ?></div></div>
      </div>
      <div class="user-card-status-area">
        <span class="role-pill <?= $u['role']==='admin'?'role-admin':'role-user' ?>"><?= esc(ucfirst($u['role'])) ?></span>
        <div class="user-status-inline">
          <span class="status-pill <?= $u['status']==='active'?'status-active':'status-suspended' ?>"><i></i><?= $u['status']==='active'?'Aktif':'Ditangguhkan' ?></span>
          <?php if ((int)$u['id'] !== (int)session()->get('user_id')): ?>
          <form method="post" action="/admin/users/toggle-status/<?= (int)$u['id'] ?>" class="user-status-switch-form user-status-switch-inline">
            <?= csrf_field() ?>
            <label class="account-switch" title="<?= $u['status']==='active'?'Nonaktifkan akun':'Aktifkan akun' ?>" aria-label="<?= $u['status']==='active'?'Nonaktifkan akun':'Aktifkan akun' ?>">
              <input type="checkbox" <?= $u['status']==='active'?'checked':'' ?> onchange="if(confirm('<?= $u['status']==='active'?'Nonaktifkan':'Aktifkan' ?> akun <?= esc($u['name'], 'js') ?>?')) this.form.requestSubmit(); else this.checked=<?= $u['status']==='active'?'true':'false' ?>;">
              <span class="account-switch-track"><span class="account-switch-thumb"></span></span>
            </label>
          </form>
          <form method="post" action="/admin/users/delete/<?= (int)$u['id'] ?>" class="user-delete-inline-form" onsubmit="return confirm('Hapus akun <?= esc($u['name'], 'js') ?> secara permanen? Penghapusan hanya tersedia jika pengguna belum memiliki file atau folder.')">
            <?= csrf_field() ?>
            <button class="user-delete-icon-btn" type="submit" title="Hapus pengguna" aria-label="Hapus pengguna"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5.5 7l1 13h11l1-13M9 7V4h6v3"/></svg></button>
          </form>
          <?php else: ?><span class="status-self-label">Akun Anda</span><?php endif ?>
        </div>
      </div>
    </div>
    <div class="user-profile-panel mb-3"><div class="control-heading mb-2">Profil pengguna</div><div class="user-profile-summary"><div class="user-profile-detail"><span class="profile-detail-label">Nama lengkap</span><strong><?= esc($u['name']) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Email akun</span><strong class="text-break"><?= esc($u['email']) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Nomor WhatsApp</span><strong><?= !empty($u['phone']) ? esc($u['phone']) : 'Belum ditambahkan' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Peran akun</span><strong><?= esc(ucfirst($u['role'])) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Status akun</span><strong><?= $u['status']==='active'?'Aktif':'Ditangguhkan' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Bergabung</span><strong><?= !empty($u['created_at']) ? esc(date('d M Y', strtotime($u['created_at']))) : '—' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">ID pengguna</span><strong>#<?= (int)$u['id'] ?></strong></div></div></div>
    
    <div class="user-storage-block">
      <div class="d-flex justify-content-between align-items-center mb-2"><span class="small text-secondary">Penyimpanan terpakai</span><strong class="small"><?= number_format(((int)$u['used_bytes'])/1073741824,2) ?> / <?= number_format(((int)$u['quota_bytes'])/1073741824,1) ?> GB</strong></div>
      <div class="progress user-storage-progress"><div class="progress-bar" style="width:<?= min(100,(int)round(((int)$u['used_bytes']/max(1,(int)$u['quota_bytes']))*100)) ?>%"></div></div>
    </div>
    <div class="user-card-controls">
      <form method="post" action="/admin/users/update/<?= (int)$u['id'] ?>" class="user-setting-form">
        <?= csrf_field() ?><div class="control-heading">Akses & kuota</div>
        <div class="mb-3"><label class="form-label small" for="user_phone_<?= (int)$u['id'] ?>">Nomor WhatsApp profil</label><input class="form-control form-control-sm" id="user_phone_<?= (int)$u['id'] ?>" type="tel" name="phone" inputmode="numeric" maxlength="20" placeholder="6281234567890" value="<?= esc($u['phone'] ?? '') ?>"><div class="form-text">Format internasional, contoh 6281234567890.</div></div><div class="quota-edit-group"><label class="form-label small" for="user_quota_<?= (int)$u['id'] ?>">Kuota penyimpanan (GB)</label><div class="quota-edit-row"><input class="form-control form-control-sm quota-number-input" id="user_quota_<?= (int)$u['id'] ?>" type="text" inputmode="numeric" pattern="[0-9]{1,5}" maxlength="5" autocomplete="off" name="quota_gb" value="<?= (int)floor(((int)$u['quota_bytes'])/1073741824) ?>" required><button class="btn quota-save-button" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5 9.5 17 19 7.5"/><path d="M20 12a8 8 0 1 1-2.34-5.66"/></svg><span>Simpan</span></button></div><div class="form-text">Masukkan kapasitas dalam GB (1–10.000).</div></div>
      </form>
      <form method="post" action="/admin/users/reset-password/<?= (int)$u['id'] ?>" class="user-password-form">
        <?= csrf_field() ?><div class="control-heading">Keamanan password</div>
        <div class="input-group input-group-sm"><input class="form-control" id="reset_password_<?= (int)$u['id'] ?>" type="password" name="new_password" minlength="12" autocomplete="new-password" placeholder="Password baru (min. 12 karakter)" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="reset_password_<?= (int)$u['id'] ?>" tabindex="0" aria-label="Tampilkan password baru" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
        <button class="btn btn-sm btn-outline-warning mt-2">Reset password</button>
        <?php if (!empty($u['must_change_password'])): ?><span class="small text-warning ms-2">Wajib ganti password</span><?php endif ?>
      </form>

    </div>
  </article>
</div>
<?php endforeach ?>
<?php if (!$users): ?><div class="col-12"><div class="empty-users"><span>♙</span><strong>Belum ada pengguna</strong><small>Buat akun pengguna pertama melalui formulir di atas.</small></div></div><?php endif ?>
</div>
<style>.user-profile-panel{border:1px solid var(--bs-border-color);border-radius:10px;padding:12px}.user-profile-panel .user-profile-summary{margin-top:0}</style>

<script>
(function(){
 const input=document.getElementById('userSearchInput');
 const clear=document.getElementById('clearUserSearch');
 const grid=document.getElementById('userCardsGrid');
 const result=document.getElementById('userSearchResult');
 if(!input||!grid||!result)return;
 const cards=Array.from(grid.querySelectorAll('.user-management-card'));
 const empty=document.createElement('div');
 empty.className='col-12';
 empty.innerHTML='<div class="empty-users"><span>⌕</span><strong>Pengguna tidak ditemukan</strong><small>Coba kata kunci nama, email, nomor WhatsApp, atau ID pengguna lainnya.</small></div>';
 empty.hidden=true;
 grid.appendChild(empty);
 function filter(){
  const query=input.value.trim().toLocaleLowerCase();
  let visible=0;
  cards.forEach(card=>{
   const match=!query||(card.dataset.userSearch||'').includes(query);
   card.closest('.col-12').hidden=!match;
   if(match)visible++;
  });
  empty.hidden=visible!==0;
  result.textContent=query?visible+' dari '+cards.length+' pengguna':' '+cards.length+' pengguna';
  clear.hidden=!query;
 }
 input.addEventListener('input',filter);
 clear.addEventListener('click',()=>{input.value='';filter();input.focus();});
})();
</script>
<?= $this->endSection() ?>