<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<title><?= esc($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/app.css" rel="stylesheet">
<style>
body.setup-page{min-height:100vh;min-height:100dvh;margin:0;overflow-x:hidden;background:radial-gradient(ellipse at 50% 0%,#1b3763 0%,#101d32 42%,#0b1220 78%);color:#e7edf7}
.setup-stage{box-sizing:border-box;min-height:100vh;min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:36px 20px}
.setup-card{width:min(100%,510px);padding:clamp(22px,4vw,38px);background:rgba(17,29,48,.96);border:1px solid #2a3b55;border-radius:24px;box-shadow:0 24px 70px rgba(0,0,0,.3);min-width:0}
.setup-brand{width:54px;height:54px;border-radius:17px;background:linear-gradient(135deg,#4388ff,#7658e8);display:grid;place-items:center;color:#fff;font-size:25px;font-weight:850;box-shadow:0 9px 24px rgba(67,136,255,.25)}
.setup-kicker{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border-radius:99px;background:rgba(67,136,255,.12);border:1px solid rgba(112,159,241,.22);color:#9fc2ff;font-size:.72rem;font-weight:750;letter-spacing:.06em}
.setup-heading h1{font-size:clamp(1.45rem,4vw,1.85rem);font-weight:800;letter-spacing:-.035em;margin:14px 0 7px}
.setup-heading p{color:#9aabc3;font-size:.9rem;line-height:1.55;margin:0}
.setup-form{margin-top:26px}
.setup-form .form-label{font-size:.83rem;font-weight:650;color:#d4deed;margin-bottom:7px}
.setup-form .form-control{width:100%;min-width:0;min-height:48px;padding:11px 13px;border-radius:11px;background:#0b1525;border:1px solid #34445e;color:#fff;font-size:16px}
.setup-form .form-control:focus{border-color:#6098f5;box-shadow:0 0 0 .2rem rgba(67,136,255,.16)}
.setup-form .input-group{flex-wrap:nowrap;min-width:0}
.setup-form .input-group .form-control{border-radius:11px 0 0 11px}
.setup-form .toggle-password{min-width:66px;border-radius:0 11px 11px 0;border-color:#34445e;color:#b9c9df;background:#17263c}
.setup-form .form-text{font-size:.75rem;line-height:1.45;color:#91a2bb;margin-top:7px}
.setup-submit{min-height:52px;border:0;border-radius:12px;font-weight:750;background:linear-gradient(110deg,#347bdc,#6659d7);box-shadow:0 8px 22px rgba(55,105,197,.22);touch-action:manipulation}
.setup-submit:hover{filter:brightness(1.08)}
.setup-footer{margin-top:22px;padding-top:16px;border-top:1px solid #26364f;text-align:center;color:#7186a5;font-size:.72rem;line-height:1.5}
.setup-form .alert{border-radius:12px;overflow-wrap:anywhere}
@media(max-width:575px){
 body.setup-page{background:radial-gradient(ellipse at 50% 0%,#1b3763 0%,#0b1220 72%)}
 .setup-stage{align-items:flex-start;padding:calc(14px + env(safe-area-inset-top,0px)) 12px calc(14px + env(safe-area-inset-bottom,0px))}
 .setup-card{padding:24px 19px 20px;border-radius:19px}
 .setup-brand{width:48px;height:48px;border-radius:15px;font-size:22px}
 .setup-form{margin-top:22px}
 .setup-form .mb-3{margin-bottom:17px!important}
 .setup-form .mb-4{margin-bottom:19px!important}
 .setup-submit{min-height:50px}
}
@media(max-width:360px){.setup-stage{padding-left:9px;padding-right:9px}.setup-card{padding:21px 15px 18px}}
@media(max-height:720px) and (max-width:575px){.setup-stage{align-items:flex-start}.setup-card{margin:0 auto}}
@media(prefers-reduced-motion:reduce){.setup-page *{transition:none!important}}
</style>
</head>
<body class="setup-page">
<main class="setup-stage">
<section class="setup-card">
<header class="setup-heading text-center">
<div class="setup-brand mx-auto mb-3" aria-hidden="true">P</div>
<span class="setup-kicker">PESONA CLOUD DRIVE <span aria-hidden="true">•</span> SETUP AWAL</span>
<h1>Siapkan Administrator</h1>
<p>Buat akun administrator pertama untuk mulai mengelola file dan pengguna Pesona Cloud Drive.</p>
</header>

<?php if(session()->getFlashdata('error')): ?>
<div class="alert alert-danger mt-4 mb-0" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>
<?php if(isset($errors)): ?>
<div class="alert alert-danger mt-4 mb-0" role="alert"><ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div>
<?php endif ?>

<form class="setup-form" method="post" action="/setup">
<?= csrf_field() ?>
<div class="mb-3">
<label class="form-label" for="setup_name">Nama administrator</label>
<input class="form-control" id="setup_name" name="name" required maxlength="120" autocomplete="name" placeholder="Nama lengkap" value="<?= old('name') ?>">
</div>
<div class="mb-3">
<label class="form-label" for="setup_email">Email</label>
<input class="form-control" id="setup_email" type="email" name="email" required autocomplete="email" inputmode="email" placeholder="admin@domain.com" value="<?= old('email') ?>">
</div>
<div class="mb-3">
<label class="form-label" for="setup_phone">Nomor WhatsApp</label>
<input class="form-control" id="setup_phone" type="tel" name="phone" required minlength="9" maxlength="20" inputmode="tel" autocomplete="tel" placeholder="6281234567890" value="<?= old('phone') ?>">
<div class="form-text">Gunakan format internasional tanpa tanda +, contoh 6281234567890. Nomor harus unik.</div>
</div>
<div class="mb-3">
<label class="form-label" for="setup_password">Password <span class="text-secondary fw-normal">(minimal 12 karakter)</span></label>
<div class="input-group">
<input class="form-control" id="setup_password" type="password" name="password" required minlength="12" autocomplete="new-password" placeholder="Buat password yang kuat">
<button class="btn btn-outline-secondary toggle-password" type="button" data-target="setup_password" aria-label="Tampilkan password" aria-pressed="false">Intip</button>
</div>
</div>
<div class="mb-4">
<label class="form-label" for="setup_password_confirm">Ulangi password</label>
<div class="input-group">
<input class="form-control" id="setup_password_confirm" type="password" name="password_confirm" required minlength="12" autocomplete="new-password" placeholder="Masukkan ulang password">
<button class="btn btn-outline-secondary toggle-password" type="button" data-target="setup_password_confirm" aria-label="Tampilkan konfirmasi password" aria-pressed="false">Intip</button>
</div>
</div>
<button class="btn btn-primary setup-submit w-100" type="submit">Buat Administrator <span aria-hidden="true">→</span></button>
</form>
<footer class="setup-footer">Pengaturan awal Pesona Cloud Drive<br>Pastikan data administrator tersimpan dengan aman.</footer>
</section>
</main>
<script src="/assets/app.js"></script>
</body>
</html>
