<!doctype html>
<html lang="id" data-bs-theme="dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= esc($title) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="/assets/app.css?v=20261121-version-copyright-v2" rel="stylesheet"></head>
<body class="login-bg login-page">
<main class="login-stage">
<section class="login-showcase">
<div class="login-brand"><span class="brand-mark">P</span><span>Pesona <small>Cloud Drive</small></span></div>
<div class="login-showcase-copy"><span class="login-kicker">RUANG DIGITAL ANDA</span><h1>Simpan. Atur.<br><span>Berbagi dengan mudah.</span></h1><p>Semua file penting Anda tersimpan rapi dalam satu ruang penyimpanan pribadi yang dapat diakses kapan saja.</p><div class="login-feature-list"><div><span>✓</span> Kelola file dan folder dengan mudah</div><div><span>✓</span> Akses dari desktop maupun perangkat mobile</div><div><span>✓</span> Bagikan file melalui tautan</div></div></div>
<div class="login-showcase-footer-wrap">
<div class="login-showcase-footer">PESONA CLOUD DRIVE <span>•</span> PERSONAL WORKSPACE</div>
<div class="login-version">Versi <strong>1.0.0</strong></div>
<div class="login-copyright">© 2026 Pesona Data Media. Hak cipta dilindungi.</div>
</div>
</section>
<section class="login-panel-wrap"><div class="login-card">
<div class="login-panel-heading"><div class="login-mobile-mark brand-mark">P</div><span class="login-kicker">SELAMAT DATANG KEMBALI</span><h2>Masuk ke akun Anda</h2><p>Masukkan email dan password untuk melanjutkan.</p></div>
<?php if(session()->getFlashdata('message')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
<?php if(session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
<form method="post" action="/login"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label" for="login_email">Alamat email</label><div class="login-input-wrap"><span class="login-input-icon">✉</span><input class="form-control form-control-lg" id="login_email" type="email" name="email" placeholder="nama@email.com" required autocomplete="username"></div></div>
<div class="mb-4"><label class="form-label" for="login_password">Password</label><div class="input-group login-password-group"><input class="form-control form-control-lg" id="login_password" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password"><button class="btn toggle-password" type="button" data-target="login_password" aria-label="Tampilkan password" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
<button class="btn btn-primary btn-lg w-100 login-submit" type="submit"><span>Masuk</span><span aria-hidden="true">→</span></button>
<div class="text-end mt-3"><a href="/forgot-password" class="small text-decoration-none">Lupa password?</a></div>
</form><div class="login-security-note"><span>◈</span> Akses pribadi dan aman untuk akun Anda</div>
</div></section>
</main><script src="/assets/app.js"></script></body></html>
