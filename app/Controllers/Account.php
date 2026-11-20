<?php
namespace App\Controllers;
use App\Models\UserModel;
class Account extends BaseController
{
    public function password()
    {
        return view('account/password', ['title'=>'Ubah Password']);
    }
    public function updatePassword()
    {
        $rules=['current_password'=>'required','password'=>'required|min_length[12]','password_confirm'=>'required|matches[password]'];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        $users=new UserModel();
        $user=$users->find((int)session()->get('user_id'));
        if (!$user || !password_verify((string)$this->request->getPost('current_password'),$user['password_hash'])) {
            return redirect()->back()->with('error','Password saat ini tidak sesuai.');
        }
        $users->update((int)$user['id'],['password_hash'=>password_hash((string)$this->request->getPost('password'),PASSWORD_DEFAULT),'must_change_password'=>false]);
        session()->regenerate();
        return redirect()->to('/account/password')->with('message','Password berhasil diperbarui.');
    }
}
