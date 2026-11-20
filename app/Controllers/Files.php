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
        $term = trim((string) $this->request->getGet('q'));
        $term = mb_substr($term, 0, 120);
        $folders = new DriveFolderModel();
        $current = null;

        if ($folderId > 0) {
            $current = $folders->where('id', $folderId)->where('user_id', $uid)->where('is_trashed', 0)->first();
            if (!$current) return redirect()->to('/files')->with('error', 'Folder tidak ditemukan.');
        }

        $folderQuery = $folders->where('user_id', $uid)->where('is_trashed', 0);
        $folderId > 0 ? $folderQuery->where('parent_id', $folderId) : $folderQuery->where('parent_id', null);
        if ($term !== '') $folderQuery->like('name', $term);
        $childFolders = $folderQuery->orderBy('name', 'ASC')->findAll(200);

        $fileQuery = (new DriveFileModel())->where('user_id', $uid)->where('is_trashed', 0);
        $folderId > 0 ? $fileQuery->where('parent_id', $folderId) : $fileQuery->where('parent_id', null);
        if ($term !== '') $fileQuery->like('original_name', $term);
        $files = $fileQuery->orderBy('original_name', 'ASC')->findAll(200);

        $breadcrumbs = [];
        $cursor = $current;
        while ($cursor) {
            array_unshift($breadcrumbs, $cursor);
            $cursor = !empty($cursor['parent_id'])
                ? $folders->where('id', (int) $cursor['parent_id'])->where('user_id', $uid)->where('is_trashed', 0)->first()
                : null;
        }

        $allFolders = (new DriveFolderModel())->where('user_id', $uid)->where('is_trashed', 0)->orderBy('name', 'ASC')->findAll(500);
        $user = db_connect()->table('users')->where('id', $uid)->get()->getRowArray();
        return view('drive/files', [
            'title' => 'File Saya', 'files' => $files, 'folders' => $childFolders,
            'currentFolder' => $current, 'breadcrumbs' => $breadcrumbs, 'user' => $user, 'term' => $term, 'allFolders' => $allFolders,
        ]);
    }

    public function createFolder()
    {
        $uid = (int) session()->get('user_id');
        $name = trim((string) $this->request->getPost('name'));
        $parentId = (int) $this->request->getPost('parent_id');
        if ($name === '' || mb_strlen($name) > 120 || str_contains($name, '/') || str_contains($name, chr(92))) {
            return redirect()->back()->with('error', 'Nama folder tidak valid (maksimal 120 karakter dan tanpa slash).');
        }
        if ($parentId > 0 && !(new DriveFolderModel())->where('id', $parentId)->where('user_id', $uid)->where('is_trashed', 0)->first()) {
            return redirect()->back()->with('error', 'Folder induk tidak valid.');
        }

        (new DriveFolderModel())->insert(['user_id' => $uid, 'parent_id' => $parentId ?: null, 'name' => $name, 'is_trashed' => 0]);
        $this->logActivity($uid, 'folder.create', 'Membuat folder: ' . $name);
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
        if (!$uploads) return redirect()->back()->with('error', 'Pilih minimal satu file.');

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
            $path = null;
            try {
                $file->move($dir, $stored);
                $path = $dir . '/' . $stored;
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
                $model = new DriveFileModel();
                $inserted = $model->insert([
                    'user_id' => $uid, 'parent_id' => $folderId ?: null,
                    'original_name' => basename($file->getClientName()), 'stored_name' => $stored,
                    'mime_type' => $mime, 'extension' => strtolower($file->getClientExtension() ?: ''),
                    'size_bytes' => $size, 'storage_path' => $path,
                ]);
                if (!$inserted) throw new \RuntimeException('Metadata file gagal disimpan.');
                $db->table('users')->where('id', $uid)->set('used_bytes', 'used_bytes+' . $size, false)->update();
                $db->transCommit();
                $success++;
                $this->logActivity($uid, 'file.upload', 'Mengunggah file: ' . basename($file->getClientName()));
            } catch (\Throwable $e) {
                $db->transRollback();
                if ($path && is_file($path)) @unlink($path);
                log_message('error', 'Cloud Drive upload failed: {message}', ['message' => $e->getMessage()]);
                $errors[] = 'Gagal menyimpan ' . basename($file->getClientName()) . '.';
            }
        }

        $target = $folderId > 0 ? '/files?folder=' . $folderId : '/files';
        if ($success > 0 && !$errors) return redirect()->to($target)->with('message', $success . ' file berhasil diunggah.');
        if ($success > 0) return redirect()->to($target)->with('error', $success . ' file berhasil diunggah. ' . implode(' ', $errors));
        return redirect()->to($target)->with('error', implode(' ', $errors) ?: 'Tidak ada file yang berhasil diunggah.');
    }

    public function download(int $id)
    {
        $row = (new DriveFileModel())->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->first();
        if (!$row || !is_file($row['storage_path'])) return $this->response->setStatusCode(404);
        $this->logActivity((int) session()->get('user_id'), 'file.download', 'Mengunduh file: ' . $row['original_name']);
        $accelerated = \\App\\Libraries\\PrivateFileDelivery::response($this->response, $row['storage_path'], $row['mime_type'], $row['original_name'], 'attachment');
        if ($accelerated !== null) return $accelerated;
        return $this->response->download($row['storage_path'], null)->setFileName($row['original_name']);
    }

    public function rename(int $id)
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '' || mb_strlen($name) > 255 || str_contains($name, '/') || str_contains($name, chr(92))) {
            return redirect()->back()->with('error', 'Nama file tidak valid.');
        }
        (new DriveFileModel())->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->set(['original_name' => $name])->update();
        $this->logActivity((int) session()->get('user_id'), 'file.rename', 'Mengubah nama file menjadi: ' . $name);
        return redirect()->back()->with('message', 'Nama file diperbarui.');
    }

    public function move(int $id)
    {
        $uid = (int) session()->get('user_id');
        $parentId = (int) $this->request->getPost('parent_id');
        if ($parentId > 0 && !(new DriveFolderModel())->where('id', $parentId)->where('user_id', $uid)->where('is_trashed', 0)->first()) {
            return redirect()->back()->with('error', 'Folder tujuan tidak valid.');
        }
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan.');
        (new DriveFileModel())->update($id, ['parent_id' => $parentId ?: null]);
        $this->logActivity($uid, 'file.move', 'Memindahkan file: ' . $file['original_name']);
        return redirect()->back()->with('message', 'File dipindahkan.');
    }

    public function toggleStar(int $id)
    {
        $model = new DriveFileModel();
        $file = $model->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan.');
        $model->update($id, ['is_starred' => empty($file['is_starred']) ? 1 : 0]);
        $this->logActivity((int) session()->get('user_id'), 'file.star', 'Mengubah favorit: ' . $file['original_name']);
        return redirect()->back()->with('message', 'Status favorit diperbarui.');
    }

    public function delete(int $id)
    {
        $model = new DriveFileModel();
        $file = $model->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->first();
        if ($file) {
            $model->update($id, ['is_trashed' => 1]);
            $this->logActivity((int) session()->get('user_id'), 'file.trash', 'Memindahkan ke sampah: ' . $file['original_name']);
        }
        return redirect()->back()->with('message', 'File dipindahkan ke sampah.');
    }

    public function renameFolder(int $id)
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '' || mb_strlen($name) > 120 || str_contains($name, '/') || str_contains($name, chr(92))) {
            return redirect()->back()->with('error', 'Nama folder tidak valid.');
        }
        $folder = (new DriveFolderModel())->where('id', $id)->where('user_id', (int) session()->get('user_id'))->where('is_trashed', 0)->first();
        if (!$folder) return redirect()->back()->with('error', 'Folder tidak ditemukan.');
        (new DriveFolderModel())->update($id, ['name' => $name]);
        $this->logActivity((int) session()->get('user_id'), 'folder.rename', 'Mengubah nama folder menjadi: ' . $name);
        return redirect()->back()->with('message', 'Nama folder diperbarui.');
    }

    public function deleteFolder(int $id)
    {
        $uid = (int) session()->get('user_id');
        $folder = (new DriveFolderModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$folder) return redirect()->back()->with('error', 'Folder tidak ditemukan.');
        $hasFolders = (new DriveFolderModel())->where('user_id', $uid)->where('parent_id', $id)->where('is_trashed', 0)->countAllResults() > 0;
        $hasFiles = (new DriveFileModel())->where('user_id', $uid)->where('parent_id', $id)->where('is_trashed', 0)->countAllResults() > 0;
        if ($hasFolders || $hasFiles) return redirect()->back()->with('error', 'Folder harus kosong sebelum dihapus. Pindahkan atau hapus isinya terlebih dahulu.');
        (new DriveFolderModel())->update($id, ['is_trashed' => 1]);
        $this->logActivity($uid, 'folder.trash', 'Memindahkan folder ke sampah: ' . $folder['name']);
        return redirect()->to('/files')->with('message', 'Folder dipindahkan ke sampah.');
    }

    public function search()
    {
        $uid = (int) session()->get('user_id');
        $term = trim((string) $this->request->getGet('q'));
        $files = [];
        $folders = [];

        if ($term !== '' && mb_strlen($term) <= 120) {
            $files = (new DriveFileModel())
                ->where('user_id', $uid)->where('is_trashed', 0)
                ->like('original_name', $term)->orderBy('original_name', 'ASC')->findAll(200);
            $folders = (new DriveFolderModel())
                ->where('user_id', $uid)->where('is_trashed', 0)
                ->like('name', $term)->orderBy('name', 'ASC')->findAll(200);
        }

        return view('drive/search', [
            'title' => 'Cari File',
            'term' => mb_substr($term, 0, 120),
            'files' => $files,
            'folders' => $folders,
        ]);
    }

    public function preview(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$file || !is_file($file['storage_path'])) {
            return $this->response->setStatusCode(404);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'video/mp4', 'video/webm', 'video/ogg'];
        $this->logActivity($uid, 'file.preview', 'Membuka preview file: ' . $file['original_name']);
        $officePreview = false;
        if (!in_array($file['mime_type'], $allowed, true)) {
            if (!\App\Libraries\OfficePreview::supports($file)) {
                return redirect()->to('/files')->with('error', 'Preview tidak tersedia untuk tipe file ini.');
            }
            $officePreview = true;
            if (!\App\Libraries\OfficePreview::convert($file)) {
                return redirect()->to('/files')->with('error', 'Preview Office gagal dibuat. Pastikan LibreOffice terpasang di server.');
            }
        }

        return view('drive/preview', ['title' => 'Preview - ' . $file['original_name'], 'file' => $file, 'officePreview' => $officePreview]);
    }

    public function previewContent(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$file || !is_file($file['storage_path'])) return $this->response->setStatusCode(404);

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'video/mp4', 'video/webm', 'video/ogg'];
        $sourcePath = $file['storage_path'];
        $contentType = $file['mime_type'];
        if (!in_array($contentType, $allowed, true)) {
            if (!\App\Libraries\OfficePreview::supports($file)) return $this->response->setStatusCode(415);
            $sourcePath = \App\Libraries\OfficePreview::convert($file);
            if (!$sourcePath) return $this->response->setStatusCode(503)->setBody('Office preview is not available. Install LibreOffice.');
            $contentType = 'application/pdf';
        }

        $accelerated = \\App\\Libraries\\PrivateFileDelivery::response($this->response, $sourcePath, $contentType, $file['original_name'], 'inline');
        if ($accelerated !== null) {
            return $accelerated->setHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }

        $name = str_replace(["\\r", "\\n", '"'], '', basename($file['original_name']));
        $body = file_get_contents($sourcePath);
        if ($body === false) return $this->response->setStatusCode(404);

        return $this->response
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Disposition', 'inline; filename="' . addslashes($name) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox")
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody($body);
    }

    protected function logActivity(int $uid, string $action, string $details): void
    {
        db_connect()->table('activity_logs')->insert([
            'user_id' => $uid,
            'action' => $action,
            'details' => mb_substr($details, 0, 2000),
            'ip_address' => $this->request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

}
