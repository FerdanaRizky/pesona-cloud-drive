<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$menuIcon = static function (string $name): string {
    $paths = [
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.7 10.7 6.6-4.4M8.7 13.3l6.6 4.4"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/>',
        'move' => '<path d="M3 7h7l2 2h9v11H3z"/><path d="M12 12v5m-3-3 3 3 3-3"/>',
        'trash' => '<path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/>',
        'rename' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'preview' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.1 0l3-3A5 5 0 0 0 13 2.9l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.1 0l-3 3A5 5 0 0 0 11 21.1l1.7-1.7"/>',
        'restore' => '<path d="M3 7v5h5"/><path d="M4.5 12a8 8 0 1 0 2-5.3L3 12"/>',
        'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
        'open' => '<path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v1H5a2 2 0 0 0-1.9 1.4L2 17.5V7Z"/><path d="m4 12 1-2h17l-3 8H2l2-6Z"/>',
        'folder' => '<path d="M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
        'close' => '<path d="m18 6-12 12M6 6l12 12"/>',
    ];
    return '<svg class="menu-action-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name] ?? $paths['folder']).'</svg>';
};
?>
<?php
$formatActivityDate = static function ($value) {
    $time = $value ? strtotime((string) $value) : false;
    return $time ? date('j M', $time) : 'Baru saja';
};
?>
<div class="dashboard-drive" id="dashboardDriveRoot" data-dashboard-view="grid">
    <div class="dashboard-welcome">
        <div>
            <h1>Selamat datang, <?= esc(session()->get('name') ?: session()->get('email') ?: 'Pengguna') ?></h1>
            <p>Semua file dan folder Anda, tersusun rapi di satu tempat.</p>
        </div>
        <a class="dashboard-info-link" href="/files" title="Kelola semua file" aria-label="Kelola semua file">ⓘ</a>
    </div>

    <form id="dashboardBulkForm" method="post" action="/files/bulk-download" class="dashboard-selection-bar" aria-hidden="true">
        <?= csrf_field() ?><div id="dashboardBulkIds"></div>
        <button type="button" class="dashboard-selection-clear" id="dashboardClearSelection" aria-label="Batalkan pilihan">×</button>
        <strong id="dashboardSelectionCount">0 item dipilih</strong>
        <div class="dashboard-selection-actions">
            <button type="button" class="selection-action-btn" data-dashboard-bulk="share" title="Bagikan" aria-label="Bagikan"><?= $menuIcon('share') ?></button>
            <button type="button" class="selection-action-btn" data-dashboard-bulk="download" title="Download" aria-label="Download"><?= $menuIcon('download') ?></button>
            <button type="button" class="selection-action-btn" data-dashboard-bulk="move" title="Pindahkan" aria-label="Pindahkan"><?= $menuIcon('move') ?></button>
            <div class="dropdown"><button type="button" class="btn action-menu-trigger bulk-menu-trigger" id="dashboardBulkMenu" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" disabled><?= $menuIcon('more') ?></button>
                <div class="dropdown-menu dropdown-menu-end file-action-menu">
                    <button type="button" class="dropdown-item" data-dashboard-bulk="share"><?= $menuIcon('share') ?>Bagikan</button>
                    <button type="button" class="dropdown-item" data-dashboard-bulk="download"><?= $menuIcon('download') ?>Download</button>
                    <button type="button" class="dropdown-item" data-dashboard-bulk="move"><?= $menuIcon('move') ?>Pindah ke folder</button>
                </div>
            </div>
        </div>
    </form>
    <div class="modal fade" id="dashboardFileActionModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="dashboardFileActionForm" method="post"><?= csrf_field() ?><div class="modal-header"><h5 class="modal-title" id="dashboardFileActionTitle">Aksi File</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">
        <div id="dashboardRenameField"><label class="form-label" for="dashboardActionName">Nama baru</label><input class="form-control" id="dashboardActionName" name="name" maxlength="255"></div>
        <div id="dashboardMoveField" class="d-none"><label class="form-label" for="dashboardActionFolder">Pilih folder tujuan</label><select class="form-select" id="dashboardActionFolder" name="parent_id"><option value="0">Drive utama</option><?php foreach (($allFolders ?? []) as $target): ?><option value="<?= (int) $target['id'] ?>"><?= esc($target['name']) ?></option><?php endforeach ?></select></div>
        <div id="dashboardShareField" class="d-none"><label class="form-label" for="dashboardActionExpiry">Masa berlaku tautan</label><select class="form-select" id="dashboardActionExpiry" name="expires_days"><option value="1">1 hari</option><option value="7" selected>7 hari</option><option value="30">30 hari</option><option value="never">Tanpa kedaluwarsa</option></select></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="dashboardFileActionSubmit">Simpan</button></div></form></div></div></div>
    <div class="modal fade" id="dashboardPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-truncate" id="dashboardPreviewTitle">Preview file</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup preview"></button></div>
            <div class="modal-body p-0" style="height:min(78vh,900px)"><iframe id="dashboardPreviewFrame" title="Preview file" src="about:blank" style="display:block;width:100%;height:100%;border:0;background:var(--bs-body-bg)"></iframe></div>
        </div></div>
    </div>
    <div class="modal fade" id="dashboardBulkModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="dashboardBulkTitle">Aksi item terpilih</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">
        <div id="dashboardBulkShareField" class="d-none"><label class="form-label" for="dashboardBulkExpiry">Masa berlaku tautan</label><select class="form-select" id="dashboardBulkExpiry" name="expires_days" form="dashboardBulkForm"><option value="1">1 hari</option><option value="7" selected>7 hari</option><option value="30">30 hari</option><option value="never">Tanpa kedaluwarsa</option></select></div>
        <div id="dashboardBulkMoveField" class="d-none"><label class="form-label" for="dashboardBulkFolder">Pilih folder tujuan</label><select class="form-select" id="dashboardBulkFolder" name="parent_id" form="dashboardBulkForm"><option value="0">Drive utama</option><?php foreach (($allFolders ?? []) as $target): ?><option value="<?= (int) $target['id'] ?>"><?= esc($target['name']) ?></option><?php endforeach ?></select></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="dashboardBulkConfirm">Lanjutkan</button></div></div></div></div>
<section class="dashboard-section dashboard-folder-section">
        <div class="dashboard-section-heading">
            <h2>Folder yang disarankan</h2>
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
                    <button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi folder <?= esc($folder['name'], 'attr') ?>"><?= $menuIcon('more') ?></button>
                    <div class="dropdown-menu dropdown-menu-end file-action-menu">
                        <a class="dropdown-item" href="/files?folder=<?= (int) $folder['id'] ?>"><?= $menuIcon('open') ?>Buka folder</a>
                        <button type="button" class="dropdown-item" data-dashboard-action="rename" data-url="/files/folders/rename/<?= (int) $folder['id'] ?>" data-name="<?= esc($folder['name'], 'attr') ?>" data-maxlength="120"><?= $menuIcon('rename') ?>Ganti nama</button>
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
            <h2>File yang disarankan</h2>
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
                    if ($ext === '') $ext = strtolower((string) pathinfo((string) ($file['original_name'] ?? ''), PATHINFO_EXTENSION));
                    $mime = strtolower((string) ($file['mime_type'] ?? ''));
                    $previewable = str_starts_with($mime, 'image/')
                        || $mime === 'application/pdf'
                        || str_starts_with($mime, 'video/')
                        || in_array($ext, ['mp4','m4v','mov','webm','ogv','ogg','avi','mkv','3gp','3g2','ts','mts','m2ts','mpg','mpeg','wmv','flv','vob','asf','divx'], true)
                        || in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp'], true);
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
                    $fileUrl = ($previewable || in_array($kind, ['image','pdf','word','sheet','slides'], true)) ? '/files/preview/' . (int) $file['id'] : '/files/download/' . (int) $file['id'];
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
                                <button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi file <?= esc($file['original_name'], 'attr') ?>"><?= $menuIcon('more') ?></button>
                                <div class="dropdown-menu dropdown-menu-end file-action-menu">
                                    <?php if ($previewable): ?><a class="dropdown-item" href="/files/preview/<?= (int) $file['id'] ?>"><?= $menuIcon('preview') ?>Preview</a><?php endif ?>
                                    <a class="dropdown-item" href="/files/download/<?= (int) $file['id'] ?>"><?= $menuIcon('download') ?>Unduh</a>
                                    <button type="button" class="dropdown-item" data-dashboard-action="rename" data-url="/files/rename/<?= (int) $file['id'] ?>" data-name="<?= esc($file['original_name'], 'attr') ?>"><?= $menuIcon('rename') ?>Ganti nama</button>
                                    <button type="button" class="dropdown-item" data-dashboard-action="move" data-url="/files/move/<?= (int) $file['id'] ?>"><?= $menuIcon('move') ?>Pindah folder</button>
                                    <button type="button" class="dropdown-item" data-dashboard-action="share" data-url="/files/share/<?= (int) $file['id'] ?>"><?= $menuIcon('share') ?>Bagi tautan</button>
                                    <form method="post" action="/files/star/<?= (int) $file['id'] ?>"><?= csrf_field() ?><button class="dropdown-item <?= !empty($file['is_starred']) ? 'text-warning' : '' ?>"><?= $menuIcon('star') ?><?= !empty($file['is_starred']) ? 'Hapus favorit' : 'Tambahkan favorit' ?></button></form>
                                    <?php if (!empty($file['has_share'])): ?><form method="post" action="/files/share/revoke/<?= (int) $file['id'] ?>" onsubmit="return confirm('Cabut tautan berbagi file ini?')"><?= csrf_field() ?><button class="dropdown-item text-info"><?= $menuIcon('link') ?>Cabut tautan berbagi</button></form><?php endif ?>
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
                    if ($ext === '') $ext = strtolower((string) pathinfo((string) ($file['original_name'] ?? ''), PATHINFO_EXTENSION));
                $mime = strtolower((string) ($file['mime_type'] ?? ''));
                $image = str_starts_with($mime, 'image/') && in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
                $pdf = $mime === 'application/pdf' || $ext === 'pdf';
                $office = in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp'], true);
                $video = str_starts_with($mime, 'video/') || in_array($ext, ['mp4','m4v','mov','webm','ogv','ogg','avi','mkv','3gp','3g2','ts','mts','m2ts','mpg','mpeg','wmv','flv','vob','asf','divx'], true);
                $previewable = $image || $pdf || $office || $video;
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
                        <button class="btn btn-sm btn-outline-secondary action-menu-trigger dashboard-more-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi file <?= esc($file['original_name'], 'attr') ?>"><?= $menuIcon('more') ?></button>
                        <div class="dropdown-menu dropdown-menu-end file-action-menu">
                            <?php if ($previewable): ?><a class="dropdown-item" href="/files/preview/<?= (int) $file['id'] ?>"><?= $menuIcon('preview') ?>Preview</a><?php endif ?>
                            <a class="dropdown-item" href="/files/download/<?= (int) $file['id'] ?>"><?= $menuIcon('download') ?>Unduh</a>
                            <button type="button" class="dropdown-item" data-dashboard-action="rename" data-url="/files/rename/<?= (int) $file['id'] ?>" data-name="<?= esc($file['original_name'], 'attr') ?>"><?= $menuIcon('rename') ?>Ganti nama</button>
                            <button type="button" class="dropdown-item" data-dashboard-action="move" data-url="/files/move/<?= (int) $file['id'] ?>"><?= $menuIcon('move') ?>Pindah folder</button>
                            <button type="button" class="dropdown-item" data-dashboard-action="share" data-url="/files/share/<?= (int) $file['id'] ?>"><?= $menuIcon('share') ?>Bagi tautan</button>
                            <form method="post" action="/files/star/<?= (int) $file['id'] ?>"><?= csrf_field() ?><button class="dropdown-item <?= !empty($file['is_starred']) ? 'text-warning' : '' ?>"><?= $menuIcon('star') ?><?= !empty($file['is_starred']) ? 'Hapus favorit' : 'Tambahkan favorit' ?></button></form>
                            <?php if (!empty($file['has_share'])): ?><form method="post" action="/files/share/revoke/<?= (int) $file['id'] ?>" onsubmit="return confirm('Cabut tautan berbagi file ini?')"><?= csrf_field() ?><button class="dropdown-item text-info"><?= $menuIcon('link') ?>Cabut tautan berbagi</button></form><?php endif ?>
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
    if (!document.getElementById('pcd-dashboard-touch-style')) {
        const style = document.createElement('style');
        style.id = 'pcd-dashboard-touch-style';
        style.textContent = '#dashboardDriveRoot [data-dashboard-selectable], #dashboardDriveRoot [data-dashboard-selectable] *, #dashboardDriveRoot a { -webkit-touch-callout:none !important; -webkit-user-select:none !important; user-select:none !important; }';
        document.head.appendChild(style);
    }
    const buttons = root.querySelectorAll('[data-dashboard-view-button]');
    const saved = localStorage.getItem('pcd-dashboard-view') || 'grid';
    const setView = view => {
        root.dataset.dashboardView = view;
        buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.dashboardViewButton === view)));
        localStorage.setItem('pcd-dashboard-view', view);
        refreshSelection();
    };
    buttons.forEach(button => button.addEventListener('click', () => setView(button.dataset.dashboardViewButton)));

    const bar = root.querySelector('.dashboard-selection-bar');
    const count = document.getElementById('dashboardSelectionCount');
    const clear = document.getElementById('dashboardClearSelection');
    const bulkForm = document.getElementById('dashboardBulkForm');
    const bulkIds = document.getElementById('dashboardBulkIds');
    const bulkMenu = document.getElementById('dashboardBulkMenu');
    const selected = new Set();
    let anchor = null;
    const itemKey = item => item.dataset.itemType + ':' + item.dataset.itemId;
    const getItem = target => target.closest('[data-dashboard-selectable]');
    const isControl = target => !!target.closest('button,input,select,textarea,form,.dropdown,[data-bs-toggle],.dropdown-menu,a.dashboard-list-location');
    const getItems = () => [...root.querySelectorAll('[data-dashboard-selectable]')].filter(item => item.getClientRects().length > 0);
    function refreshSelection() {
        const items = getItems();
        items.forEach(item => {
            const active = selected.has(itemKey(item));
            item.classList.toggle('is-dashboard-selected', active);
            item.setAttribute('aria-selected', String(active));
        });
        const total = selected.size;
        const fileCount = [...selected].filter(key => key.startsWith('file:')).length;
        const folderCount = total - fileCount;
        count.textContent = total + ' item dipilih (' + fileCount + ' file, ' + folderCount + ' folder)';
        bar.classList.toggle('is-active', total > 0);
        bar.setAttribute('aria-hidden', String(total === 0));
        bulkIds.innerHTML = '';
        selected.forEach(key => {
            const [type, id] = key.split(':');
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = type === 'folder' ? 'folder_ids[]' : 'file_ids[]'; input.value = id;
            bulkIds.appendChild(input);
        });
        if (bulkMenu) bulkMenu.disabled = total === 0;
    }
    function choose(item, event) {
        const items = getItems();
        if (event.shiftKey && anchor && items.includes(anchor)) {
            if (!event.ctrlKey && !event.metaKey) selected.clear();
            const from = items.indexOf(anchor), to = items.indexOf(item);
            items.slice(Math.min(from, to), Math.max(from, to) + 1).forEach(entry => selected.add(itemKey(entry)));
        } else if (event.ctrlKey || event.metaKey) {
            const key = itemKey(item);
            selected.has(key) ? selected.delete(key) : selected.add(key);
            anchor = item;
        } else {
            selected.clear(); selected.add(itemKey(item)); anchor = item;
        }
        refreshSelection();
    }
    // Desktop: single click selects; double click opens the item.
    const previewModal = document.getElementById('dashboardPreviewModal');
    const previewFrame = document.getElementById('dashboardPreviewFrame');
    const previewTitle = document.getElementById('dashboardPreviewTitle');
    const openPreview = (item, link) => {
        if (!previewModal || !previewFrame || !window.bootstrap?.Modal) return;
        const match = (link?.getAttribute('href') || '').match(/\\/files\\/preview\\/(\\d+)/);
        if (!match) return;
        const name = item.querySelector('.dashboard-list-filename,.dashboard-file-title')?.textContent?.trim()
            || link.getAttribute('title') || link.getAttribute('aria-label') || 'Preview file';
        previewTitle.textContent = name;
        previewFrame.src = '/files/preview-content/' + match[1];
        window.bootstrap.Modal.getOrCreateInstance(previewModal).show();
    };
    previewModal?.addEventListener('hidden.bs.modal', () => { if (previewFrame) previewFrame.src = 'about:blank'; });
    root.addEventListener('click', event => {
        if (isMobileTouch()) return;
        const item = getItem(event.target);
        if (!item || isControl(event.target)) return;
        const link = event.target.closest('a[href]');
        if (link && item.contains(link) && !link.closest('.dashboard-list-location')) event.preventDefault();
        choose(item, event);
    });
    root.addEventListener('dblclick', event => {
        if (isMobileTouch()) return;
        const item = getItem(event.target);
        if (!item || isControl(event.target)) return;
        const link = item.querySelector('.dashboard-folder-main[href],.dashboard-file-title[href],.dashboard-file-preview[href],.dashboard-list-name[href]');
        if (!link) return;
        event.preventDefault();
        if (item.dataset.itemType === 'file' && link.pathname.includes('/files/preview/')) openPreview(item, link);
        else window.location.assign(link.href);
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Delete' && event.key !== 'Escape') return;
        if (event.target?.isContentEditable || ['INPUT','TEXTAREA','SELECT'].includes(event.target?.tagName)) return;
        if (document.querySelector('.modal.show') || selected.size === 0) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            selected.clear(); anchor = null; mobileSelectionMode = false;
            delete root.dataset.mobileSelectionMode; root.dataset.mobileSelectedCount = '0';
            refreshSelection();
        } else {
            event.preventDefault();
            if (!confirm('Pindahkan ' + selected.size + ' item terpilih ke Sampah?')) return;
            bulkForm.action = '/files/bulk-trash';
            bulkForm.requestSubmit();
        }
    });

    const isMobileTouch = () => ('ontouchstart' in window) || navigator.maxTouchPoints > 0;
    let mobileSelectionMode = false, longPressTimer = null, longPressItem = null, longPressFired = false;
    let startX = 0, startY = 0, touchStartItem = null, touchMoved = false, suppressNextClick = false;
    const cancelLongPress = () => { if (longPressTimer !== null) clearTimeout(longPressTimer); longPressTimer = null; longPressItem = null; };
    const toggleMobileItem = item => {
        const key = itemKey(item);
        if (selected.has(key)) selected.delete(key); else { selected.add(key); anchor = item; }
        mobileSelectionMode = selected.size > 0;
        root.dataset.mobileSelectedCount = String(selected.size);
        if (mobileSelectionMode) root.dataset.mobileSelectionMode = 'true'; else delete root.dataset.mobileSelectionMode;
        refreshSelection();
    };
    const beginLongPress = (event, point) => {
        if (!isMobileTouch() || !point || (event.touches && event.touches.length !== 1)) return;
        const item = getItem(event.target);
        if (!item || isControl(event.target)) return;
        cancelLongPress(); longPressFired = false; longPressItem = item;
        startX = point.clientX; startY = point.clientY;
        longPressTimer = window.setTimeout(() => {
            longPressTimer = null;
            if (!longPressItem || !root.contains(longPressItem)) return;
            longPressFired = true; mobileSelectionMode = true;
            root.dataset.mobileSelectionMode = 'true';
            toggleMobileItem(longPressItem);
            if (navigator.vibrate) { try { navigator.vibrate(30); } catch (error) {} }
        }, 500);
    };
    root.addEventListener('touchstart', event => {
        touchStartItem = null; touchMoved = false;
        if (event.touches && event.touches.length === 1) {
            touchStartItem = getItem(event.target);
            beginLongPress(event, event.touches[0]);
        }
    }, { capture: true, passive: true });
    root.addEventListener('touchmove', event => {
        if (!event.touches || !event.touches.length) return;
        const p = event.touches[0];
        if (Math.abs(p.clientX - startX) > 12 || Math.abs(p.clientY - startY) > 12) {
            touchMoved = true;
            cancelLongPress();
        }
    }, { capture: true, passive: true });
    root.addEventListener('touchend', event => {
        const item = touchStartItem;
        const wasLongPress = longPressFired;
        cancelLongPress();
        touchStartItem = null;
        if (!item || touchMoved || wasLongPress || !mobileSelectionMode || isControl(event.target)) {
            if (wasLongPress) suppressNextClick = true;
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        suppressNextClick = true;
        toggleMobileItem(item);
    }, { capture: true, passive: false });
    root.addEventListener('touchcancel', () => {
        cancelLongPress(); touchStartItem = null; touchMoved = false;
    }, { capture: true, passive: true });
    root.addEventListener('pointerdown', event => { if (event.pointerType === 'touch') beginLongPress(event, event); }, { capture: true, passive: true });
    root.addEventListener('pointermove', event => {
        if (longPressTimer !== null && event.pointerType === 'touch' &&
            (Math.abs(event.clientX - startX) > 12 || Math.abs(event.clientY - startY) > 12)) cancelLongPress();
    }, { capture: true, passive: true });
    root.addEventListener('pointerup', event => { if (event.pointerType === 'touch') cancelLongPress(); }, { capture: true, passive: true });
    root.addEventListener('pointercancel', cancelLongPress, { capture: true, passive: true });
    root.addEventListener('contextmenu', event => {
        if (getItem(event.target)) { event.preventDefault(); event.stopPropagation(); }
    }, true);
    root.addEventListener('click', event => {
        const item = getItem(event.target);
        if (!isMobileTouch() || !item || isControl(event.target)) return;
        if (longPressFired || suppressNextClick) { longPressFired = false; suppressNextClick = false; event.preventDefault(); event.stopImmediatePropagation(); return; }
        if (mobileSelectionMode || root.dataset.mobileSelectionMode === 'true') {
            event.preventDefault(); event.stopImmediatePropagation(); toggleMobileItem(item); return;
        }
        const clickedLink = event.target.closest('a[href]');
        if (!clickedLink) {
            const link = item.querySelector('.dashboard-folder-main[href], .dashboard-file-title[href], .dashboard-file-preview[href], .dashboard-list-name[href]');
            if (link) { event.preventDefault(); window.location.assign(link.href); }
        }
    }, true);
    clear?.addEventListener('click', () => { selected.clear(); anchor = null; mobileSelectionMode = false; delete root.dataset.mobileSelectionMode; root.dataset.mobileSelectedCount = '0'; refreshSelection(); });

    const actionModal = document.getElementById('dashboardFileActionModal');
    const actionForm = document.getElementById('dashboardFileActionForm');
    const actionTitle = document.getElementById('dashboardFileActionTitle');
    const actionSubmit = document.getElementById('dashboardFileActionSubmit');
    const renameField = document.getElementById('dashboardRenameField');
    const moveField = document.getElementById('dashboardMoveField');
    const shareField = document.getElementById('dashboardShareField');
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-dashboard-action]');
        if (!button) return;
        event.preventDefault();
        const kind = button.dataset.dashboardAction;
        actionForm.action = button.dataset.url;
        renameField.classList.toggle('d-none', kind !== 'rename');
        moveField.classList.toggle('d-none', kind !== 'move');
        shareField.classList.toggle('d-none', kind !== 'share');
        if (kind === 'rename') {
            actionTitle.textContent = 'Ganti Nama'; actionSubmit.textContent = 'Simpan';
            const input = document.getElementById('dashboardActionName');
            input.value = button.dataset.name || ''; input.maxLength = Number(button.dataset.maxlength || 255);
        } else if (kind === 'move') {
            actionTitle.textContent = 'Pindah Folder'; actionSubmit.textContent = 'Pindahkan';
        } else {
            actionTitle.textContent = 'Bagi Tautan'; actionSubmit.textContent = 'Buat Tautan';
        }
        bootstrap.Modal.getOrCreateInstance(actionModal).show();
    });

    const bulkModal = document.getElementById('dashboardBulkModal');
    const bulkTitle = document.getElementById('dashboardBulkTitle');
    const bulkShare = document.getElementById('dashboardBulkShareField');
    const bulkMove = document.getElementById('dashboardBulkMoveField');
    const bulkConfirm = document.getElementById('dashboardBulkConfirm');
    let pendingBulk = '';
    function runBulk(action) {
        if (!selected.size) return;
        const files = [...selected].filter(key => key.startsWith('file:'));
        if (action === 'share' || action === 'move') {
            if (!files.length) { alert(action === 'share' ? 'Pilih minimal satu file untuk dibagikan.' : 'Pilih minimal satu file untuk dipindahkan.'); return; }
            pendingBulk = action;
            bulkShare.classList.toggle('d-none', action !== 'share');
            bulkMove.classList.toggle('d-none', action !== 'move');
            bulkTitle.textContent = action === 'share' ? 'Bagikan file terpilih' : 'Pindahkan file terpilih';
            bulkConfirm.textContent = action === 'share' ? 'Buat tautan' : 'Pindahkan file';
            bootstrap.Modal.getOrCreateInstance(bulkModal, {backdrop:true, keyboard:true, focus:true}).show();
            return;
        }
        if (action === 'download') {
            if (!files.length) { alert('Pilih minimal satu file untuk diunduh.'); return; }
            if (!confirm('Unduh file yang dipilih? Folder yang dipilih akan dilewati.')) return;
            bulkForm.action = '/files/bulk-download'; bulkForm.requestSubmit();
        }
    }
    document.querySelectorAll('[data-dashboard-bulk]').forEach(button => button.addEventListener('click', event => {
        event.preventDefault(); event.stopPropagation(); runBulk(button.dataset.dashboardBulk);
    }));
    bulkConfirm.addEventListener('click', () => {
        if (!pendingBulk) return;
        bulkForm.action = pendingBulk === 'share' ? '/files/bulk-share' : '/files/bulk-move';
        bulkForm.requestSubmit();
    });
    bulkModal.addEventListener('hidden.bs.modal', () => { pendingBulk = ''; document.body.classList.remove('modal-open'); });
    setView(saved);
});
</script>
<?= $this->endSection() ?>
