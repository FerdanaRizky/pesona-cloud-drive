<!doctype html>
<html lang="id" data-bs-theme="dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= esc($title) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="/assets/app.css?v=20261122-login-layout-dark-v1" rel="stylesheet"></head>
<body class="login-bg login-page">
<main class="login-simple-stage">
<section class="login-simple-card" aria-labelledby="loginTitle">
<div class="login-simple-brand"><span class="brand-mark">P</span><span>Pesona <small>Cloud Drive</small></span></div>
<div class="login-simple-heading"><span class="login-simple-kicker">SELAMAT DATANG</span><h1 id="loginTitle">Masuk ke akun Anda</h1><p>Silakan masuk untuk melanjutkan ke ruang digital Anda.</p></div>
<?php if(session()->getFlashdata('message')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
<?php if(session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
<form method="post" action="/login"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label" for="login_email">Email</label><input class="form-control form-control-lg" id="login_email" type="email" name="email" placeholder="nama@email.com" required autocomplete="username"></div>
<div class="mb-4"><label class="form-label" for="login_password">Password</label><div class="input-group login-password-group"><input class="form-control form-control-lg" id="login_password" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password"><button class="btn toggle-password" type="button" data-target="login_password" aria-label="Tampilkan password" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
<button class="btn btn-primary btn-lg w-100 login-simple-submit" type="submit"><span aria-hidden="true">↪</span> Masuk</button>
</form>
<div class="login-simple-divider"><span>atau</span></div>
<div class="login-simple-links"><a href="/forgot-password">Lupa password</a><a href="/register">Belum punya akun? Daftar</a></div>
<div class="login-simple-footer">© 2026 Pesona Data Media</div>
</section>
</main><script src="/assets/app.js"></script></body></html>
