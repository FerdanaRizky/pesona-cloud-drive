<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="mb-4"><h1 class="h3">Ubah Password</h1><p class="text-secondary">Gunakan password kuat minimal 12 karakter.</p></div>
<?php if (isset($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div><?php endif ?>
<div class="card p-4" style="max-width:620px">
  <form method="post" action="/account/password">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="current_password">Password saat ini</label>
      <div class="input-group">
        <input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password" aria-label="Tampilkan password saat ini" aria-pressed="false">Intip</button>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="new_password">Password baru</label>
      <div class="input-group">
        <input class="form-control" id="new_password" type="password" name="password" minlength="12" autocomplete="new-password" required>
        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password" aria-label="Tampilkan password baru" aria-pressed="false">Intip</button>
      </div>
      <div class="form-text">Minimal 12 karakter.</div>
    </div>
    <div class="mb-4">
      <label class="form-label" for="password_confirm">Konfirmasi password baru</label>
      <div class="input-group">
        <input class="form-control" id="password_confirm" type="password" name="password_confirm" minlength="12" autocomplete="new-password" required>
        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirm" aria-label="Tampilkan konfirmasi password" aria-pressed="false">Intip</button>
      </div>
    </div>
    <button class="btn btn-primary">Simpan Password</button>
  </form>
</div>
<?= $this->endSection() ?>
