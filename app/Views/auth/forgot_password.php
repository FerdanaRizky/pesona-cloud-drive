<!doctype html>
<html lang="id" data-bs-theme="dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= esc($title) ?></title><script>(function(){try{var t=localStorage.getItem("pcd-theme");if(t==="light"||t==="dark")document.documentElement.setAttribute("data-bs-theme",t);}catch(e){}})();</script><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="/assets/app.css?v=20261122-auth-theme-responsive-v2" rel="stylesheet"></head>
<button type="button" class="auth-theme-toggle" id="themeToggle" aria-label="Ganti tema"><span id="themeToggleIcon" aria-hidden="true">☾</span></button><body class="login-bg login-page forgot-password-page">
<main class="login-stage forgot-password-backdrop" aria-hidden="true">
<section class="login-showcase"><div class="login-brand"><span class="brand-mark">P</span><span>Pesona <small>Cloud Drive</small></span></div><div class="login-showcase-copy"><span class="login-kicker">PEMULIHAN AKUN</span><h1>Akses kembali<br><span>ke ruang digital Anda.</span></h1><p>Kode verifikasi dikirim ke nomor WhatsApp yang tersimpan pada profil akun.</p></div><div class="login-showcase-footer">PESONA CLOUD DRIVE <span>•</span> PERSONAL WORKSPACE</div></section>
<section class="login-panel-wrap"><div class="login-card"><div class="login-panel-heading"><span class="login-kicker">BANTUAN AKUN</span><h2>Lupa password?</h2><p>Masukkan email akun untuk menerima kode OTP WhatsApp.</p></div><div class="forgot-backdrop-placeholder"><div></div><div></div><div></div></div></div></section>
</main>
<div class="forgot-modal-overlay">
<section class="forgot-modal-card" role="dialog" aria-modal="true" aria-labelledby="forgotModalTitle">
<div class="forgot-modal-heading"><span class="login-kicker">BANTUAN AKUN</span><h2 id="forgotModalTitle">Lupa password</h2><p>Masukkan email akun Anda. Kami akan mengirimkan kode OTP WhatsApp untuk membuat password baru.</p></div>
<?php if(session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
<?php if(session()->getFlashdata('message')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
<form method="post" action="/forgot-password"><?= csrf_field() ?>
<div class="mb-4"><label class="form-label" for="reset_email">Alamat email</label><input class="form-control form-control-lg" id="reset_email" type="email" name="email" placeholder="nama@email.com" required maxlength="190" autocomplete="email" value="<?= esc(old('email') ?? '') ?>"></div>
<div class="forgot-modal-actions"><a href="/login" class="btn forgot-cancel-btn">Batal</a><button class="btn btn-primary login-submit forgot-send-btn" type="submit">Kirim kode OTP</button></div>
</form>
</section>
</div>
<script src="/assets/app.js?v=20261122-auth-theme-responsive-v1" defer></script><script src="/assets/app.js?v=20261122-auth-theme-responsive-v2" defer></script></body></html>