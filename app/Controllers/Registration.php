<?php
namespace App\\Controllers;
use App\\Models\\UserModel;
use App\\Services\\GowaService;
class Registration extends BaseController
{
    public function form()
    {
        if(session()->get('user_id')) return redirect()->to('/dashboard');
        return view('auth/register',['title'=>'Daftar Akun - Pesona Cloud Drive']);
    }
    public function sendOtp()
    {
        if(session()->get('user_id')) return redirect()->to('/dashboard');
        $rules=['name'=>'required|min_length[2]|max_length[120]','email'=>'required|valid_email|max_length[190]','phone'=>'required|min_length[9]|max_length[20]','password'=>'required|min_length[12]|max_length[200]','password_confirm'=>'required|matches[password]'];
        if(!$this->validate($rules)) return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        $name=trim((string)$this->request->getPost('name'));
        $email=strtolower(trim((string)$this->request->getPost('email')));
        $phone=preg_replace('/[\\s+().-]/','',(string)$this->request->getPost('phone'));
        if(!preg_match('/^[1-9][0-9]{8,14}$/',$phone)) return redirect()->back()->withInput()->with('error','Nomor WhatsApp tidak valid. Gunakan format internasional, contoh 6281234567890.');
        $db=db_connect();$now=date('Y-m-d H:i:s');$ip=$this->request->getIPAddress();
        if((new UserModel())->where('email',$email)->first()) return redirect()->back()->withInput()->with('error','Email sudah terdaftar. Silakan masuk atau gunakan email lain.');
        if($db->table('users')->where('phone',$phone)->countAllResults()>0) return redirect()->back()->withInput()->with('error','Nomor WhatsApp sudah digunakan akun lain.');
        $recent=$db->table('registration_otps')->groupStart()->where('email',$email)->orWhere('ip_address',$ip)->groupEnd()->where('created_at >=',date('Y-m-d H:i:s',time()-60))->countAllResults();
        if($recent>0) return redirect()->back()->withInput()->with('error','Kode OTP baru saja diminta. Tunggu 60 detik sebelum meminta kode lagi.');
        $hourly=$db->table('registration_otps')->groupStart()->where('email',$email)->orWhere('ip_address',$ip)->groupEnd()->where('created_at >=',date('Y-m-d H:i:s',time()-3600))->countAllResults();
        if($hourly>=5) return redirect()->back()->withInput()->with('error','Batas permintaan OTP tercapai. Coba kembali setelah satu jam.');
        $otp=(string)random_int(100000,999999);
        $db->table('registration_otps')->where('email',$email)->where('used_at',null)->update(['used_at'=>$now]);
        $db->table('registration_otps')->insert([
            'name'=>$name,'email'=>$email,'phone'=>$phone,
            'password_hash'=>password_hash((string)$this->request->getPost('password'),PASSWORD_DEFAULT),
            'otp_hash'=>hash('sha256',$otp.':'.$email),'expires_at'=>date('Y-m-d H:i:s',time()+300),
            'used_at'=>null,'attempts'=>0,'ip_address'=>$ip,'created_at'=>$now
        ]);
        $message="Pesona Cloud Drive\\n\\nKode OTP pendaftaran akun Anda: {$otp}\\nBerlaku 5 menit. Jangan berikan kode ini kepada siapa pun. Jika Anda tidak merasa mendaftar, abaikan pesan ini.";
        if((new GowaService())->sendText($phone,$message)){
            session()->set('registration_email',$email);
            return redirect()->to('/register/verify')->with('message','Kode OTP telah dikirim ke WhatsApp Anda. Masukkan kode untuk menyelesaikan pendaftaran.');
        }
        $db->table('registration_otps')->where('email',$email)->where('used_at',null)->update(['used_at'=>$now]);
        log_message('error','Registration OTP delivery failed for email hash {email_hash}.',['email_hash'=>hash('sha256',$email)]);
        return redirect()->to('/register')->withInput()->with('error','Kode OTP gagal dikirim. Hubungi admin untuk memeriksa konfigurasi WA dan memastikan perangkat sedang terhubung, lalu coba lagi.');
    }
    public function verifyForm()
    {
        if(session()->get('user_id')) return redirect()->to('/dashboard');
        $email=(string)session()->get('registration_email');
        if($email==='') return redirect()->to('/register')->with('error','Silakan isi formulir pendaftaran terlebih dahulu.');
        return view('auth/register_verify',['title'=>'Verifikasi Pendaftaran - Pesona Cloud Drive','email'=>$email]);
    }
    public function verify()
    {
        if(session()->get('user_id')) return redirect()->to('/dashboard');
        $email=(string)session()->get('registration_email');$otp=trim((string)$this->request->getPost('otp'));
        if($email===''||!preg_match('/^[0-9]{6}$/',$otp)) return redirect()->to('/register')->with('error','Sesi atau kode OTP tidak valid. Silakan daftar kembali.');
        $db=db_connect();$row=$db->table('registration_otps')->where('email',$email)->where('used_at',null)->where('expires_at >',date('Y-m-d H:i:s'))->orderBy('id','DESC')->get()->getRowArray();
        if(!$row||(int)$row['attempts']>=5) return redirect()->to('/register')->with('error','Kode OTP kedaluwarsa atau batas percobaan tercapai. Silakan daftar kembali.');
        if(!hash_equals((string)$row['otp_hash'],hash('sha256',$otp.':'.$email))){
            $db->table('registration_otps')->where('id',(int)$row['id'])->set('attempts','attempts+1',false)->update();
            return redirect()->to('/register/verify')->with('error','Kode OTP salah. Periksa kembali pesan WhatsApp Anda.');
        }
        if((new UserModel())->where('email',$email)->first()||$db->table('users')->where('phone',(string)$row['phone'])->countAllResults()>0){
            $db->table('registration_otps')->where('id',(int)$row['id'])->update(['used_at'=>date('Y-m-d H:i:s')]);
            session()->remove('registration_email');
            return redirect()->to('/register')->with('error','Email atau nomor WhatsApp sudah digunakan akun lain.');
        }
        $db->transBegin();
        $users=new UserModel();
        $inserted=$users->insert([
            'name'=>$row['name'],'email'=>$email,'phone'=>$row['phone'],
            'password_hash'=>$row['password_hash'],'role'=>'user','status'=>'active',
            'quota_bytes'=>10737418240,'used_bytes'=>0,'must_change_password'=>false
        ]);
        if(!$inserted){$db->transRollback();return redirect()->to('/register/verify')->with('error','Akun gagal dibuat. Silakan coba kembali.');}
        $uid=(int)$users->getInsertID();
        $db->table('registration_otps')->where('id',(int)$row['id'])->update(['used_at'=>date('Y-m-d H:i:s')]);
        $db->table('activity_logs')->insert(['user_id'=>$uid,'action'=>'auth.register','details'=>'Pendaftaran akun berhasil diverifikasi melalui OTP WhatsApp.','ip_address'=>$this->request->getIPAddress(),'created_at'=>date('Y-m-d H:i:s')]);
        $db->transCommit();
        session()->remove('registration_email');
        return redirect()->to('/login')->with('message','Pendaftaran berhasil diverifikasi. Silakan masuk menggunakan email dan password Anda.');
    }
}
