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
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:720px">
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
    <div class="create-user-field"><label for="new_user_name" class="form-label required-label">Nama lengkap</label><input class="form-control" id="new_user_name" name="name" maxlength="120" value="<?= esc(old('name')) ?>" placeholder="Nama pengguna" required></div>
    <div class="create-user-field"><label for="new_user_email" class="form-label required-label">Alamat email</label><input class="form-control" id="new_user_email" type="email" name="email" maxlength="190" value="<?= esc(old('email')) ?>" placeholder="nama@email.com" required></div>
    <div class="create-user-field"><label for="new_user_phone" class="form-label required-label">Nomor WhatsApp</label><input class="form-control" id="new_user_phone" type="tel" name="phone" inputmode="numeric" maxlength="20" value="<?= esc(old('phone')) ?>" placeholder="6281234567890" required><div class="form-text">Format internasional.</div></div>
    <div class="create-user-field"><label for="new_user_password" class="form-label required-label">Password sementara</label><div class="password-input-wrap"><input class="form-control" id="new_user_password" type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Minimal 12 karakter" required><button class="btn btn-outline-secondary toggle-password password-inline-toggle" type="button" data-target="new_user_password" aria-label="Tampilkan password sementara" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
    <div class="create-user-field"><label for="new_user_quota" class="form-label required-label">Kuota penyimpanan (GB)</label><input class="form-control quota-number-input" id="new_user_quota" type="text" inputmode="numeric" pattern="[0-9]{1,5}" name="quota_gb" maxlength="5" autocomplete="off" value="<?= esc(old('quota_gb') ?: '10') ?>" required><div class="form-text">Masukkan angka 1–10.000 GB.</div></div>
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
        <span class="role-pill <?= $u['role']==='admin'?'role-admin':'role-user' ?>"><?= $u['role']==='admin'?'Superadmin':'User' ?></span>
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
    <div class="user-profile-panel mb-3"><div class="control-heading mb-2">Profil pengguna</div><div class="user-profile-summary"><div class="user-profile-detail"><span class="profile-detail-label">Nama lengkap</span><strong><?= esc($u['name']) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Email akun</span><strong class="text-break"><?= esc($u['email']) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Nomor WhatsApp</span><strong class="user-phone-value"><?= !empty($u['phone']) ? esc($u['phone']) : 'Belum ditambahkan' ?> <button type="button" class="user-phone-edit-button" data-bs-toggle="modal" data-bs-target="#editPhoneModal" data-user-id="<?= (int)$u['id'] ?>" data-user-name="<?= esc($u['name'], 'attr') ?>" data-user-phone="<?= esc($u['phone'] ?? '', 'attr') ?>" data-user-quota="<?= (int)floor(((int)$u['quota_bytes'])/1073741824) ?>" title="Ubah nomor WhatsApp" aria-label="Ubah nomor WhatsApp pengguna <?= esc($u['name'], 'attr') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg></button></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Peran akun</span><strong class="user-role-value"><?= $u['role']==='admin'?'Superadmin':'User' ?><?php if ((int)$u['id'] !== (int)session()->get('user_id')): ?> <button type="button" class="user-role-edit-button" data-bs-toggle="modal" data-bs-target="#editRoleModal" data-user-id="<?= (int)$u['id'] ?>" data-user-name="<?= esc($u['name'], 'attr') ?>" data-user-role="<?= $u['role']==='admin'?'superadmin':'user' ?>" title="Ubah peran" aria-label="Ubah peran pengguna <?= esc($u['name'], 'attr') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg></button><?php endif ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Status akun</span><strong><?= $u['status']==='active'?'Aktif':'Ditangguhkan' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Bergabung</span><strong><?= !empty($u['created_at']) ? esc(date('d M Y', strtotime($u['created_at']))) : '—' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">ID pengguna</span><strong>#<?= (int)$u['id'] ?></strong></div></div>
      <div class="user-profile-actions">
        <button type="button" class="btn btn-sm user-reset-password-trigger" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" data-user-id="<?= (int)$u['id'] ?>" data-user-name="<?= esc($u['name'], 'attr') ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2Z"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
          Reset password
        </button>
        <?php if (!empty($u['must_change_password'])): ?><span class="small text-warning">Wajib ganti password</span><?php endif ?>
      </div>
    </div>
    
    <div class="user-storage-block">
      <div class="d-flex justify-content-between align-items-center mb-2 user-storage-heading">
        <span class="small text-secondary">Penyimpanan terpakai</span>
        <div class="d-flex align-items-center gap-2">
          <strong class="small"><?= number_format(((int)$u['used_bytes'])/1073741824,2) ?> / <?= number_format(((int)$u['quota_bytes'])/1073741824,1) ?> GB</strong>
          <button class="user-quota-toggle" type="button" data-bs-toggle="modal" data-bs-target="#editQuotaModal" data-user-id="<?= (int)$u['id'] ?>" data-user-name="<?= esc($u['name'], 'attr') ?>" data-user-quota="<?= (int)floor(((int)$u['quota_bytes'])/1073741824) ?>" title="Ubah kuota penyimpanan" aria-label="Ubah kuota penyimpanan pengguna <?= esc($u['name'], 'attr') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
          </button>
        </div>
      </div>
      <div class="progress user-storage-progress"><div class="progress-bar" style="width:<?= min(100,(int)round(((int)$u['used_bytes']/max(1,(int)$u['quota_bytes']))*100)) ?>%"></div></div>
    </div>
    <div class="user-card-controls">



    </div>
  </article>
</div>
<?php endforeach ?>
<?php if (!$users): ?><div class="col-12"><div class="empty-users"><span>♙</span><strong>Belum ada pengguna</strong><small>Buat akun pengguna pertama melalui formulir di atas.</small></div></div><?php endif ?>
</div>
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="editRoleForm" action="/admin/users/update-role/0" onsubmit="return confirm('Simpan perubahan peran pengguna ini?')">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title" id="editRoleModalLabel">Ubah Peran Pengguna</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <label class="form-label" for="editRoleSelect">Peran dan hak akses</label>
          <select class="form-select" id="editRoleSelect" name="role" required>
            <option value="user">User</option>
            <option value="superadmin">Superadmin</option>
          </select>
          <div class="form-text">Pilih tingkat akses untuk pengguna ini.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('editRoleModal');
  const form = document.getElementById('editRoleForm');
  const select = document.getElementById('editRoleSelect');
  const title = document.getElementById('editRoleModalLabel');
  if (!modal || !form || !select || !title) return;
  modal.addEventListener('show.bs.modal', function (event) {
    const trigger = event.relatedTarget;
    if (!trigger) return;
    form.action = '/admin/users/update-role/' + encodeURIComponent(trigger.getAttribute('data-user-id'));
    select.value = trigger.getAttribute('data-user-role') || 'user';
    title.textContent = 'Ubah Peran — ' + (trigger.getAttribute('data-user-name') || 'Pengguna');
  });
});
</script>
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="resetPasswordForm" action="/admin/users/reset-password/0">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <label class="form-label" for="resetPasswordInput">Password baru</label>
          <div class="input-group">
            <input class="form-control" id="resetPasswordInput" type="password" name="new_password" minlength="12" maxlength="200" autocomplete="new-password" placeholder="Minimal 12 karakter" required>
            <button class="btn btn-outline-secondary" type="button" id="toggleResetPassword" aria-label="Tampilkan password" aria-pressed="false">
              <svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="form-text">Password baru minimal 12 karakter. Pengguna akan diminta mengganti password saat masuk berikutnya.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Simpan password</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('resetPasswordModal');
  const form = document.getElementById('resetPasswordForm');
  const input = document.getElementById('resetPasswordInput');
  const title = document.getElementById('resetPasswordModalLabel');
  const toggle = document.getElementById('toggleResetPassword');
  if (!modal || !form || !input || !title) return;
  modal.addEventListener('show.bs.modal', function (event) {
    const trigger = event.relatedTarget;
    if (!trigger) return;
    form.action = '/admin/users/reset-password/' + encodeURIComponent(trigger.getAttribute('data-user-id'));
    input.value = '';
    title.textContent = 'Reset Password — ' + (trigger.getAttribute('data-user-name') || 'Pengguna');
    if (toggle) { input.type = 'password'; toggle.setAttribute('aria-pressed', 'false'); }
  });
  if (toggle) toggle.addEventListener('click', function () {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
    toggle.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
  });
});
</script>
<div class="modal fade" id="editQuotaModal" tabindex="-1" aria-labelledby="editQuotaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="editQuotaForm" action="/admin/users/update/0">
        <?= csrf_field() ?>
        <input type="hidden" name="phone" id="editQuotaPhone" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="editQuotaModalLabel">Ubah Kuota Penyimpanan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <label class="form-label" for="editQuotaInput">Kuota penyimpanan (GB)</label>
          <input class="form-control quota-number-input" id="editQuotaInput" type="text" inputmode="numeric" pattern="[0-9]{1,5}" maxlength="5" name="quota_gb" required>
          <div class="form-text">Masukkan kapasitas dalam GB (1–10.000). Kuota tidak boleh lebih kecil daripada ruang yang sudah digunakan.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('editQuotaModal');
  const form = document.getElementById('editQuotaForm');
  const quota = document.getElementById('editQuotaInput');
  const title = document.getElementById('editQuotaModalLabel');
  if (!modal || !form || !quota || !title) return;
  modal.addEventListener('show.bs.modal', function (event) {
    const trigger = event.relatedTarget;
    if (!trigger) return;
    const userId = trigger.getAttribute('data-user-id');
    form.action = '/admin/users/update/' + encodeURIComponent(userId);
    quota.value = trigger.getAttribute('data-user-quota') || '';
    title.textContent = 'Ubah Kuota — ' + (trigger.getAttribute('data-user-name') || 'Pengguna');
  });
});
</script>
<div class="modal fade" id="editPhoneModal" tabindex="-1" aria-labelledby="editPhoneModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="editPhoneForm" action="/admin/users/update/0">
        <?= csrf_field() ?>
        <input type="hidden" name="quota_gb" id="editPhoneQuota" value="1">
        <div class="modal-header">
          <h5 class="modal-title" id="editPhoneModalLabel">Ubah Nomor WhatsApp</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <label class="form-label" for="editPhoneInput">Nomor WhatsApp pengguna</label>
          <input class="form-control" id="editPhoneInput" type="tel" name="phone" inputmode="numeric" maxlength="20" placeholder="6281234567890" required>
          <div class="form-text">Gunakan format internasional, contoh 6281234567890.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan nomor</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('editPhoneModal');
  const form = document.getElementById('editPhoneForm');
  const phone = document.getElementById('editPhoneInput');
  const quota = document.getElementById('editPhoneQuota');
  const title = document.getElementById('editPhoneModalLabel');
  if (!modal || !form || !phone || !quota || !title) return;
  modal.addEventListener('show.bs.modal', function (event) {
    const trigger = event.relatedTarget;
    if (!trigger) return;
    const userId = trigger.getAttribute('data-user-id');
    form.action = '/admin/users/update/' + encodeURIComponent(userId);
    phone.value = trigger.getAttribute('data-user-phone') || '';
    quota.value = trigger.getAttribute('data-user-quota') || '1';
    title.textContent = 'Ubah Nomor WhatsApp — ' + (trigger.getAttribute('data-user-name') || 'Pengguna');
  });
});
</script>
<style>
.user-profile-panel{border:1px solid var(--bs-border-color);border-radius:10px;padding:12px}
.user-profile-panel .user-profile-summary{margin-top:0}
.user-profile-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:14px;padding-top:12px;border-top:1px solid var(--bs-border-color)}
.user-reset-password-trigger{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--bs-warning);color:var(--bs-warning);border-radius:9px;padding:7px 11px;font-weight:600}
.user-reset-password-trigger:hover{background:rgba(var(--bs-warning-rgb),.12);color:var(--bs-warning);border-color:var(--bs-warning)}
.user-reset-password-trigger svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}

.user-phone-value{display:inline-flex;align-items:center;gap:7px;flex-wrap:wrap}
.user-role-value{display:inline-flex;align-items:center;gap:7px;flex-wrap:wrap}
.user-role-edit-button{display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;padding:4px;border:1px solid var(--bs-border-color);border-radius:7px;background:transparent;color:var(--bs-secondary-color);vertical-align:middle;cursor:pointer}
.user-role-edit-button:hover{color:var(--bs-primary);border-color:var(--bs-primary);background:rgba(var(--bs-primary-rgb),.08)}
.user-role-edit-button svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}

.user-phone-edit-button{display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;padding:4px;border:1px solid var(--bs-border-color);border-radius:7px;background:transparent;color:var(--bs-secondary-color);vertical-align:middle;cursor:pointer}
.user-phone-edit-button:hover{color:var(--bs-primary);border-color:var(--bs-primary);background:rgba(var(--bs-primary-rgb),.08)}
.user-phone-edit-button svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.user-quota-toggle{display:inline-flex;align-items:center;justify-content:center;width:29px;height:29px;padding:5px;border:1px solid var(--bs-border-color);border-radius:8px;background:transparent;color:var(--bs-secondary-color);cursor:pointer}
.user-quota-toggle:hover,.user-quota-toggle[aria-expanded="true"]{color:var(--bs-primary);border-color:var(--bs-primary);background:rgba(var(--bs-primary-rgb),.08)}
.user-quota-toggle svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
#userCardsGrid .user-card-controls{display:block}
#userCardsGrid .user-password-form{width:100%;height:auto}


/* Mobile layout: keep account status, toggle, and delete action in one clear row. */
@media (max-width: 600px) {
  #userCardsGrid .user-management-card {
    padding: 14px;
    min-width: 0;
  }
  #userCardsGrid .user-card-heading {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  #userCardsGrid .user-identity {
    width: 100%;
    min-width: 0;
    align-items: center;
  }
  #userCardsGrid .user-card-status-area {
    width: 100%;
    min-width: 0;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
  }
  #userCardsGrid .user-status-inline {
    width: 100%;
    display: flex;
    flex-direction: row !important;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: nowrap;
    gap: 10px;
  }
  #userCardsGrid .user-status-inline > .status-pill {
    flex: 0 0 auto;
    white-space: nowrap;
  }
  #userCardsGrid .user-status-switch-inline,
  #userCardsGrid .user-delete-inline-form {
    display: inline-flex !important;
    width: auto !important;
    max-width: none;
    flex: 0 0 auto;
    align-items: center;
    margin: 0;
  }
  #userCardsGrid .user-status-switch-inline .account-switch {
    display: inline-flex;
    align-items: center;
    padding: 0;
    min-height: 40px;
  }
  #userCardsGrid .user-status-switch-inline .account-switch-track {
    width: 48px;
    height: 28px;
    padding: 3px;
  }
  #userCardsGrid .user-status-switch-inline .account-switch-thumb {
    width: 22px;
    height: 22px;
  }
  #userCardsGrid .user-status-switch-inline input:checked + .account-switch-track .account-switch-thumb {
    transform: translateX(20px);
  }
  #userCardsGrid .user-delete-icon-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
  }
  #userCardsGrid .user-delete-icon-btn svg {
    width: 18px;
    height: 18px;
  }
  #userCardsGrid .user-profile-summary {
    grid-template-columns: minmax(0, 1fr);
    gap: 14px;
  }
  #userCardsGrid .user-profile-detail {
    min-width: 0;
    overflow-wrap: anywhere;
  }
  #userCardsGrid .user-storage-heading {
    align-items: flex-start !important;
    gap: 8px;
  }
  #userCardsGrid .user-storage-heading > div {
    min-width: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
}

</style>

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