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
        (new DriveFileModel())->update($id, ['is_trashed' => 0]);
        $this->log($uid, 'file.restore', 'Memulihkan file: ' . $file['original_name']);
        return redirect()->back()->with('message', 'File berhasil dipulihkan.');
    }

    public function restoreFolder(int $id)
    {
        $uid = (int) session()->get('user_id');
        $folder = (new DriveFolderModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 1)->first();
        if (!$folder) return redirect()->back()->with('error', 'Folder di sampah tidak ditemukan.');
        (new DriveFolderModel())->update($id, ['is_trashed' => 0]);
        $this->log($uid, 'folder.restore', 'Memulihkan folder: ' . $folder['name']);
        return redirect()->back()->with('message', 'Folder berhasil dipulihkan. Pulihkan juga subfolder/file jika diperlukan.');
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
