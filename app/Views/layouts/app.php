<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($title ?? 'Pesona Cloud Drive') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<aside class="sidebar">
<a class="brand" href="/dashboard"><span class="brand-mark">P</span><span>Pesona <small>Cloud Drive</small></span></a>
<nav class="nav flex-column mt-4">
<a class="nav-link" href="/dashboard">⌂ Dashboard</a>
<a class="nav-link" href="/files">▤ File Saya</a>
<a class="nav-link" href="/trash">♲ Sampah</a>
<a class="nav-link" href="/shares">⛓ Link Berbagi</a>
<a class="nav-link" href="/activity">◷ Riwayat Aktivitas</a>
<?php if (session()->get('role') === 'admin'): ?>
<div class="nav-label mt-3 mb-2 px-3 text-uppercase small text-secondary">Administrasi</div>
<a class="nav-link" href="/admin/users">♙ Manajemen Pengguna</a>
<?php endif ?>
<a class="nav-link" href="/account/password">⚙ Ubah Password</a>
</nav>
</aside>
<main class="main-content">
<header class="topbar">
<button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.body.classList.toggle('sidebar-open')">☰</button>
<span class="text-secondary topbar-caption">Personal cloud workspace</span>
<div class="topbar-actions ms-auto">
<button class="btn btn-sm btn-outline-secondary theme-toggle-btn" id="themeToggle" type="button" aria-label="Ganti tema">◐ <span class="d-none d-sm-inline">Tema</span></button>
<div class="user-top-card">
<div class="user-avatar"><?= esc(strtoupper(substr((string)(session()->get('name') ?: session()->get('email') ?: 'U'),0,1))) ?></div>
<div class="user-top-meta"><strong><?= esc(session()->get('name') ?: session()->get('email')) ?></strong><span><?= esc(ucfirst((string)session()->get('role'))) ?> account</span><small class="user-presence"><i></i>Online</small></div>
</div>
<a class="logout-top-btn" href="/logout"><span class="logout-icon">↪</span><span>Keluar</span></a>
</div>
</header>
<div class="content-wrap">
<?php if (session()->getFlashdata('message')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('share_link')): ?><div class="alert alert-info d-flex flex-wrap align-items-center gap-2"><span>Link berbagi:</span><a class="text-break" href="<?= esc(session()->getFlashdata('share_link')) ?>" target="_blank" rel="noopener"><?= esc(session()->getFlashdata('share_link')) ?></a></div><?php endif ?>
<?php if (session()->getFlashdata('errors')): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?= $this->renderSection('content') ?>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/app.js"></script>
</body>
</html>
