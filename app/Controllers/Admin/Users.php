<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Models\UserModel;
class Users extends BaseController
{
    public function index()
    {
        $users=(new UserModel())->orderBy('created_at','DESC')->findAll();
        return view('admin/users',['title'=>'Manajemen Pengguna','users'=>$users]);
    }
    public function create()
    {
        $rules=['name'=>'required|min_length[2]|max_length[120]','email'=>'required|valid_email|max_length[190]|is_unique[users.email]','password'=>'required|min_length[12]','quota_gb'=>'required|is_natural_no_zero|less_than_equal_to[10000]'];
        if(!$this->validate($rules)) return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        (new UserModel())->insert([
            'name'=>trim((string)$this->request->getPost('name')),
            'email'=>strtolower(trim((string)$this->request->getPost('email'))),
            'password_hash'=>password_hash((string)$this->request->getPost('password'),PASSWORD_DEFAULT),
            'role'=>'user','status'=>'active','quota_bytes'=>(int)$this->request->getPost('quota_gb')*1073741824,
            'used_bytes'=>0,'must_change_password'=>true
        ]);
        return redirect()->to('/admin/users')->with('message','Pengguna berhasil dibuat.');
    }
}
