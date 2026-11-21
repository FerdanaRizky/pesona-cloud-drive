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

    <div class="dashboard-selection-bar" id="dashboardSelectionBar" aria-live="polite" aria-hidden="true">
    <span class="dashboard-selection-check" aria-hidden="true">✓</span>
    <strong id="dashboardSelectionCount">0 item dipilih</strong>
    <button type="button" id="dashboardClearSelection" class="dashboard-selection-clear">Batal pilih</button>
</div>
<section class="dashboard-section dashboard-folder-section">
        <div class="dashboard-section-heading">
            <h2><span aria-hidden="true">⌄</span> Folder yang disarankan</h2>
            <a href="/files" class="dashboard-see-all">Lihat semua</a>
        </div>
        <?php if (!empty($folders)): ?>
        <div class="dashboard-folder-grid">
            <?php foreach ($folders as $folder): ?>
            <article class="dashboard-folder-card dashboard-selectable-item" data-dashboard-selectable data-item-type="folder" data-item-id="<?= (int) $folder['id'] ?>" tabindex="0" role="option" aria-selected="false">
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
        <div class="dashboard-file-list-wrap">
            <table class="dashboard-file-list">
                <thead><tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Alasan file disarankan</th>
                    <th scope="col">Pemilik</th>
                    <th scope="col">Lokasi</th>
                    <th scope="col" class="dashboard-list-action-heading"><span class="visually-hidden">Aksi</span></th>
                </tr></thead>
                <tbody>
                <?php foreach ($files as $file):
                    $ext = strtolower((string) ($file['extension'] ?? ''));
                    $mime = strtolower((string) ($file['mime_type'] ?? ''));
                    $kind = 'file'; $label = strtoupper($ext ?: 'FILE');
                    if (in_array($ext, ['doc','docx','odt'], true)) { $kind='word'; $label='DOC'; }
                    elseif (in_array($ext, ['xls','xlsx','ods','csv'], true)) { $kind='sheet'; $label='XLS'; }
                    elseif (in_array($ext, ['ppt','pptx','odp'], true)) { $kind='slides'; $label='PPT'; }
                    elseif ($mime === 'application/pdf' || $ext === 'pdf') { $kind='pdf'; $label='PDF'; }
                    elseif (str_starts_with($mime, 'image/')) { $kind='image'; $label='IMG'; }
                    elseif (in_array($ext, ['mp4','m4v','mov','webm','ogv','avi','mkv'], true)) { $kind='video'; $label='VIDEO'; }
                    elseif (in_array($ext, ['mp3','wav','ogg','m4a','flac','aac'], true)) { $kind='audio'; $label='AUDIO'; }
                    elseif (in_array($ext, ['zip','rar','7z','tar','gz','bz2'], true)) { $kind='archive'; $label='ZIP'; }
                    $reasonDate = $formatActivityDate($file['updated_at'] ?? $file['created_at'] ?? null);
                    $ownerName = (string) ($user['name'] ?? session()->get('name') ?? 'Saya');
                    $parentId = (int) ($file['parent_id'] ?? 0);
                    $locationName = (string) ($file['dashboard_location'] ?? 'Drive Saya');
                    $fileUrl = in_array($kind, ['image','pdf','word','sheet','slides'], true) ? '/files/preview/' . (int) $file['id'] : '/files/download/' . (int) $file['id'];
                ?>
                    <tr class="dashboard-selectable-item dashboard-file-list-row" data-dashboard-selectable data-item-type="file" data-item-id="<?= (int) $file['id'] ?>" tabindex="0" role="option" aria-selected="false">
                        <td>
                            <a class="dashboard-list-name" href="<?= esc($fileUrl, 'attr') ?>">
                                <span class="dashboard-file-kind dashboard-file-kind--<?= esc($kind, 'attr') ?>" aria-hidden="true"><?= esc($label) ?></span>
                                <span class="dashboard-list-filename" title="<?= esc($file['original_name'], 'attr') ?>"><?= esc($file['original_name']) ?></span>
                            </a>
                        </td>
                        <td><span class="dashboard-list-history">Anda mengubahnya · <?= esc($reasonDate) ?></span></td>
                        <td><span class="dashboard-list-owner"><span class="dashboard-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr($ownerName, 0, 1))) ?></span><span title="<?= esc($ownerName, 'attr') ?>"><?= esc($ownerName) ?></span></span></td>
                        <td><a class="dashboard-list-location" href="<?= $parentId > 0 ? '/files?folder=' . $parentId : '/files' ?>" title="<?= esc($locationName, 'attr') ?>"><span aria-hidden="true"><?= $parentId > 0 ? '📁' : '▣' ?></span><span><?= esc($locationName) ?></span></a></td>
                        <td class="dashboard-list-action-cell">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi file <?= esc($file['original_name'], 'attr') ?>">⋮</button>
                                <div class="dropdown-menu dropdown-menu-end file-action-menu">
                                    <a class="dropdown-item" href="/files/preview/<?= (int) $file['id'] ?>">Preview</a>
                                    <a class="dropdown-item" href="/files/download/<?= (int) $file['id'] ?>">Unduh</a>
                                    <a class="dropdown-item" href="/files">Kelola di File Saya</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
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
            <article class="dashboard-file-card dashboard-selectable-item" data-dashboard-selectable data-item-type="file" data-item-id="<?= (int) $file['id'] ?>" tabindex="0" role="option" aria-selected="false">
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

    // Same interaction as File Saya: click selects, double-click opens.
    const getItem = target => target.closest('[data-dashboard-selectable]');
    const isControl = target => !!target.closest('button,a,input,select,textarea,form,.dropdown,[data-bs-toggle],.dropdown-menu');
    const getItems = () => [...root.querySelectorAll('[data-dashboard-selectable]')];
    const selected = new Set();
    let anchor = null;
    const bar = document.getElementById('dashboardSelectionBar');
    const count = document.getElementById('dashboardSelectionCount');
    const clear = document.getElementById('dashboardClearSelection');
    const refresh = () => {
        getItems().forEach(item => {
            const active = selected.has(item);
            item.classList.toggle('is-dashboard-selected', active);
            item.setAttribute('aria-selected', String(active));
        });
        const total = selected.size;
        const files = [...selected].filter(item => item.dataset.itemType === 'file').length;
        count.textContent = total + ' item dipilih (' + files + ' file, ' + (total - files) + ' folder)';
        bar.classList.toggle('is-active', total > 0);
        bar.setAttribute('aria-hidden', String(total === 0));
    };
    const choose = (item, event) => {
        const items = getItems();
        if (event.shiftKey && anchor && items.includes(anchor)) {
            if (!event.ctrlKey && !event.metaKey) selected.clear();
            const from = items.indexOf(anchor), to = items.indexOf(item);
            items.slice(Math.min(from, to), Math.max(from, to) + 1).forEach(entry => selected.add(entry));
        } else if (event.ctrlKey || event.metaKey) {
            selected.has(item) ? selected.delete(item) : selected.add(item);
            anchor = item;
        } else {
            selected.clear();
            selected.add(item);
            anchor = item;
        }
        refresh();
    };
    root.addEventListener('click', event => {
        const item = getItem(event.target);
        if (!item || isControl(event.target)) return;
        event.preventDefault();
        choose(item, event);
    });
    root.addEventListener('dblclick', event => {
        const item = getItem(event.target);
        if (!item || isControl(event.target)) return;
        const link = item.querySelector('.dashboard-folder-main[href], .dashboard-file-title[href], .dashboard-list-name[href]');
        if (link) {
            event.preventDefault();
            window.location.assign(link.href);
        }
    });
    root.addEventListener('keydown', event => {
        if ((event.key === 'Enter' || event.key === ' ') && getItem(event.target) && !isControl(event.target)) {
            event.preventDefault();
            choose(getItem(event.target), event);
        }
    });
    clear?.addEventListener('click', () => { selected.clear(); anchor = null; refresh(); });
});
</script>
<?= $this->endSection() ?>
