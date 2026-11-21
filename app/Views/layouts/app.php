<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($title ?? 'Pesona Cloud Drive') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/app.css?v=20261121-responsive-ui-v2" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<?php $currentPath = trim(service('uri')->getPath(), '/'); ?>
<aside class="sidebar" id="appSidebar">
<a class="brand" href="/dashboard"><span class="brand-mark">P</span><span>Pesona <small>Cloud Drive</small></span></a>
<nav class="nav flex-column mt-4">
<a class="nav-link <?= $currentPath === 'dashboard' ? 'active' : '' ?>" href="/dashboard">⌂ <span>Dashboard</span></a>
<a class="nav-link <?= in_array($currentPath, ['files', 'files/search'], true) ? 'active' : '' ?>" href="/files">▤ <span>File Saya</span></a>
<a class="nav-link <?= str_starts_with($currentPath, 'trash') ? 'active' : '' ?>" href="/trash">♲ <span>Sampah</span></a>
<a class="nav-link <?= str_starts_with($currentPath, 'shares') ? 'active' : '' ?>" href="/shares">⛓ <span>Link Berbagi</span></a>
<a class="nav-link <?= str_starts_with($currentPath, 'activity') ? 'active' : '' ?>" href="/activity">◷ <span>Riwayat Aktivitas</span></a>
<?php if (session()->get('role') === 'admin'): ?>
<div class="nav-label mt-3 mb-2 px-3 text-uppercase small text-secondary">Administrasi</div>
<a class="nav-link <?= str_starts_with($currentPath, 'admin/users') ? 'active' : '' ?>" href="/admin/users">♙ <span>Manajemen Pengguna</span></a>
<?php endif ?>
</nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<main class="main-content">
<header class="topbar">
<button class="btn btn-sm btn-outline-secondary d-lg-none sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka navigasi" aria-expanded="false" aria-controls="appSidebar">☰</button>
<span class="text-secondary topbar-caption">Personal cloud workspace</span>
<div class="topbar-actions ms-auto">
<button class="btn btn-sm btn-outline-secondary theme-toggle-btn" id="themeToggle" type="button" aria-label="Ganti tema" title="Klik untuk mengganti tema"><span class="theme-toggle-icon" aria-hidden="true">◐</span><span class="theme-toggle-caption">Tema: <strong id="themeModeLabel">Dark</strong></span></button>
<div class="dropdown account-dropdown">
<button class="user-top-card account-trigger" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Buka menu akun">
<div class="user-avatar"><?= esc(strtoupper(substr((string)(session()->get('name') ?: session()->get('email') ?: 'U'),0,1))) ?></div>
<div class="user-top-meta"><strong><?= esc(session()->get('name') ?: session()->get('email')) ?></strong><span><?= esc(ucfirst((string)session()->get('role'))) ?> account</span><small class="user-presence"><i></i>Online</small></div><span class="account-chevron">⌄</span>
</button>
<div class="dropdown-menu dropdown-menu-end account-menu">
<div class="account-menu-heading"><strong><?= esc(session()->get('name') ?: session()->get('email')) ?></strong><small><?= esc(ucfirst((string)session()->get('role'))) ?> · <span class="user-presence"><i></i>Online</span></small></div>
<div class="dropdown-divider"></div>
<a class="dropdown-item" href="/account/profile"><span class="logout-icon">♙</span><span>Profil Pengguna</span></a>

<div class="dropdown-divider"></div>
<a class="dropdown-item account-logout-item" href="/logout"><span class="logout-icon">↪</span><span>Keluar dari akun</span></a>
</div>
</div>
</div>
</header>
<div class="content-wrap">
<?php if (session()->getFlashdata('message') && service('uri')->getPath() !== 'shares'): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('share_link') && service('uri')->getPath() !== 'shares'): ?><div class="alert alert-info d-flex flex-wrap align-items-center gap-2"><span>Link berbagi:</span><a class="text-break" href="<?= esc(session()->getFlashdata('share_link')) ?>" target="_blank" rel="noopener"><?= esc(session()->getFlashdata('share_link')) ?></a></div><?php endif ?>
<?php if ($bulkShareLinks = session()->getFlashdata('share_links')): ?><div class="alert alert-info"><strong>Link berbagi berhasil dibuat</strong><ul class="mb-0 mt-2"><?php foreach ($bulkShareLinks as $shareItem): ?><li class="mb-1"><span><?= esc($shareItem['name']) ?>:</span> <a class="text-break" href="<?= esc($shareItem['url']) ?>" target="_blank" rel="noopener"><?= esc($shareItem['url']) ?></a></li><?php endforeach ?></ul></div><?php endif ?>
<?php if (session()->getFlashdata('errors')): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?= $this->renderSection('content') ?>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/app.js?v=20261121-responsive-ui-v2"></script>
</body>
</html>
