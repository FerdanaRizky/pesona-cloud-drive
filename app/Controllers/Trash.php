<?php
namespace App\Controllers;

use App\Models\DriveFileModel;
use App\Models\DriveFolderModel;

class Trash extends BaseController
{
    public function index()
    {
        $uid = (int) session()->get('user_id');
        $fileModel = new DriveFileModel();
        $folderModel = new DriveFolderModel();

        $files = $fileModel->where('user_id', $uid)->where('is_trashed', 1)->orderBy('updated_at', 'DESC')->findAll();
        $folders = $folderModel->where('user_id', $uid)->where('is_trashed', 1)->orderBy('updated_at', 'DESC')->findAll();

        // Keep the original parent references available even when the parent folder is also in Trash.
        $allFolders = $folderModel->where('user_id', $uid)->findAll();
        $folderMap = [];
        foreach ($allFolders as $folder) {
            $folderMap[(int) $folder['id']] = $folder;
        }

        $folderPath = static function (?int $parentId) use (&$folderMap): string {
            if (!$parentId) return 'Drive Saya';
            $parts = [];
            $visited = [];
            $currentId = $parentId;
            while ($currentId && isset($folderMap[$currentId]) && !isset($visited[$currentId])) {
                $visited[$currentId] = true;
                $current = $folderMap[$currentId];
                array_unshift($parts, (string) $current['name']);
                $currentId = !empty($current['parent_id']) ? (int) $current['parent_id'] : 0;
            }
            return $parts ? 'Drive Saya / ' . implode(' / ', $parts) : 'Drive Saya';
        };

        foreach ($files as &$file) {
            $file['original_location'] = $folderPath(!empty($file['parent_id']) ? (int) $file['parent_id'] : null);
            $file['trashed_at'] = $file['updated_at'] ?? null;
        }
        unset($file);

        foreach ($folders as &$folder) {
            $folder['original_location'] = $folderPath(!empty($folder['parent_id']) ? (int) $folder['parent_id'] : null);
            $folder['trashed_at'] = $folder['updated_at'] ?? null;
        }
        unset($folder);

        // Trash filters: item type, deletion/update history, and original parent folder.
        $typeFilter = trim((string) $this->request->getGet('type'));
        $historyFilter = trim((string) $this->request->getGet('history'));
        $locationFilter = trim((string) $this->request->getGet('location'));
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));

        $validTypes = ['folder', 'document', 'spreadsheet', 'presentation', 'image', 'video', 'audio', 'archive', 'other'];
        if (!in_array($typeFilter, $validTypes, true)) $typeFilter = '';
        $validHistory = ['today', '7days', '30days', 'this_year', 'last_year', 'range'];
        if (!in_array($historyFilter, $validHistory, true)) $historyFilter = '';
        if ($locationFilter !== '' && (!ctype_digit($locationFilter) || ((int) $locationFilter > 0 && !isset($folderMap[(int) $locationFilter])))) $locationFilter = '';

        $extensionGroups = [
            'document' => ['pdf','doc','docx','odt','rtf','txt'],
            'spreadsheet' => ['xls','xlsx','csv','ods'],
            'presentation' => ['ppt','pptx','odp'],
            'image' => ['jpg','jpeg','png','gif','webp','bmp','svg','heic','tif','tiff'],
            'video' => ['mp4','m4v','mov','avi','mkv','webm','mpeg','mpg'],
            'audio' => ['mp3','wav','ogg','m4a','aac','flac'],
            'archive' => ['zip','rar','7z','tar','gz','bz2'],
        ];
        $matchesType = static function (array $item, string $type) use ($extensionGroups): bool {
            if ($type === '' ) return true;
            if ($type === 'folder') return false;
            $ext = strtolower((string) ($item['extension'] ?? pathinfo((string) ($item['original_name'] ?? ''), PATHINFO_EXTENSION)));
            $group = 'other';
            foreach ($extensionGroups as $name => $extensions) {
                if (in_array($ext, $extensions, true)) { $group = $name; break; }
            }
            return $group === $type;
        };
        $cutoff = null;
        $now = new \DateTimeImmutable('today');
        switch ($historyFilter) {
            case 'today': $cutoff = $now; break;
            case '7days': $cutoff = $now->modify('-6 days'); break;
            case '30days': $cutoff = $now->modify('-29 days'); break;
            case 'this_year': $cutoff = $now->setDate((int) $now->format('Y'), 1, 1); break;
            case 'last_year':
                $cutoff = $now->modify('-1 year')->setDate((int) $now->modify('-1 year')->format('Y'), 1, 1);
                $dateTo = $now->modify('-1 year')->setDate((int) $now->modify('-1 year')->format('Y'), 12, 31)->format('Y-m-d');
                break;
        }
        $dateValid = static function (array $item) use ($cutoff, $historyFilter, $dateFrom, $dateTo): bool {
            $raw = $item['trashed_at'] ?? $item['updated_at'] ?? null;
            if (!$raw) return false;
            try { $date = new \DateTimeImmutable((string) $raw); } catch (\Throwable $e) { return false; }
            if ($historyFilter !== 'range' && $cutoff && $date < $cutoff) return false;
            if ($historyFilter === 'last_year' && $date->format('Y-m-d') > $dateTo) return false;
            if ($historyFilter === 'range') {
                if ($dateFrom !== '' && $date->format('Y-m-d') < $dateFrom) return false;
                if ($dateTo !== '' && $date->format('Y-m-d') > $dateTo) return false;
            }
            return true;
        };
        $matchesLocation = static function (array $item) use ($locationFilter): bool {
            return $locationFilter === '' || (int) ($item['parent_id'] ?? 0) === (int) $locationFilter;
        };

        $files = array_values(array_filter($files, static fn(array $item): bool =>
            $typeFilter !== 'folder' && $matchesType($item, $typeFilter) && $dateValid($item) && $matchesLocation($item)
        ));
        if ($typeFilter !== '' && $typeFilter !== 'folder') $folders = [];
        if ($typeFilter === 'folder') $files = [];
        $folders = array_values(array_filter($folders, static fn(array $item): bool =>
            ($typeFilter === '' || $typeFilter === 'folder') && $dateValid($item) && $matchesLocation($item)
        ));

        // Folder choices include trashed parents so users can filter by the original location.
        $folderOptions = [];
        foreach ($allFolders as $folderOption) {
            $folderOptions[] = [
                'id' => (int) $folderOption['id'],
                'name' => $folderPath((int) $folderOption['id']),
            ];
        }
        usort($folderOptions, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        return view('drive/trash', [
            'title' => 'Sampah',
            'files' => $files,
            'folders' => $folders,
            'folderOptions' => $folderOptions,
            'filters' => ['type' => $typeFilter, 'history' => $historyFilter, 'location' => $locationFilter, 'date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }

    public function restoreFile(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
        if (!$file) return redirect()->back()->with('error', 'File di sampah tidak ditemukan.');
        if (!empty($file['parent_id'])) {
            $parent = (new DriveFolderModel())->where('id', (int) $file['parent_id'])->where('user_id', $uid)->where('is_trashed', 0)->first();
            if (!$parent) return redirect()->back()->with('error', 'Pulihkan folder induk terlebih dahulu sebelum memulihkan file ini.');
        }
        (new DriveFileModel())->update($id, ['is_trashed' => 0]);
        $this->log($uid, 'file.restore', 'Memulihkan file: ' . $file['original_name']);
        return redirect()->back()->with('message', 'File berhasil dipulihkan.');
    }

    public function restoreFolder(int $id)
    {
        $uid = (int) session()->get('user_id');
        $folder = (new DriveFolderModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
        if (!$folder) return redirect()->back()->with('error', 'Folder di sampah tidak ditemukan.');
        if (!empty($folder['parent_id'])) {
            $parent = (new DriveFolderModel())->where('id', (int) $folder['parent_id'])->where('user_id', $uid)->where('is_trashed', 0)->first();
            if (!$parent) return redirect()->back()->with('error', 'Pulihkan folder induk terlebih dahulu.');
        }
        (new DriveFolderModel())->update($id, ['is_trashed' => 0]);
        $this->log($uid, 'folder.restore', 'Memulihkan folder: ' . $folder['name']);
        return redirect()->back()->with('message', 'Folder berhasil dipulihkan. Pulihkan juga subfolder/file jika diperlukan.');
    }

    public function bulkDeletePermanently()
    {
        $uid = (int) session()->get('user_id');
        $fileIds = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('file_ids')), static fn($id) => $id > 0)));
        $folderIds = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('folder_ids')), static fn($id) => $id > 0)));
        if (!$fileIds && !$folderIds) return redirect()->back()->with('error', 'Pilih minimal satu item dari sampah.');

        $fileModel = new DriveFileModel();
        $folderModel = new DriveFolderModel();
        $db = db_connect();
        $deletedFiles = 0;
        $deletedFolders = 0;
        $db->transBegin();
        foreach ($fileIds as $id) {
            $file = $fileModel->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
            if (!$file) continue;
            $db->table('file_shares')->where('file_id', $id)->delete();
            $fileModel->delete($id);
            $db->table('users')->where('id', $uid)->set('used_bytes', 'GREATEST(used_bytes-' . (int) $file['size_bytes'] . ',0)', false)->update();
            if (is_file($file['storage_path'])) @unlink($file['storage_path']);
            $deletedFiles++;
        }
        foreach ($folderIds as $id) {
            $folder = $folderModel->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
            if (!$folder) continue;
            $this->purgeFolder($id, $uid);
            $deletedFolders++;
        }
        $db->transCommit();

        if ($deletedFiles || $deletedFolders) {
            $this->log($uid, 'bulk.delete_permanent', 'Menghapus permanen ' . $deletedFiles . ' file dan ' . $deletedFolders . ' folder beserta isinya dari sampah.');
            return redirect()->back()->with('message', 'Berhasil menghapus permanen ' . $deletedFiles . ' file dan ' . $deletedFolders . ' folder beserta isinya.');
        }
        return redirect()->back()->with('error', 'Item yang dipilih tidak ditemukan di sampah.');
    }

    public function bulkRestore()
    {
        $uid = (int) session()->get('user_id');
        $fileIds = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('file_ids')), static fn($id) => $id > 0)));
        $folderIds = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('folder_ids')), static fn($id) => $id > 0)));
        if (!$fileIds && !$folderIds) return redirect()->back()->with('error', 'Pilih minimal satu item dari sampah.');

        $folderModel = new DriveFolderModel();
        $fileModel = new DriveFileModel();
        $restoredFolders = 0;
        $restoredFiles = 0;

        // Restore folders first so selected files inside those folders can be restored too.
        foreach ($folderIds as $id) {
            $folder = $folderModel->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
            if (!$folder) continue;
            if (!empty($folder['parent_id'])) {
                $parent = (new DriveFolderModel())->where('id', (int) $folder['parent_id'])->where('user_id', $uid)->where('is_trashed', 0)->first();
                if (!$parent) continue;
            }
            $folderModel->update($id, ['is_trashed' => 0]);
            $this->log($uid, 'folder.restore', 'Memulihkan folder: ' . $folder['name']);
            $restoredFolders++;
        }

        foreach ($fileIds as $id) {
            $file = $fileModel->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
            if (!$file) continue;
            if (!empty($file['parent_id'])) {
                $parent = (new DriveFolderModel())->where('id', (int) $file['parent_id'])->where('user_id', $uid)->where('is_trashed', 0)->first();
                if (!$parent) continue;
            }
            $fileModel->update($id, ['is_trashed' => 0]);
            $this->log($uid, 'file.restore', 'Memulihkan file: ' . $file['original_name']);
            $restoredFiles++;
        }

        if ($restoredFolders || $restoredFiles) {
            return redirect()->back()->with('message', 'Berhasil memulihkan ' . $restoredFiles . ' file dan ' . $restoredFolders . ' folder.');
        }
        return redirect()->back()->with('error', 'Tidak ada item yang dapat dipulihkan. Pastikan folder induk sudah dipulihkan.');
    }

    public function deleteFilePermanently(int $id)
    {
        $uid = (int) session()->get('user_id');
        $db = db_connect();
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan di sampah.');
        $db->transBegin();
        $db->table('file_shares')->where('file_id', $id)->delete();
        (new DriveFileModel())->delete($id);
        $db->table('users')->where('id', $uid)->set('used_bytes', 'GREATEST(used_bytes-' . (int) $file['size_bytes'] . ',0)', false)->update();
        $db->transCommit();
        if (is_file($file['storage_path'])) @unlink($file['storage_path']);
        $this->log($uid, 'file.delete_permanent', 'Menghapus permanen file: ' . $file['original_name']);
        return redirect()->back()->with('message', 'File dihapus permanen.');
    }

    public function deleteFolderPermanently(int $id)
    {
        $uid = (int) session()->get('user_id');
        $folder = (new DriveFolderModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
        if (!$folder) return redirect()->back()->with('error', 'Folder tidak ditemukan di sampah.');
        $this->purgeFolder($id, $uid);
        $this->log($uid, 'folder.delete_permanent', 'Menghapus permanen folder: ' . $folder['name']);
        return redirect()->back()->with('message', 'Folder beserta isinya dihapus permanen.');
    }

    private function purgeFolder(int $folderId, int $uid): void
    {
        $folders = new DriveFolderModel();
        foreach ($folders->where('user_id', $uid)->where('parent_id', $folderId)->findAll() as $child) $this->purgeFolder((int) $child['id'], $uid);
        $db = db_connect();
        $files = (new DriveFileModel())->where('user_id', $uid)->where('parent_id', $folderId)->findAll();
        foreach ($files as $file) {
            $db->table('file_shares')->where('file_id', $file['id'])->delete();
            (new DriveFileModel())->delete((int) $file['id']);
            $db->table('users')->where('id', $uid)->set('used_bytes', 'GREATEST(used_bytes-' . (int) $file['size_bytes'] . ',0)', false)->update();
            if (is_file($file['storage_path'])) @unlink($file['storage_path']);
        }
        $folders->delete($folderId);
    }

    private function log(int $uid, string $action, string $details): void
    {
        db_connect()->table('activity_logs')->insert(['user_id'=>$uid,'action'=>$action,'details'=>$details,'ip_address'=>$this->request->getIPAddress(),'created_at'=>date('Y-m-d H:i:s')]);
    }
}
