<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
  <div>
    <div class="page-eyebrow">ADMINISTRASI</div>
    <h1 class="h3 mb-1">Informasi Sistem</h1>
    <p class="text-secondary mb-0">Ringkasan spesifikasi server dan lingkungan aplikasi yang sedang berjalan.</p>
  </div>
  <span class="badge text-bg-success px-3 py-2">Informasi server</span>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-md-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="text-secondary small mb-1">Sistem Operasi</div><div class="fw-semibold"><?= esc($server['os']) ?></div><div class="small text-secondary mt-1"><?= esc($server['architecture']) ?> · Kernel <?= esc($server['kernel']) ?></div></div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="text-secondary small mb-1">CPU</div><div class="fw-semibold"><?= esc($server['cpu']) ?></div><div class="small text-secondary mt-1">Hostname: <?= esc($server['hostname']) ?></div></div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="text-secondary small mb-1">Memori RAM</div><div class="fw-semibold"><?= esc($server['memory']['used']) ?> / <?= esc($server['memory']['total']) ?></div><?php if ($server['memory']['used_percent'] !== null): ?><div class="progress mt-2" role="progressbar" aria-label="Penggunaan RAM" aria-valuenow="<?= (float) $server['memory']['used_percent'] ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?= (float) $server['memory']['used_percent'] ?>%"></div></div><div class="small text-secondary mt-1"><?= (float) $server['memory']['used_percent'] ?>% digunakan</div><?php endif ?></div></div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="text-secondary small mb-1">Penyimpanan Disk</div><div class="fw-semibold"><?= esc($server['disk_free']) ?> tersedia</div><div class="small text-secondary mt-1">Total <?= esc($server['disk_total']) ?><?php if ($server['disk_used_percent'] !== null): ?> · <?= (float) $server['disk_used_percent'] ?>% terpakai<?php endif ?></div></div></div></div>
</div>

<div class="row g-4">
  <div class="col-12 col-xl-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0">Server & Webserver</h2></div>
      <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><tbody>
        <tr><th class="ps-3 fw-medium text-secondary">Hostname</th><td class="text-end pe-3"><?= esc($server['hostname']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Sistem operasi</th><td class="text-end pe-3"><?= esc($server['os']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Kernel</th><td class="text-end pe-3"><?= esc($server['kernel']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Arsitektur</th><td class="text-end pe-3"><?= esc($server['architecture']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">CPU</th><td class="text-end pe-3"><?= esc($server['cpu']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Uptime</th><td class="text-end pe-3"><?= esc($server['uptime']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Webserver (Nginx/Apache)</th><td class="text-end pe-3"><?= esc($runtime['webserver']) ?></td></tr>
      </tbody></table></div></div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0">Panel & Runtime Aplikasi</h2></div>
      <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><tbody>
        <tr><th class="ps-3 fw-medium text-secondary">aaPanel</th><td class="text-end pe-3"><?= $panel['installed'] ? 'Terpasang' : 'Tidak terdeteksi' ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Versi aaPanel</th><td class="text-end pe-3"><?= esc($panel['version']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">PHP</th><td class="text-end pe-3"><?= esc($runtime['php']) ?> (<?= esc($runtime['php_sapi']) ?>)</td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">CodeIgniter</th><td class="text-end pe-3"><?= esc($runtime['codeigniter']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Database MySQL/MariaDB</th><td class="text-end pe-3"><?= esc($runtime['database']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">OpenSSL</th><td class="text-end pe-3"><?= esc($runtime['openssl']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Docker</th><td class="text-end pe-3"><?= esc($runtime['docker']) ?></td></tr>
      </tbody></table></div></div>
    </div>
  </div>
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0">Informasi Aplikasi & Waktu</h2></div>
      <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><tbody>
        <tr><th class="ps-3 fw-medium text-secondary">Versi aplikasi</th><td class="text-end pe-3">v1.0.0</td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Environment</th><td class="text-end pe-3"><?= esc($runtime['environment']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Waktu server</th><td class="text-end pe-3"><?= esc($runtime['server_time']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Zona waktu</th><td class="text-end pe-3"><?= esc($runtime['timezone']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Penyimpanan disk</th><td class="text-end pe-3"><?= esc($server['disk_free']) ?> tersedia dari <?= esc($server['disk_total']) ?></td></tr>
      </tbody></table></div></div>
    </div>
  </div>
</div>
<p class="small text-secondary mt-3 mb-0">Data dibaca secara langsung dari lingkungan PHP dan sistem operasi. Informasi versi layanan hanya ditampilkan jika dapat diakses oleh proses PHP, tanpa mengubah konfigurasi server.</p>
<?= $this->endSection() ?>
