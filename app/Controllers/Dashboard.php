<?php
namespace App\Controllers;

use App\Models\DriveFileModel;
use App\Models\DriveFolderModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $uid = (int) session()->get('user_id');
        $db = db_connect();
        $user = $db->table('users')->where('id', $uid)->get()->getRowArray();

        $folders = (new DriveFolderModel())
            ->where('user_id', $uid)->where('is_trashed', 0)
            ->orderBy('updated_at', 'DESC')->orderBy('name', 'ASC')
            ->findAll(8);

        $files = (new DriveFileModel())
            ->where('user_id', $uid)->where('is_trashed', 0)
            ->orderBy('updated_at', 'DESC')->orderBy('created_at', 'DESC')
            ->findAll(12);

        return view('drive/dashboard', [
            'title' => 'Beranda',
            'user' => $user,
            'folders' => $folders,
            'files' => $files,
        ]);
    }
}
