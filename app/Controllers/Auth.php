<?php
namespace App\Controllers;
use App\Models\UserModel;
class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('user_id')) return redirect()->to('/dashboard');
        return view('auth/login', ['title' => 'Masuk - Pesona Cloud Drive']);
    }
    public function attempt()
    {
        $email = strtolower(trim((string)$this->request->getPost('email')));
        $password = (string)$this->request->getPost('password');
        $user = (new UserModel())->where('email', $email)->first();
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Email atau password tidak valid.');
        }
        session()->regenerate();
        db_connect()->table('activity_logs')->insert([
            'user_id' => (int) $user['id'], 'action' => 'auth.login',
            'details' => 'Login berhasil', 'ip_address' => $this->request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        session()->set(['user_id'=>(int)$user['id'], 'name'=>$user['name'], 'email'=>$user['email'], 'role'=>$user['role']]);
        return redirect()->to('/dashboard');
    }
    public function logout()
    {
        $uid = (int) session()->get('user_id');
        if ($uid > 0) {
            db_connect()->table('activity_logs')->insert([
                'user_id' => $uid, 'action' => 'auth.logout',
                'details' => 'Logout', 'ip_address' => $this->request->getIPAddress(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        session()->destroy();
        return redirect()->to('/login')->with('message', 'Anda telah keluar.');
    }
}
