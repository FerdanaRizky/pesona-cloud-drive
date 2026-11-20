<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div><h1 class="h3 mb-1">Manajemen Pengguna</h1><p class="text-secondary mb-0">Buat akun, atur kuota, nonaktifkan akun, dan reset password.</p></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-4"><div class="admin-stat"><div class="muted-label">Total akun</div><div class="fs-3 fw-semibold"><?= (int) $stats['total'] ?></div></div></div>
  <div class="col-sm-4"><div class="admin-stat"><div class="muted-label">Akun aktif</div><div class="fs-3 fw-semibold"><?= (int) $stats['active'] ?></div></div></div>
  <div class="col-sm-4"><div class="admin-stat"><div class="muted-label">Penyimpanan digunakan</div><div class="fs-3 fw-semibold"><?= number_format($stats['storage'] / 1073741824, 2) ?> GB</div></div></div>
</div>

<div class="card p-4 mb-4">
  <h2 class="h5 mb-3">Tambah pengguna</h2>
  <form method="post" action="/admin/users/create" class="row g-3">
    <?= csrf_field() ?>
    <div class="col-md-3"><label class="form-label">Nama</label><input class="form-control" name="name" maxlength="120" value="<?= esc(old('name')) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="190" value="<?= esc(old('email')) ?>" required></div>
    <div class="col-md-3">
      <label class="form-label" for="new_user_password">Password sementara</label>
      <div class="input-group"><input class="form-control" id="new_user_password" type="password" name="password" minlength="12" autocomplete="new-password" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_user_password" aria-label="Tampilkan password sementara" aria-pressed="false">Intip</button></div>
      <div class="form-text">Minimal 12 karakter.</div>
    </div>
    <div class="col-md-2"><label class="form-label">Kuota (GB)</label><input class="form-control" type="number" name="quota_gb" min="1" max="10000" value="<?= esc(old('quota_gb') ?: '10') ?>" required></div>
    <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100">Buat</button></div>
  </form>
</div>

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Daftar akun</h2><span class="small text-secondary"><?= count($users) ?> akun</span></div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Pengguna</th><th>Peran</th><th>Penyimpanan</th><th>Status & kuota</th><th>Password</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><div class="fw-semibold"><?= esc($u['name']) ?></div><small class="text-secondary"><?= esc($u['email']) ?></small><?php if ((int) $u['id'] === (int) session()->get('user_id')): ?> <span class="badge text-bg-primary">Anda</span><?php endif ?></td>
          <td><span class="badge <?= $u['role'] === 'admin' ? 'text-bg-primary' : 'text-bg-secondary' ?>"><?= esc(ucfirst($u['role'])) ?></span></td>
          <td><div><?= number_format(((int) $u['used_bytes']) / 1073741824, 2) ?> / <?= number_format(((int) $u['quota_bytes']) / 1073741824, 1) ?> GB</div><div class="progress mt-1" style="max-width:180px"><div class="progress-bar" style="width:<?= min(100, (int) round(((int) $u['used_bytes'] / max(1, (int) $u['quota_bytes'])) * 100)) ?>%"></div></div></td>
          <td>
            <form method="post" action="/admin/users/update/<?= (int) $u['id'] ?>" class="d-flex flex-wrap gap-2 align-items-center">
              <?= csrf_field() ?>
              <select class="form-select form-select-sm" name="status" style="width:auto"><option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Aktif</option><option value="suspended" <?= $u['status'] === 'suspended' ? 'selected' : '' ?>>Ditangguhkan</option></select>
              <input class="form-control form-control-sm" type="number" name="quota_gb" min="1" max="10000" value="<?= (int) floor(((int) $u['quota_bytes']) / 1073741824) ?>" style="width:92px" required>
              <button class="btn btn-sm btn-outline-primary">Simpan</button>
            </form>
          </td>
          <td>
            <form method="post" action="/admin/users/reset-password/<?= (int) $u['id'] ?>" class="d-flex flex-column gap-1">
              <?= csrf_field() ?>
              <div class="input-group input-group-sm"><input class="form-control" id="reset_password_<?= (int) $u['id'] ?>" type="password" name="new_password" minlength="12" autocomplete="new-password" placeholder="Password baru" required><button class="btn btn-outline-secondary toggle-password" type="button" data-target="reset_password_<?= (int) $u['id'] ?>" aria-label="Tampilkan password baru" aria-pressed="false">Intip</button></div>
              <button class="btn btn-sm btn-outline-warning">Reset password</button>
            </form>
            <?php if (!empty($u['must_change_password'])): ?><small class="text-warning">Wajib ganti password</small><?php endif ?>
          </td>
        </tr>
      <?php endforeach ?>
      <?php if (!$users): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada pengguna.</td></tr><?php endif ?>
      </tbody>
    </table>
  </div>
</div>
<?= $this->endSection() ?>
