<?php
namespace App\Controllers;

use App\Models\DriveFileModel;
use App\Models\DriveFolderModel;

class Trash extends BaseController
{
    public function index()
    {
        $uid = (int) session()->get('user_id');
        return view('drive/trash', [
            'title' => 'Sampah',
            'files' => (new DriveFileModel())->where('user_id', $uid)->where('is_trashed', 1)->orderBy('updated_at', 'DESC')->findAll(),
            'folders' => (new DriveFolderModel())->where('user_id', $uid)->where('is_trashed', 1)->orderBy('updated_at', 'DESC')->findAll(),
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
