<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="mb-4"><h1 class="h3">Profil Pengguna</h1><p class="text-secondary">Atur nomor WhatsApp untuk menerima kode OTP pemulihan akun.</p></div>
<div class="card p-4" style="max-width:650px">
<form method="post" action="/account/profile"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label" for="phone">Nomor WhatsApp</label><input class="form-control" id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="6281234567890" value="<?= esc($user['phone'] ?? '') ?>" maxlength="20" required><div class="form-text">Gunakan format internasional tanpa tanda +, spasi, atau strip. Contoh: 6281234567890.</div></div>
<button class="btn btn-primary" type="submit">Simpan nomor WhatsApp</button>
</form></div>
<?= $this->endSection() ?>