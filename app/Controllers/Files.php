<?php
namespace App\Controllers;
use App\Models\DriveFileModel;
class Files extends BaseController
{
    public function index()
    {
        $files = (new DriveFileModel())->where('user_id', (int)session()->get('user_id'))->where('is_trashed', 0)->orderBy('original_name')->findAll();
        return view('drive/files', ['title'=>'File Saya', 'files'=>$files]);
    }
    public function upload()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid() || $file->hasMoved()) return redirect()->back()->with('error', 'File upload tidak valid.');
        $uid = (int)session()->get('user_id');
        $db = db_connect();
        $user = $db->table('users')->where('id', $uid)->get()->getRowArray();
        $size = (int)$file->getSize();
        if (!$user || $size < 1 || ((int)$user['used_bytes'] + $size) > (int)$user['quota_bytes']) return redirect()->back()->with('error', 'Kuota penyimpanan tidak mencukupi.');
        $root = (string)env('storage.privatePath', WRITEPATH . 'private');
        $dir = rtrim($root, '/') . '/' . $uid . '/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) return redirect()->back()->with('error', 'Storage tidak dapat dibuat.');
        $stored = bin2hex(random_bytes(24));
        $file->move($dir, $stored);
        $path = $dir . '/' . $stored;
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        (new DriveFileModel())->insert(['user_id'=>$uid, 'original_name'=>basename($file->getClientName()), 'stored_name'=>$stored, 'mime_type'=>$mime, 'extension'=>strtolower($file->getClientExtension() ?: ''), 'size_bytes'=>$size, 'storage_path'=>$path]);
        $db->table('users')->where('id', $uid)->set('used_bytes', 'used_bytes+' . $size, false)->update();
        return redirect()->to('/files')->with('message', 'File berhasil diunggah.');
    }
    public function download(int $id)
    {
        $row = (new DriveFileModel())->where('id', $id)->where('user_id', (int)session()->get('user_id'))->where('is_trashed', 0)->first();
        if (!$row || !is_file($row['storage_path'])) return $this->response->setStatusCode(404);
        return $this->response->download($row['storage_path'], null)->setFileName($row['original_name']);
    }
    public function delete(int $id)
    {
        (new DriveFileModel())->where('id', $id)->where('user_id', (int)session()->get('user_id'))->set(['is_trashed'=>1])->update();
        return redirect()->to('/files')->with('message', 'File dipindahkan ke sampah.');
    }
}
