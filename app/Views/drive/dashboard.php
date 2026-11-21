<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$formatActivityDate = static function ($value) {
    $time = $value ? strtotime((string) $value) : false;
    return $time ? date('j M', $time) : 'Baru saja';
};
?>
<div class="dashboard-drive" id="dashboardDriveRoot" data-dashboard-view="grid">
    <div class="dashboard-welcome">
        <div>
            <h1>Selamat datang di Drive</h1>
            <p>Semua file dan folder Anda, tersusun rapi di satu tempat.</p>
        </div>
        <a class="dashboard-info-link" href="/files" title="Kelola semua file" aria-label="Kelola semua file">ⓘ</a>
    </div>

    <section class="dashboard-section dashboard-folder-section">
        <div class="dashboard-section-heading">
            <h2><span aria-hidden="true">⌄</span> Folder yang disarankan</h2>
            <a href="/files" class="dashboard-see-all">Lihat semua</a>
        </div>
        <?php if (!empty($folders)): ?>
        <div class="dashboard-folder-grid">
            <?php foreach ($folders as $folder): ?>
            <article class="dashboard-folder-card">
                <a class="dashboard-folder-main" href="/files?folder=<?= (int) $folder['id'] ?>">
                    <span class="dashboard-folder-icon" aria-hidden="true">📁</span>
                    <span class="dashboard-card-name" title="<?= esc($folder['name'], 'attr') ?>"><?= esc($folder['name']) ?></span>
                </a>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi folder <?= esc($folder['name'], 'attr') ?>">⋮</button>
                    <div class="dropdown-menu dropdown-menu-end file-action-menu">
                        <a class="dropdown-item" href="/files?folder=<?= (int) $folder['id'] ?>">Buka folder</a>
                        <a class="dropdown-item" href="/files">Kelola di File Saya</a>
                    </div>
                </div>
            </article>
            <?php endforeach ?>
        </div>
        <?php else: ?>
        <div class="dashboard-empty">Belum ada folder. Buat folder pertama Anda melalui <a href="/files">File Saya</a>.</div>
        <?php endif ?>
    </section>

    <section class="dashboard-section dashboard-files-section">
        <div class="dashboard-section-heading dashboard-files-heading">
            <h2><span aria-hidden="true">⌄</span> File yang disarankan</h2>
            <div class="dashboard-heading-actions">
                <a href="/files" class="dashboard-see-all">Lihat semua</a>
                <div class="view-switcher dashboard-view-switcher" role="group" aria-label="Tampilan file">
                    <button type="button" class="view-switch-btn" data-dashboard-view-button="list" aria-label="Tampilan daftar" aria-pressed="false">☰</button>
                    <button type="button" class="view-switch-btn" data-dashboard-view-button="grid" aria-label="Tampilan grid" aria-pressed="true">▦</button>
                </div>
            </div>
        </div>
        <?php if (!empty($files)): ?>
        <div class="dashboard-file-grid">
            <?php foreach ($files as $file):
                $ext = strtolower((string) ($file['extension'] ?? ''));
                $mime = strtolower((string) ($file['mime_type'] ?? ''));
                $image = str_starts_with($mime, 'image/') && in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
                $pdf = $mime === 'application/pdf' || $ext === 'pdf';
                $office = in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp'], true);
                $previewable = $image || $pdf || $office;
                $kind = 'file';
                $label = strtoupper($ext ?: 'FILE');
                if (in_array($ext, ['doc','docx','odt'], true)) { $kind='word'; $label='DOC'; }
                elseif (in_array($ext, ['xls','xlsx','ods','csv'], true)) { $kind='sheet'; $label='XLS'; }
                elseif (in_array($ext, ['ppt','pptx','odp'], true)) { $kind='slides'; $label='PPT'; }
                elseif ($pdf) { $kind='pdf'; $label='PDF'; }
                elseif ($image) { $kind='image'; $label='IMG'; }
                elseif (in_array($ext, ['mp4','m4v','mov','webm','ogv','avi','mkv'], true)) { $kind='video'; $label='VIDEO'; }
                elseif (in_array($ext, ['mp3','wav','ogg','m4a','flac','aac'], true)) { $kind='audio'; $label='AUDIO'; }
                elseif (in_array($ext, ['zip','rar','7z','tar','gz','bz2'], true)) { $kind='archive'; $label='ZIP'; }
                elseif (in_array($ext, ['php','js','ts','html','css','json','xml','py','sh','sql'], true)) { $kind='code'; $label='CODE'; }
                $openUrl = $previewable ? '/files/preview/' . (int) $file['id'] : '/files/download/' . (int) $file['id'];
            ?>
            <article class="dashboard-file-card">
                <div class="dashboard-file-card-head">
                    <span class="dashboard-file-kind dashboard-file-kind--<?= esc($kind, 'attr') ?>" aria-hidden="true">
                        <?php if ($image): ?>▧<?php elseif ($kind === 'sheet'): ?>▤<?php elseif ($kind === 'video'): ?>▷<?php elseif ($kind === 'archive'): ?>▣<?php elseif ($kind === 'pdf'): ?>PDF<?php else: ?>▤<?php endif ?>
                    </span>
                    <a class="dashboard-file-title" href="<?= esc($openUrl, 'attr') ?>" title="<?= esc($file['original_name'], 'attr') ?>"><?= esc($file['original_name']) ?></a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi file <?= esc($file['original_name'], 'attr') ?>">⋮</button>
                        <div class="dropdown-menu dropdown-menu-end file-action-menu">
                            <?php if ($previewable): ?><a class="dropdown-item" href="/files/preview/<?= (int) $file['id'] ?>">Preview</a><?php endif ?>
                            <a class="dropdown-item" href="/files/download/<?= (int) $file['id'] ?>">Unduh</a>
                            <a class="dropdown-item" href="/files">Kelola di File Saya</a>
                        </div>
                    </div>
                </div>
                <a class="dashboard-file-preview dashboard-file-preview--<?= esc($kind, 'attr') ?>" href="<?= esc($openUrl, 'attr') ?>" aria-label="Buka <?= esc($file['original_name'], 'attr') ?>">
                    <?php if ($previewable): ?>
                        <img src="/files/thumbnail/<?= (int) $file['id'] ?>" alt="Preview <?= esc($file['original_name'], 'attr') ?>" loading="lazy" onerror="this.closest('.dashboard-file-preview').classList.add('is-fallback');this.remove()">
                        <span class="dashboard-preview-fallback"><?= esc($label) ?></span>
                    <?php else: ?>
                        <span class="dashboard-file-art-icon"><?= $kind === 'video' ? '▷' : ($kind === 'audio' ? '♫' : ($kind === 'archive' ? '▣' : '▤')) ?></span>
                        <span class="dashboard-file-art-label"><?= esc($label) ?></span>
                    <?php endif ?>
                </a>
                <div class="dashboard-file-activity">
                    <span class="dashboard-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) ($user['name'] ?? 'A'), 0, 1))) ?></span>
                    <span>Anda mengubahnya · <?= esc($formatActivityDate($file['updated_at'] ?? $file['created_at'] ?? null)) ?></span>
                </div>
            </article>
            <?php endforeach ?>
        </div>
        <?php else: ?>
        <div class="dashboard-empty">Belum ada file. <a href="/files">Unggah file pertama Anda</a> untuk mulai menggunakan Drive.</div>
        <?php endif ?>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('dashboardDriveRoot');
    if (!root) return;
    const buttons = root.querySelectorAll('[data-dashboard-view-button]');
    const saved = localStorage.getItem('pcd-dashboard-view') || 'grid';
    const setView = function (view) {
        root.dataset.dashboardView = view;
        buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.dashboardViewButton === view)));
        localStorage.setItem('pcd-dashboard-view', view);
    };
    buttons.forEach(button => button.addEventListener('click', () => setView(button.dataset.dashboardViewButton)));
    setView(saved);
});
</script>
<?= $this->endSection() ?>
