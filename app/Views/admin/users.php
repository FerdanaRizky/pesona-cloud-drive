<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="users-page-head mb-4">
  <div><div class="page-eyebrow">ADMINISTRASI WORKSPACE</div><h1 class="h3 mb-1">Manajemen Pengguna</h1><p class="text-secondary mb-0">Kelola akun, kapasitas penyimpanan, status akses, dan keamanan pengguna.</p></div>
  <div class="users-head-icon">♙</div>
</div>
<div class="row g-3 mb-4">
  <div class="col-sm-4"><div class="user-metric-card"><span class="metric-icon metric-blue">♙</span><div><div class="muted-label">Total akun</div><div class="metric-value"><?= (int)$stats['total'] ?></div></div></div></div>
  <div class="col-sm-4"><div class="user-metric-card"><span class="metric-icon metric-green">✓</span><div><div class="muted-label">Akun aktif</div><div class="metric-value"><?= (int)$stats['active'] ?></div></div></div></div>
  <div class="col-sm-4"><div class="user-metric-card"><span class="metric-icon metric-purple">▤</span><div><div class="muted-label">Penyimpanan digunakan</div><div class="metric-value"><?= number_format($stats['storage']/1073741824,2) ?> <small>GB</small></div></div></div></div>
</div>
<div class="card user-create-card p-3 p-lg-4 mb-4">
  <div class="d-flex align-items-center gap-3 mb-3"><div class="create-user-icon">＋</div><div><h2 class="h5 mb-1">Tambah pengguna baru</h2><p class="small text-secondary mb-0">Buat akun dan tentukan kuota penyimpanan awal.</p></div></div>
  <form method="post" action="/admin/users/create" class="row g-3">
    <?= csrf_field() ?>
    <div class="col-md-3"><label class="form-label">Nama lengkap</label><input class="form-control" name="name" maxlength="120" value="<?= esc(old('name')) ?>" placeholder="Nama pengguna" required></div>
    <div class="col-md-3"><label class="form-label">Alamat email</label><input class="form-control" type="email" name="email" maxlength="190" value="<?= esc(old('email')) ?>" placeholder="nama@email.com" required></div>
    <div class="col-md-3"><label class="form-label" for="new_user_phone">Nomor WhatsApp</label><input class="form-control" id="new_user_phone" type="tel" name="phone" inputmode="numeric" maxlength="20" value="<?= esc(old('phone')) ?>" placeholder="6281234567890" required><div class="form-text">Format internasional.</div></div>
    <div class="col-md-3"><label class="form-label" for="new_user_password">Password sementara</label><div class="input-group"><input class="form-control" id="new_user_password" type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Minimal 12 karakter" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_user_password" aria-label="Tampilkan password sementara" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
    <div class="col-md-2"><label class="form-label">Kuota (GB)</label><input class="form-control" type="number" name="quota_gb" min="1" max="10000" value="<?= esc(old('quota_gb') ?: '10') ?>" required></div>
    <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100 create-user-submit">Buat</button></div>
  </form>
</div>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h2 class="h5 mb-1">Daftar pengguna</h2><p class="small text-secondary mb-0">Atur akses dan keamanan setiap akun.</p></div><span class="users-count-pill"><?= count($users) ?> akun terdaftar</span></div>
<div class="row g-3">
<?php foreach ($users as $u): ?>
<div class="col-12 col-xl-6">
  <article class="user-management-card h-100">
    <div class="user-card-heading">
      <div class="user-identity">
        <div class="user-card-avatar <?= $u['role']==='admin'?'avatar-admin':'' ?>"><?= esc(strtoupper(substr((string)$u['name'],0,1))) ?></div>
        <div class="min-width-0"><div class="d-flex align-items-center flex-wrap gap-2"><strong class="user-card-name"><?= esc($u['name']) ?></strong><?php if ((int)$u['id']===(int)session()->get('user_id')): ?><span class="badge text-bg-primary">Anda</span><?php endif ?></div><div class="small text-secondary text-break"><?= esc($u['email']) ?></div></div>
      </div>
      <div class="d-flex flex-column align-items-end gap-2"><span class="role-pill <?= $u['role']==='admin'?'role-admin':'role-user' ?>"><?= esc(ucfirst($u['role'])) ?></span><span class="status-pill <?= $u['status']==='active'?'status-active':'status-suspended' ?>"><i></i><?= $u['status']==='active'?'Aktif':'Ditangguhkan' ?></span></div>
    </div>
    <div class="user-profile-panel mb-3"><div class="control-heading mb-2">Profil pengguna</div><div class="user-profile-summary"><div class="user-profile-detail"><span class="profile-detail-label">Nama lengkap</span><strong><?= esc($u['name']) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Email akun</span><strong class="text-break"><?= esc($u['email']) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Nomor WhatsApp</span><strong><?= !empty($u['phone']) ? esc($u['phone']) : 'Belum ditambahkan' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Peran akun</span><strong><?= esc(ucfirst($u['role'])) ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Status akun</span><strong><?= $u['status']==='active'?'Aktif':'Ditangguhkan' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Bergabung</span><strong><?= !empty($u['created_at']) ? esc(date('d M Y', strtotime($u['created_at']))) : '—' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">ID pengguna</span><strong>#<?= (int)$u['id'] ?></strong></div></div></div>
    <div class="user-profile-summary"><div class="user-profile-detail"><span class="profile-detail-label">WhatsApp</span><strong><?= !empty($u['phone']) ? esc($u['phone']) : 'Belum ditambahkan' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">Bergabung</span><strong><?= !empty($u['created_at']) ? esc(date('d M Y', strtotime($u['created_at']))) : '—' ?></strong></div><div class="user-profile-detail"><span class="profile-detail-label">ID pengguna</span><strong>#<?= (int)$u['id'] ?></strong></div></div>
    <div class="user-storage-block">
      <div class="d-flex justify-content-between align-items-center mb-2"><span class="small text-secondary">Penyimpanan terpakai</span><strong class="small"><?= number_format(((int)$u['used_bytes'])/1073741824,2) ?> / <?= number_format(((int)$u['quota_bytes'])/1073741824,1) ?> GB</strong></div>
      <div class="progress user-storage-progress"><div class="progress-bar" style="width:<?= min(100,(int)round(((int)$u['used_bytes']/max(1,(int)$u['quota_bytes']))*100)) ?>%"></div></div>
    </div>
    <div class="user-card-controls">
      <form method="post" action="/admin/users/update/<?= (int)$u['id'] ?>" class="user-setting-form">
        <?= csrf_field() ?><div class="control-heading">Akses & kuota</div>
        <div class="row g-2 align-items-end"><div class="col-12"><label class="form-label small">Nomor WhatsApp profil</label><input class="form-control form-control-sm" type="tel" name="phone" inputmode="numeric" maxlength="20" placeholder="6281234567890" value="<?= esc($u['phone'] ?? '') ?>"><div class="form-text">Format internasional, contoh 6281234567890.</div></div><div class="col-5"><label class="form-label small">Status akun</label><select class="form-select form-select-sm" name="status"><option value="active" <?= $u['status']==='active'?'selected':'' ?>>Aktif</option><option value="suspended" <?= $u['status']==='suspended'?'selected':'' ?>>Ditangguhkan</option></select></div><div class="col-4"><label class="form-label small">Kuota GB</label><input class="form-control form-control-sm" type="number" name="quota_gb" min="1" max="10000" value="<?= (int)floor(((int)$u['quota_bytes'])/1073741824) ?>" required></div><div class="col-3"><button class="btn btn-sm btn-outline-primary w-100">Simpan</button></div></div>
      </form>
      <form method="post" action="/admin/users/reset-password/<?= (int)$u['id'] ?>" class="user-password-form">
        <?= csrf_field() ?><div class="control-heading">Keamanan password</div>
        <div class="input-group input-group-sm"><input class="form-control" id="reset_password_<?= (int)$u['id'] ?>" type="password" name="new_password" minlength="12" autocomplete="new-password" placeholder="Password baru (min. 12 karakter)" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="reset_password_<?= (int)$u['id'] ?>" aria-label="Tampilkan password baru" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
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
<?= $this->endSection() ?>