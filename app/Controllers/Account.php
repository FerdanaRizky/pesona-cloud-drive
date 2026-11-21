<?php
namespace App\Controllers;
use App\Models\UserModel;
class Account extends BaseController
{
 public function profile(){
  $user=(new UserModel())->find((int)session()->get('user_id'));
  if(!$user)return redirect()->to('/login');
  return view('account/profile',['title'=>'Profil Pengguna','user'=>$user]);
 }
 public function updateProfile(){
  $uid=(int)session()->get('user_id'); $users=new UserModel(); $user=$users->find($uid);
  if(!$user)return redirect()->to('/login');
  $changes=[]; $details=[];
  if($this->request->getPost('name')!==null){
   $name=trim((string)$this->request->getPost('name'));
   if($name===''||mb_strlen($name)>100)return redirect()->to('/account/profile')->with('error','Nama lengkap wajib diisi dan maksimal 100 karakter.');
   $changes['name']=$name; $details[]='nama lengkap';
  }
  if($this->request->getPost('phone')!==null){
   $phone=preg_replace('/[\s+().-]/','',(string)$this->request->getPost('phone'));
   if(!preg_match('/^[1-9][0-9]{8,14}$/',$phone))return redirect()->to('/account/profile')->with('error','Nomor WhatsApp harus menggunakan format internasional, contoh 6281234567890.');
   $exists=(new UserModel())->where('phone',$phone)->where('id !=',$uid)->first();
   if($exists)return redirect()->to('/account/profile')->with('error','Nomor WhatsApp sudah digunakan akun lain.');
   $changes['phone']=$phone; $details[]='nomor WhatsApp';
  }
  if(!$changes)return redirect()->to('/account/profile');
  $users->update($uid,$changes);
  if(isset($changes['name']))session()->set('name',$changes['name']);
  db_connect()->table('activity_logs')->insert(['user_id'=>$uid,'action'=>'account.profile_update','details'=>'Memperbarui '.implode(' dan ',$details).' melalui profil.','ip_address'=>$this->request->getIPAddress(),'created_at'=>date('Y-m-d H:i:s')]);
  return redirect()->to('/account/profile')->with('message','Perubahan '.implode(' dan ',$details).' berhasil disimpan.');
 }
 public function password(){return view('account/password',['title'=>'Ubah Password']);}
 public function updatePassword(){
  $rules=['current_password'=>'required','password'=>'required|min_length[12]','password_confirm'=>'required|matches[password]'];
  if(!$this->validate($rules))return redirect()->to('/account/profile#ubah-password')->withInput()->with('errors',$this->validator->getErrors());
  $users=new UserModel(); $user=$users->find((int)session()->get('user_id'));
  if(!$user||!password_verify((string)$this->request->getPost('current_password'),$user['password_hash']))return redirect()->to('/account/profile#ubah-password')->with('error','Password saat ini tidak sesuai.');
  $users->update((int)$user['id'],['password_hash'=>password_hash((string)$this->request->getPost('password'),PASSWORD_DEFAULT),'password_changed_at'=>date('Y-m-d H:i:s'),'must_change_password'=>false]);
  session()->regenerate(); return redirect()->to('/account/profile#ubah-password')->with('message','Password berhasil diperbarui.');
 }
}