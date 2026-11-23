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

<div class="row g-4">
  <div class="col-12 col-xl-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0">Server & Webserver</h2></div>
      <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><tbody>
        <tr><th class="ps-3 fw-medium text-secondary">Memori</th><td class="text-end pe-3"><?= esc($server['memory']['used']) ?> / <?= esc($server['memory']['total']) ?><?php if ($server['memory']['used_percent'] !== null): ?> <span class="text-secondary small">(<?= (float) $server['memory']['used_percent'] ?>% digunakan)</span><?php endif ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Sistem operasi</th><td class="text-end pe-3"><?= esc($server['os']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Arsitektur</th><td class="text-end pe-3"><?= esc($server['architecture']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Model CPU</th><td class="text-end pe-3"><?= esc($server['cpu_model']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Jumlah core</th><td class="text-end pe-3"><?= esc($server['cpu']) ?></td></tr>
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
        <tr><th class="ps-3 fw-medium text-secondary">PHP</th><td class="text-end pe-3"><?= esc($runtime['php']) ?> (<?= esc($runtime['php_sapi']) ?>)</td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">CodeIgniter</th><td class="text-end pe-3"><?= esc($runtime['codeigniter']) ?></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Database MySQL/MariaDB</th><td class="text-end pe-3"><span><?= esc($runtime['database']) ?></span><span class="badge rounded-pill ms-2 <?= !empty($runtime['database_connected']) ? 'text-bg-success' : 'text-bg-danger' ?>"><?= !empty($runtime['database_connected']) ? 'Terhubung' : 'Tidak terhubung' ?></span></td></tr>
        <tr><th class="ps-3 fw-medium text-secondary">Docker</th><td class="text-end pe-3"><?= esc($runtime['docker']) ?></td></tr>
      </tbody></table></div></div>
    </div>
  </div>
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0">Informasi Aplikasi & Waktu</h2></div>
      <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><tbody>
        <tr><th class="ps-3 fw-medium text-secondary">Versi aplikasi</th><td class="text-end pe-3"><?= esc($app_version) ?></td></tr>
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
