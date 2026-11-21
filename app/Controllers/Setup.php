<?php
namespace App\Controllers;
use App\Models\UserModel;
class Setup extends BaseController
{
    public function index()
    {
        if ((new UserModel())->countAllResults() > 0) return redirect()->to('/login');
        return view('auth/setup', ['title'=>'Setup Administrator']);
    }
    public function create()
    {
        $users = new UserModel();
        if ($users->countAllResults() > 0) return redirect()->to('/login');
        $rules = [
            'name'=>'required|min_length[2]|max_length[120]',
            'email'=>'required|valid_email|max_length[190]|is_unique[users.email]',
            'phone'=>'required|min_length[9]|max_length[20]',
            'password'=>'required|min_length[12]',
            'password_confirm'=>'required|matches[password]'
        ];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        $phone = preg_replace('/[\\s+().-]/', '', (string) $this->request->getPost('phone'));
        if (!preg_match('/^[1-9][0-9]{8,14}$/', $phone)) return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp tidak valid. Gunakan format internasional, contoh 6281234567890.');
        if (db_connect()->table('users')->where('phone', $phone)->countAllResults() > 0) return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp sudah digunakan pengguna lain.');
        $users->insert([
            'name'=>trim((string)$this->request->getPost('name')),
            'email'=>strtolower(trim((string)$this->request->getPost('email'))),
            'phone'=>$phone,
            'password_hash'=>password_hash((string)$this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'=>'admin','status'=>'active','quota_bytes'=>107374182400,'used_bytes'=>0,'must_change_password'=>false
        ]);
        return redirect()->to('/login')->with('message','Administrator berhasil dibuat. Silakan login.');
    }
}
