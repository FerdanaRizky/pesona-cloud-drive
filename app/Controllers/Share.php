<?php
namespace App\Controllers;

use App\Models\DriveFileModel;

class Share extends BaseController
{
    public function create(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan.');
        $token = bin2hex(random_bytes(32));
        $expiry = $this->request->getPost('expires_days');
        $expiresAt = in_array((string)$expiry, ['1','7','30'], true) ? date('Y-m-d H:i:s', strtotime('+' . (int)$expiry . ' days')) : null;
        db_connect()->table('file_shares')->insert([
            'file_id'=>$id, 'owner_id'=>$uid, 'shared_with_email'=>null,
            'token_hash'=>hash('sha256', $token), 'permission'=>'viewer',
            'expires_at'=>$expiresAt, 'created_at'=>date('Y-m-d H:i:s')
        ]);
        db_connect()->table('activity_logs')->insert(['user_id'=>$uid,'action'=>'file.share','details'=>'Membuat tautan berbagi: '.$file['original_name'],'ip_address'=>$this->request->getIPAddress(),'created_at'=>date('Y-m-d H:i:s')]);
        $url = site_url('s/' . $token);
        return redirect()->back()->with('share_link', $url)->with('message', 'Tautan berbagi berhasil dibuat.');
    }

    public function revoke(int $id)
    {
        $uid = (int) session()->get('user_id');
        db_connect()->table('file_shares')->where('file_id', $id)->where('owner_id', $uid)->delete();
        db_connect()->table('activity_logs')->insert(['user_id'=>$uid,'action'=>'file.share_revoke','details'=>'Mencabut tautan berbagi file #'.$id,'ip_address'=>$this->request->getIPAddress(),'created_at'=>date('Y-m-d H:i:s')]);
        return redirect()->back()->with('message', 'Tautan berbagi dicabut.');
    }

    public function open(string $token)
    {
        $share = db_connect()->table('file_shares s')->select('s.*, f.original_name, f.mime_type, f.size_bytes, f.storage_path, f.is_trashed')->join('drive_files f', 'f.id=s.file_id')->where('s.token_hash', hash('sha256', $token))->get()->getRowArray();
        if (!$share || !empty($share['is_trashed']) || ($share['expires_at'] && strtotime($share['expires_at']) < time()) || !is_file($share['storage_path'])) {
            return $this->response->setStatusCode(404)->setBody('Tautan tidak tersedia atau sudah kedaluwarsa.');
        }
        return $this->response->download($share['storage_path'], null)->setFileName($share['original_name']);
    }
}
