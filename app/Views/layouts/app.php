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
<?php if (session()->get('role') === 'admin'): ?>
<div class="nav-label mt-3 mb-2 px-3 text-uppercase small text-secondary">Administrasi</div>
<a class="nav-link" href="/admin/users">♙ Manajemen Pengguna</a>
<?php endif ?>
<a class="nav-link" href="/account/password">⚙ Ubah Password</a>
</nav>
<div class="sidebar-bottom">
<span class="small text-secondary"><?= esc(session()->get('email')) ?></span>
<span class="small text-secondary"><?= esc(ucfirst((string) session()->get('role'))) ?></span>
<a class="nav-link" href="/logout">Keluar</a>
</div>
</aside>
<main class="main-content">
<header class="topbar">
<button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.body.classList.toggle('sidebar-open')">☰</button>
<span class="text-secondary">Personal cloud workspace</span>
<button class="btn btn-sm btn-outline-secondary ms-auto" id="themeToggle">Ganti tema</button>
</header>
<div class="content-wrap">
<?php if (session()->getFlashdata('message')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('errors')): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?= $this->renderSection('content') ?>
</div>
</main>
</div>
<script src="/assets/app.js"></script>
</body>
</html>
