<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="mb-4"><h1 class="h3 mb-1">Riwayat Aktivitas</h1><p class="text-secondary mb-0">Aktivitas terbaru di Pesona Cloud Drive (maksimal 250 catatan).</p></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Waktu</th><?php if (session()->get('role') === 'admin'): ?><th>Pengguna</th><?php endif ?><th>Aktivitas</th><th>Detail</th><th>IP</th></tr></thead><tbody>
<?php foreach ($logs as $log): ?><tr><td class="text-nowrap"><?= esc($log['created_at']) ?></td><?php if (session()->get('role') === 'admin'): ?><td><?= esc($log['user_name'] ?? 'Pengguna dihapus') ?><br><small class="text-secondary"><?= esc($log['user_email'] ?? '') ?></small></td><?php endif ?><td><span class="badge text-bg-secondary"><?= esc($log['action']) ?></span></td><td><?= esc($log['details'] ?? '') ?></td><td><?= esc($log['ip_address'] ?? '—') ?></td></tr><?php endforeach ?>
<?php if (!$logs): ?><tr><td colspan="<?= session()->get('role') === 'admin' ? 5 : 4 ?>" class="text-center text-secondary">Belum ada aktivitas tercatat.</td></tr><?php endif ?></tbody></table></div></div>
<?= $this->endSection() ?>
