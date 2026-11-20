<?php
namespace App\Controllers;
use App\Models\UserModel;
use App\Services\GowaService;
class PasswordReset extends BaseController
{
 public function requestForm(){ if(session()->get('user_id'))return redirect()->to('/dashboard'); return view('auth/forgot_password',['title'=>'Lupa Password - Pesona Cloud Drive']); }
 public function sendLink(){
  if(session()->get('user_id'))return redirect()->to('/dashboard');
  $email=strtolower(trim((string)$this->request->getPost('email')));
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)return redirect()->back()->withInput()->with('error','Masukkan email yang valid.');
  $db=db_connect(); $ip=$this->request->getIPAddress(); $now=date('Y-m-d H:i:s');
  $user=(new UserModel())->where('email',$email)->where('status','active')->first();
  if(!$user)return redirect()->to('/forgot-password')->withInput()->with('error','Akun aktif dengan email tersebut tidak ditemukan. Periksa kembali alamat email.');
  if(empty($user['phone']))return redirect()->to('/forgot-password')->withInput()->with('error','Nomor WhatsApp belum terdaftar pada profil akun. Hubungi administrator untuk memperbarui nomor WhatsApp.');
  $last=$db->table('password_reset_tokens')->where('user_id',(int)$user['id'])->where('created_at >=',date('Y-m-d H:i:s',time()-60))->countAllResults();
  if($last>0)return redirect()->to('/forgot-password')->withInput()->with('error','Kode OTP baru saja diminta. Tunggu 60 detik sebelum meminta kode lagi.');
  $hourly=$db->table('password_reset_tokens')->groupStart()->where('email',$email)->orWhere('ip_address',$ip)->groupEnd()->where('created_at >=',date('Y-m-d H:i:s',time()-3600))->countAllResults();
  if($hourly>=5)return redirect()->to('/forgot-password')->withInput()->with('error','Batas permintaan OTP tercapai. Coba kembali setelah satu jam.');
  $otp=(string)random_int(100000,999999);
  $db->table('password_reset_tokens')->where('user_id',(int)$user['id'])->where('used_at',null)->update(['used_at'=>$now]);
  $db->table('password_reset_tokens')->insert(['user_id'=>(int)$user['id'],'email'=>$email,'token_hash'=>hash('sha256',$otp.':'.(int)$user['id']),'expires_at'=>date('Y-m-d H:i:s',time()+300),'used_at'=>null,'ip_address'=>$ip,'created_at'=>$now,'attempts'=>0]);
  $message="Pesona Cloud Drive\n\nKode OTP reset password Anda: {$otp}\nBerlaku 5 menit. Jangan berikan kode ini kepada siapa pun. Jika Anda tidak meminta reset password, abaikan pesan ini.";
  if((new GowaService())->sendText((string)$user['phone'],$message))return redirect()->to('/reset-password')->with('reset_email',$email)->with('message','Kode OTP berhasil dikirim ke WhatsApp yang terdaftar. Periksa pesan WhatsApp Anda.');
  $db->table('password_reset_tokens')->where('user_id',(int)$user['id'])->where('used_at',null)->update(['used_at'=>$now]);
  log_message('error','Password reset OTP delivery failed for user id {id}; check GOWA credentials, device id and API response.',['id'=>$user['id']]);
  return redirect()->to('/forgot-password')->withInput()->with('error','Kode OTP gagal dikirim. Periksa konfigurasi GOWA dan pastikan perangkat billing sedang terhubung, lalu coba lagi.');
 }
 public function resetForm(){
  if(session()->get('user_id'))return redirect()->to('/dashboard');
  $email=(string)session()->getFlashdata('reset_email');
  if($email!=='')session()->set('reset_email',$email); else $email=(string)session()->get('reset_email');
  return view('auth/reset_password',['title'=>'Reset Password - Pesona Cloud Drive','validToken'=>$email!=='','email'=>$email]);
 }
 public function updatePassword(){
  $email=strtolower(trim((string)$this->request->getPost('email'))); $otp=trim((string)$this->request->getPost('otp'));
  $password=(string)$this->request->getPost('password'); $confirm=(string)$this->request->getPost('password_confirm');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[0-9]{6}$/',$otp))return redirect()->to('/forgot-password')->with('error','Kode OTP tidak valid.');
  if(strlen($password)<12||strlen($password)>200)return redirect()->to('/reset-password')->with('reset_email',$email)->with('error','Password minimal 12 karakter.');
  if($password!==$confirm)return redirect()->to('/reset-password')->with('reset_email',$email)->with('error','Konfirmasi password tidak sama.');
  $db=db_connect(); $user=(new UserModel())->where('email',$email)->where('status','active')->first();
  if(!$user)return redirect()->to('/forgot-password')->with('error','Kode OTP tidak valid atau kedaluwarsa.');
  $record=$db->table('password_reset_tokens')->where('user_id',(int)$user['id'])->where('used_at',null)->where('expires_at >',date('Y-m-d H:i:s'))->orderBy('id','DESC')->get()->getRowArray();
  if(!$record || (int)$record['attempts']>=5)return redirect()->to('/forgot-password')->with('error','Kode OTP tidak valid atau kedaluwarsa. Minta kode baru.');
  $hash=hash('sha256',$otp.':'.(int)$user['id']);
  if(!hash_equals((string)$record['token_hash'],$hash)){
   $db->table('password_reset_tokens')->where('id',(int)$record['id'])->set('attempts','attempts+1',false)->update();
   return redirect()->to('/reset-password')->with('reset_email',$email)->with('error','Kode OTP salah. Periksa kembali kode Anda.');
  }
  $db->transStart();
  (new UserModel())->update((int)$user['id'],['password_hash'=>password_hash($password,PASSWORD_DEFAULT),'password_changed_at'=>date('Y-m-d H:i:s'),'must_change_password'=>false]);
  $db->table('password_reset_tokens')->where('user_id',(int)$user['id'])->where('used_at',null)->update(['used_at'=>date('Y-m-d H:i:s')]);
  $db->table('activity_logs')->insert(['user_id'=>(int)$user['id'],'action'=>'auth.password_reset','details'=>'Password direset melalui OTP WhatsApp.','ip_address'=>$this->request->getIPAddress(),'created_at'=>date('Y-m-d H:i:s')]);
  $db->transComplete();
  if(!$db->transStatus())return redirect()->to('/forgot-password')->with('error','Password belum berhasil diperbarui.');
  session()->remove('reset_email');
  return redirect()->to('/login')->with('message','Password berhasil diperbarui. Silakan masuk dengan password baru.');
 }
}