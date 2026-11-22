<!doctype html>
<html lang="id" data-bs-theme="dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= esc($title) ?></title><script>(function(){try{var t=localStorage.getItem("pcd-theme");if(t==="light"||t==="dark")document.documentElement.setAttribute("data-bs-theme",t);}catch(e){}})();</script><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="/assets/app.css?v=20261122-brandfix2" rel="stylesheet"><script>(function(){window.pcdToggleTheme=function(event){if(event){event.preventDefault();event.stopPropagation();}var root=document.documentElement;var next=root.getAttribute('data-bs-theme')==='dark'?'light':'dark';root.setAttribute('data-bs-theme',next);try{localStorage.setItem('pcd-theme',next);}catch(e){}var icon=document.getElementById('themeToggleIcon');var button=document.getElementById('themeToggle');if(icon)icon.textContent=next==='light'?'☀':'☾';if(button){button.setAttribute('aria-label','Tema '+(next==='light'?'terang':'gelap')+'. Klik untuk mengganti tema.');button.setAttribute('title','Beralih ke tema '+(next==='light'?'gelap':'terang'));}return false;};})();</script></head>
<body class="login-bg login-page">
<button type="button" class="auth-theme-toggle" id="themeToggle" aria-label="Ganti tema" onclick="return window.pcdToggleTheme(event)"><span id="themeToggleIcon" aria-hidden="true">☾</span></button>
<main class="login-stage">
<section class="login-showcase">
<div class="login-brand"><img class="brand-logo" src="/assets/pesona-cloud-logo.png?v=20261122-brandfix2" alt="Pesona Cloud"><span>Pesona <small>Cloud Drive</small></span></div>
<div class="login-showcase-copy"><span class="login-kicker">RUANG DIGITAL ANDA</span><h1>Simpan. Atur.<br><span>Berbagi dengan mudah.</span></h1><p>Semua file penting Anda tersimpan rapi dalam satu ruang penyimpanan pribadi yang dapat diakses kapan saja.</p><div class="login-feature-list"><div><span>✓</span> Kelola file dan folder dengan mudah</div><div><span>✓</span> Akses dari desktop maupun perangkat mobile</div><div><span>✓</span> Bagikan file melalui tautan</div></div></div>
<div class="login-showcase-footer-wrap"><div class="login-showcase-footer">PESONA CLOUD DRIVE <span>•</span> PERSONAL WORKSPACE</div><div class="login-version">Versi <strong>1.0.0</strong></div><div class="login-copyright">© 2026 Pesona Data Media. Hak cipta dilindungi.</div></div>
</section>
<section class="login-panel-wrap"><div class="login-card">
<div class="login-panel-heading"><img class="login-mobile-mark brand-logo" src="/assets/pesona-cloud-logo.png?v=20261122-brandfix2" alt="Pesona Cloud"><span class="login-kicker">SELAMAT DATANG KEMBALI</span><h2>Masuk ke akun Anda</h2><p>Masukkan email dan password untuk melanjutkan.</p></div>
<?php if(session()->getFlashdata('message')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
<?php if(session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
<form method="post" action="/login"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label" for="login_email">Email</label><input class="form-control form-control-lg" id="login_email" type="email" name="email" placeholder="nama@email.com" required autocomplete="username"></div>
<div class="mb-4"><label class="form-label" for="login_password">Password</label><div class="input-group login-password-group"><input class="form-control form-control-lg" id="login_password" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password"><button class="btn toggle-password" type="button" data-target="login_password" aria-label="Tampilkan password" aria-pressed="false"><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
<button class="btn btn-primary btn-lg w-100 login-submit" type="submit"><span aria-hidden="true">↪</span> Masuk</button>
</form>
<div class="login-simple-divider"><span>atau</span></div>
<div class="login-simple-links"><a href="/forgot-password">Lupa password</a><a href="/register">Belum punya akun? Daftar</a></div>
<div class="login-security-note"><span>◈</span> Akses pribadi dan aman untuk akun Anda</div>
</div></section>
</main><script src="/assets/app.js?v=20261122-theme-click-fix-v3" defer></script></body></html>
