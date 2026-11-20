<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Users extends BaseController
{
    public function index()
    {
        $users = (new UserModel())->orderBy('created_at', 'DESC')->findAll();
        $stats = [
            'total' => count($users),
            'active' => count(array_filter($users, static fn ($u) => $u['status'] === 'active')),
            'storage' => array_sum(array_map(static fn ($u) => (int) $u['used_bytes'], $users)),
        ];
        return view('admin/users', ['title' => 'Manajemen Pengguna', 'users' => $users, 'stats' => $stats]);
    }

    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]|max_length[120]',
            'email' => 'required|valid_email|max_length[190]|is_unique[users.email]',
            'password' => 'required|min_length[12]|max_length[200]',
            'quota_gb' => 'required|is_natural_no_zero|less_than_equal_to[10000]',
        ];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());

        $newId = (new UserModel())->insert([
            'name' => trim((string) $this->request->getPost('name')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active',
            'quota_bytes' => (int) $this->request->getPost('quota_gb') * 1073741824,
            'used_bytes' => 0,
            'must_change_password' => true,
        ]);
        $this->logAdmin('admin.user_create', 'Membuat akun pengguna #' . (int) $newId . ' (' . strtolower(trim((string) $this->request->getPost('email'))) . ')');
        return redirect()->to('/admin/users')->with('message', 'Pengguna berhasil dibuat. Minta pengguna mengganti password saat pertama kali masuk.');
    }

    public function update(int $id)
    {
        $users = new UserModel();
        $target = $users->find($id);
        if (!$target) return redirect()->to('/admin/users')->with('error', 'Pengguna tidak ditemukan.');

        $status = (string) $this->request->getPost('status');
        $quotaGb = (int) $this->request->getPost('quota_gb');
        if (!in_array($status, ['active', 'suspended'], true) || $quotaGb < 1 || $quotaGb > 10000) {
            return redirect()->back()->with('error', 'Status atau kuota tidak valid.');
        }
        $phone = preg_replace('/[\\s+().-]/', '', (string) $this->request->getPost('phone'));
        if ($phone !== '' && !preg_match('/^[1-9][0-9]{8,14}$/', $phone)) {
            return redirect()->back()->with('error', 'Nomor WhatsApp pengguna tidak valid. Gunakan format internasional, contoh 6281234567890.');
        }
        if ($phone !== '') {
            $duplicate = db_connect()->table('users')->where('phone', $phone)->where('id !=', $id)->countAllResults();
            if ($duplicate > 0) return redirect()->back()->with('error', 'Nomor WhatsApp sudah digunakan pengguna lain.');
        }
        $quotaBytes = $quotaGb * 1073741824;
        if ($quotaBytes < (int) $target['used_bytes']) {
            return redirect()->back()->with('error', 'Kuota tidak boleh lebih kecil daripada penyimpanan yang sudah digunakan.');
        }
        if ($id === (int) session()->get('user_id') && $status !== 'active') {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }
        if ($target['role'] === 'admin' && $target['status'] === 'active' && $status === 'suspended') {
            $activeAdmins = $users->where('role', 'admin')->where('status', 'active')->countAllResults();
            if ($activeAdmins <= 1) return redirect()->back()->with('error', 'Tidak dapat menonaktifkan administrator aktif terakhir.');
        }

        $users->update($id, ['status' => $status, 'quota_bytes' => $quotaBytes, 'phone' => $phone !== '' ? $phone : null]);
        $this->logAdmin('admin.user_update', 'Mengubah status/kuota pengguna #' . $id . ' menjadi ' . $status . ', kuota ' . $quotaGb . ' GB');
        return redirect()->to('/admin/users')->with('message', 'Pengaturan pengguna berhasil diperbarui.');
    }

    public function resetPassword(int $id)
    {
        $users = new UserModel();
        if (!$users->find($id)) return redirect()->to('/admin/users')->with('error', 'Pengguna tidak ditemukan.');
        $password = (string) $this->request->getPost('new_password');
        if (strlen($password) < 12 || strlen($password) > 200) {
            return redirect()->back()->with('error', 'Password sementara harus terdiri dari 12 sampai 200 karakter.');
        }
        $users->update($id, ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'must_change_password' => true]);
        $this->logAdmin('admin.user_password_reset', 'Reset password pengguna #' . $id);
        return redirect()->to('/admin/users')->with('message', 'Password direset. Sampaikan password sementara secara aman kepada pengguna.');
    }
    private function logAdmin(string $action, string $details): void
    {
        db_connect()->table('activity_logs')->insert([
            'user_id' => (int) session()->get('user_id'), 'action' => $action,
            'details' => mb_substr($details, 0, 2000), 'ip_address' => $this->request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
