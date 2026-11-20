<?php
namespace App\Controllers;

use App\Models\DriveFileModel;
use App\Models\DriveFolderModel;

class Files extends BaseController
{
    public function index()
    {
        $uid = (int) session()->get('user_id');
        $folderId = (int) $this->request->getGet('folder');
        $folders = new DriveFolderModel();
        $current = null;

        if ($folderId > 0) {
            $current = $folders->where('id', $folderId)->where('user_id', $uid)->where('is_trashed', 0)->first();
            if (!$current) {
                return redirect()->to('/files')->with('error', 'Folder tidak ditemukan.');
            }
        }

        $folderQuery = $folders->where('user_id', $uid)->where('is_trashed', 0);
        $folderId > 0 ? $folderQuery->where('parent_id', $folderId) : $folderQuery->where('parent_id', null);
        $childFolders = $folderQuery->orderBy('name', 'ASC')->findAll();

        $fileQuery = (new DriveFileModel())->where('user_id', $uid)->where('is_trashed', 0);
        $folderId > 0 ? $fileQuery->where('parent_id', $folderId) : $fileQuery->where('parent_id', null);
        $files = $fileQuery->orderBy('original_name', 'ASC')->findAll();

        $breadcrumbs = [];
        $cursor = $current;
        while ($cursor) {
            array_unshift($breadcrumbs, $cursor);
            $cursor = !empty($cursor['parent_id'])
                ? $folders->where('id', (int) $cursor['parent_id'])->where('user_id', $uid)->first()
                : null;
        }

        $user = db_connect()->table('users')->where('id', $uid)->get()->getRowArray();
        return view('drive/files', [
            'title' => 'File Saya',
            'files' => $files,
            'folders' => $childFolders,
            'currentFolder' => $current,
            'breadcrumbs' => $breadcrumbs,
            'user' => $user,
        ]);
    }

    public function createFolder()
    {
        $uid = (int) session()->get('user_id');
        $name = trim((string) $this->request->getPost('name'));
        $parentId = (int) $this->request->getPost('parent_id');
        if ($name === '' || mb_strlen($name) > 120 || str_contains($name, '/') || str_contains($name, "\\\\")) {
            return redirect()->back()->with('error', 'Nama folder tidak valid (maksimal 120 karakter dan tanpa slash).');
        }
        if ($parentId > 0 && !(new DriveFolderModel())->where('id', $parentId)->where('user_id', $uid)->where('is_trashed', 0)->first()) {
            return redirect()->back()->with('error', 'Folder induk tidak valid.');
        }

        (new DriveFolderModel())->insert([
            'user_id' => $uid,
            'parent_id' => $parentId ?: null,
            'name' => $name,
            'is_trashed' => 0,
        ]);
        return redirect()->to($parentId > 0 ? '/files?folder=' . $parentId : '/files')->with('message', 'Folder berhasil dibuat.');
    }

    public function upload()
    {
        $uid = (int) session()->get('user_id');
        $folderId = (int) $this->request->getPost('parent_id');
        if ($folderId > 0 && !(new DriveFolderModel())->where('id', $folderId)->where('user_id', $uid)->where('is_trashed', 0)->first()) {
            return redirect()->back()->with('error', 'Folder tujuan tidak valid.');
        }

        $uploads = $this->request->getFileMultiple('files') ?: [];
        if (!$uploads) {
            $single = $this->request->getFile('file');
            $uploads = $single ? [$single] : [];
        }
        if (!$uploads) {
            return redirect()->back()->with('error', 'Pilih minimal satu file.');
        }

        $root = rtrim((string) env('storage.privatePath', WRITEPATH . 'private'), '/');
        $dir = $root . '/' . $uid . '/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            return redirect()->back()->with('error', 'Direktori penyimpanan tidak dapat dibuat.');
        }

        $success = 0;
        $errors = [];
        $db = db_connect();
        foreach ($uploads as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved() || $file->getSize() < 1) {
                $errors[] = 'Ada file yang tidak valid atau kosong.';
                continue;
            }

            $size = (int) $file->getSize();
            $db->transBegin();
            $user = $db->query('SELECT quota_bytes, used_bytes FROM users WHERE id = ? FOR UPDATE', [$uid])->getRowArray();
            if (!$user || ((int) $user['used_bytes'] + $size) > (int) $user['quota_bytes']) {
                $db->transRollback();
                $errors[] = 'Kuota tidak cukup untuk ' . basename($file->getClientName()) . '.';
                continue;
            }

            $stored = bin2hex(random_bytes(24));
            try {
                $file->move($dir, $stored);
                $path = $dir . '/' . $stored;
                $mime = (new \\finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
                $model = new DriveFileModel();
                $model->insert([
                    'user_id' => $uid,
                    'parent_id' => $folderId ?: null,
                    'original_name' => basename($file->getClientName()),
                    'stored_name' => $stored,
                    'mime_type' => $mime,
                    'extension' => strtolower($file->getClientExtension() ?: ''),
                    'size_bytes' => $size,
                    'storage_path' => $path,
                ]);
                $db->table('users')->where('id', $uid)->set('used_bytes', 'used_bytes+' . $size, false)->update();
                $db->transCommit();
                $success++;
            } catch (\\Throwable $e) {
                $db->transRollback();
                if (isset($path) && is_file($path)) {
                    @unlink($path);
                }
                log_message('error', 'Cloud Drive upload failed: {message}', ['message' => $e->getMessage()]);
                $errors[] = 'Gagal menyimpan ' . basename($file->getClientName()) . '.';
            }
        }

        $target = $folderId > 0 ? '/files?folder=' . $folderId : '/files';
        if ($success > 0 && !$errors) {
            return redirect()->to($target)->with('message', $success . ' file berhasil diunggah.');
        }
        if ($success > 0) {
            return redirect()->to($target)->with('error', $success . ' file berhasil diunggah. ' . implode(' ', $errors));
        }
        return redirect()->to($target)->with('error', implode(' ', $errors) ?: 'Tidak ada file yang berhasil diunggah.');
    }

    public function download(int $id)
    {
        $row = (new DriveFileModel())->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->first();
        if (!$row || !is_file($row['storage_path'])) {
            return $this->response->setStatusCode(404);
        }
        return $this->response->download($row['storage_path'], null)->setFileName($row['original_name']);
    }

    public function rename(int $id)
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '' || mb_strlen($name) > 255 || str_contains($name, '/') || str_contains($name, "\\\\")) {
            return redirect()->back()->with('error', 'Nama file tidak valid.');
        }
        (new DriveFileModel())->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->set(['original_name' => $name])->update();
        return redirect()->back()->with('message', 'Nama file diperbarui.');
    }

    public function move(int $id)
    {
        $uid = (int) session()->get('user_id');
        $parentId = (int) $this->request->getPost('parent_id');
        if ($parentId > 0 && !(new DriveFolderModel())->where('id', $parentId)->where('user_id', $uid)->where('is_trashed', 0)->first()) {
            return redirect()->back()->with('error', 'Folder tujuan tidak valid.');
        }
        (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->set(['parent_id' => $parentId ?: null])->update();
        return redirect()->back()->with('message', 'File dipindahkan.');
    }

    public function toggleStar(int $id)
    {
        $model = new DriveFileModel();
        $file = $model->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->first();
        if (!$file) {
            return redirect()->back()->with('error', 'File tidak ditemukan.');
        }
        $model->update($id, ['is_starred' => empty($file['is_starred']) ? 1 : 0]);
        return redirect()->back()->with('message', 'Status favorit diperbarui.');
    }

    public function delete(int $id)
    {
        $model = new DriveFileModel();
        $file = $model->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->first();
        if ($file) {
            $model->update($id, ['is_trashed' => 1]);
        }
        return redirect()->back()->with('message', 'File dipindahkan ke sampah.');
    }

    public function renameFolder(int $id)
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '' || mb_strlen($name) > 120 || str_contains($name, '/') || str_contains($name, "\\\\")) {
            return redirect()->back()->with('error', 'Nama folder tidak valid.');
        }
        (new DriveFolderModel())->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->set(['name' => $name])->update();
        return redirect()->back()->with('message', 'Nama folder diperbarui.');
    }

    public function deleteFolder(int $id)
    {
        $uid = (int) session()->get('user_id');
        $folder = (new DriveFolderModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$folder) {
            return redirect()->back()->with('error', 'Folder tidak ditemukan.');
        }
        $hasFolders = (new DriveFolderModel())->where('user_id', $uid)->where('parent_id', $id)->where('is_trashed', 0)->countAllResults() > 0;
        $hasFiles = (new DriveFileModel())->where('user_id', $uid)->where('parent_id', $id)->where('is_trashed', 0)->countAllResults() > 0;
        if ($hasFolders || $hasFiles) {
            return redirect()->back()->with('error', 'Folder harus kosong sebelum dihapus. Pindahkan atau hapus isinya terlebih dahulu.');
        }
        (new DriveFolderModel())->update($id, ['is_trashed' => 1]);
        return redirect()->to('/files')->with('message', 'Folder dipindahkan ke sampah.');
    }
}
