<?php
namespace App\Controllers;
use App\Models\UserModel;
class Account extends BaseController
{
 public function profile(){ $user=(new UserModel())->find((int)session()->get('user_id')); return view('account/profile',['title'=>'Profil Pengguna','user'=>$user]); }
 public function updateProfile(){
  $phone=preg_replace('/[\s+().-]/','',(string)$this->request->getPost('phone'));
  if(!preg_match('/^[1-9][0-9]{8,14}$/',$phone))return redirect()->back()->withInput()->with('error','Nomor WhatsApp harus menggunakan format internasional, contoh 6281234567890.');
  $users=new UserModel(); $user=$users->find((int)session()->get('user_id'));
  if(!$user)return redirect()->to('/login');
  $exists=$users->where('phone',$phone)->where('id !=',(int)$user['id'])->first();
  if($exists)return redirect()->back()->withInput()->with('error','Nomor WhatsApp sudah digunakan akun lain.');
  $users->update((int)$user['id'],['phone'=>$phone]);
  db_connect()->table('activity_logs')->insert(['user_id'=>(int)$user['id'],'action'=>'account.phone_update','details'=>'Memperbarui nomor WhatsApp profil.','ip_address'=>$this->request->getIPAddress(),'created_at'=>date('Y-m-d H:i:s')]);
  return redirect()->to('/account/profile')->with('message','Nomor WhatsApp berhasil diperbarui.');
 }
 public function password(){ return view('account/password',['title'=>'Ubah Password']); }
 public function updatePassword(){
  $rules=['current_password'=>'required','password'=>'required|min_length[12]','password_confirm'=>'required|matches[password]'];
  if(!$this->validate($rules))return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
  $users=new UserModel(); $user=$users->find((int)session()->get('user_id'));
  if(!$user||!password_verify((string)$this->request->getPost('current_password'),$user['password_hash']))return redirect()->back()->with('error','Password saat ini tidak sesuai.');
  $users->update((int)$user['id'],['password_hash'=>password_hash((string)$this->request->getPost('password'),PASSWORD_DEFAULT),'must_change_password'=>false]);
  session()->regenerate(); return redirect()->to('/account/password')->with('message','Password berhasil diperbarui.');
 }
}