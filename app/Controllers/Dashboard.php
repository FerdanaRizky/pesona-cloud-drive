<?php
namespace App\Controllers;
use App\Models\DriveFileModel;
class Dashboard extends BaseController
{
    public function index()
    {
        $uid = (int)session()->get('user_id');
        $user = db_connect()->table('users')->where('id', $uid)->get()->getRowArray();
        $files = (new DriveFileModel())->where('user_id', $uid)->where('is_trashed', 0)->orderBy('created_at', 'DESC')->findAll(8);
        return view('drive/dashboard', ['title'=>'Dashboard', 'user'=>$user, 'files'=>$files]);
    }
}
