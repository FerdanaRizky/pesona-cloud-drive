<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
 <div><h1 class="h3 mb-1">Profil Pengguna</h1><p class="text-secondary mb-0">Kelola informasi akun dan keamanan Pesona Cloud Drive.</p></div>
 <div class="user-avatar profile-page-avatar"><?= esc(strtoupper(substr((string)($user['name'] ?: $user['email']),0,1))) ?></div>
</div>
<div class="card p-4 mb-4" style="max-width:820px">
 <div class="d-flex align-items-center gap-2 mb-3"><span class="profile-section-icon">♙</span><div><h2 class="h5 mb-0">Informasi akun</h2><small class="text-secondary">Informasi profil yang terhubung dengan akun Anda.</small></div></div>
 <div class="user-profile-summary profile-account-summary">
  <div class="user-profile-detail"><span class="profile-detail-label">Nama lengkap</span><strong><?= esc($user['name']) ?></strong></div>
  <div class="user-profile-detail"><span class="profile-detail-label">Email akun</span><strong class="text-break"><?= esc($user['email']) ?></strong></div>
  <div class="user-profile-detail"><span class="profile-detail-label">Nomor WhatsApp</span><strong><?= !empty($user['phone']) ? esc($user['phone']) : 'Belum ditambahkan' ?></strong></div>
  <div class="user-profile-detail"><span class="profile-detail-label">Status akun</span><strong><?= ($user['status'] ?? '')==='active'?'Aktif':'Ditangguhkan' ?></strong></div>
  <div class="user-profile-detail"><span class="profile-detail-label">Bergabung</span><strong><?= !empty($user['created_at']) ? esc(date('d M Y',strtotime($user['created_at']))) : '—' ?></strong></div>
 </div>
 <form method="post" action="/account/profile" class="mt-3">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label" for="profile_name">Nama lengkap</label><input class="form-control" id="profile_name" name="name" maxlength="100" value="<?= esc(old('name') ?? $user['name']) ?>" required><div class="form-text">Nama dapat diubah kapan saja.</div></div>
  <div class="mb-3"><label class="form-label" for="profile_email">Email akun</label><input class="form-control" id="profile_email" type="email" value="<?= esc($user['email']) ?>" readonly disabled><div class="form-text">Email akun tidak dapat diubah.</div></div>
  <div class="mb-3"><label class="form-label" for="profile_phone">Nomor WhatsApp</label><input class="form-control" id="profile_phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="6281234567890" value="<?= esc(old('phone') ?? ($user['phone'] ?? '')) ?>" maxlength="20" required><div class="form-text">Gunakan format internasional tanpa tanda +, spasi, atau strip. Nomor ini digunakan untuk menerima OTP reset password.</div></div>
  <button class="btn btn-primary" type="submit">Simpan perubahan profil</button>
 </form>
</div>
<div class="card p-4" id="ubah-password" style="max-width:820px">
 <div class="d-flex align-items-center gap-2 mb-3"><span class="profile-section-icon">⚿</span><div><h2 class="h5 mb-0">Ubah Password</h2><small class="text-secondary">Gunakan password kuat minimal 12 karakter.</small></div></div>
 <form method="post" action="/account/password"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label" for="current_password">Password saat ini</label><div class="input-group"><input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password" aria-label="Tampilkan password saat ini" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
  <div class="mb-3"><label class="form-label" for="new_password">Password baru</label><div class="input-group"><input class="form-control" id="new_password" type="password" name="password" minlength="12" autocomplete="new-password" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password" aria-label="Tampilkan password baru" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div><div class="form-text">Minimal 12 karakter.</div></div>
  <div class="mb-4"><label class="form-label" for="password_confirm">Konfirmasi password baru</label><div class="input-group"><input class="form-control" id="password_confirm" type="password" name="password_confirm" minlength="12" autocomplete="new-password" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirm" aria-label="Tampilkan konfirmasi password" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7S15.6 19 12 19 2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
  <button class="btn btn-primary" type="submit">Simpan Password</button>
 </form>
</div>
<?= $this->endSection() ?>