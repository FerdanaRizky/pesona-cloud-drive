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
            'phone' => 'required|min_length[9]|max_length[20]',
        ];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $phone = preg_replace('/[\\s+().-]/', '', (string) $this->request->getPost('phone'));
        if (!preg_match('/^[1-9][0-9]{8,14}$/', $phone)) return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp tidak valid. Gunakan format internasional, contoh 6281234567890.');
        if (db_connect()->table('users')->where('phone', $phone)->countAllResults() > 0) return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp sudah digunakan pengguna lain.');

        $newId = (new UserModel())->insert([
            'name' => trim((string) $this->request->getPost('name')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'phone' => $phone,
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active',
            'quota_bytes' => (int) $this->request->getPost('quota_gb') * 1073741824,
            'used_bytes' => 0,
            'must_change_password' => true,
            'password_changed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAdmin('admin.user_create', 'Membuat akun pengguna #' . (int) $newId . ' (' . strtolower(trim((string) $this->request->getPost('email'))) . ')');
        return redirect()->to('/admin/users')->with('message', 'Pengguna berhasil dibuat. Minta pengguna mengganti password saat pertama kali masuk.');
    }

    public function update(int $id)
    {
        $users = new UserModel();
        $target = $users->find($id);
        if (!$target) return redirect()->to('/admin/users')->with('error', 'Pengguna tidak ditemukan.');

        $status = (string) ($this->request->getPost('status') ?? $target['status']);
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
        $users->update($id, ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'must_change_password' => true, 'password_changed_at' => date('Y-m-d H:i:s')]);
        $this->logAdmin('admin.user_password_reset', 'Reset password pengguna #' . $id);
        return redirect()->to('/admin/users')->with('message', 'Password direset. Sampaikan password sementara secara aman kepada pengguna.');
    }

    public function toggleStatus(int $id)
    {
        $users = new UserModel();
        $target = $users->find($id);
        if (!$target) return redirect()->to('/admin/users')->with('error', 'Pengguna tidak ditemukan.');
        if ($id === (int) session()->get('user_id')) return redirect()->to('/admin/users')->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        $next = $target['status'] === 'active' ? 'suspended' : 'active';
        if ($target['role'] === 'admin' && $target['status'] === 'active' && $next === 'suspended') {
            $activeAdmins = (new UserModel())->where('role', 'admin')->where('status', 'active')->countAllResults();
            if ($activeAdmins <= 1) return redirect()->to('/admin/users')->with('error', 'Administrator aktif terakhir tidak dapat dinonaktifkan.');
        }
        $users->update($id, ['status' => $next]);
        $this->logAdmin('admin.user_status', 'Mengubah status akun #' . $id . ' menjadi ' . $next);
        return redirect()->to('/admin/users')->with('message', $next === 'active' ? 'Akun pengguna berhasil diaktifkan.' : 'Akun pengguna berhasil dinonaktifkan.');
    }

    public function delete(int $id)
    {
        $users = new UserModel();
        $target = $users->find($id);
        if (!$target) return redirect()->to('/admin/users')->with('error', 'Pengguna tidak ditemukan.');
        if ($id === (int) session()->get('user_id')) return redirect()->to('/admin/users')->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        if ($target['role'] === 'admin' && $target['status'] === 'active') {
            $activeAdmins = (new UserModel())->where('role', 'admin')->where('status', 'active')->countAllResults();
            if ($activeAdmins <= 1) return redirect()->to('/admin/users')->with('error', 'Administrator aktif terakhir tidak dapat dihapus.');
        }
        $db = db_connect();
        $fileCount = $db->table('drive_files')->where('user_id', $id)->countAllResults();
        $folderCount = $db->table('drive_folders')->where('user_id', $id)->countAllResults();
        if ($fileCount > 0 || $folderCount > 0) {
            return redirect()->to('/admin/users')->with('error', 'Akun belum dapat dihapus karena masih memiliki ' . $fileCount . ' file dan ' . $folderCount . ' folder. Nonaktifkan akun agar data tetap aman.');
        }
        $db->transStart();
        $db->table('password_reset_tokens')->where('user_id', $id)->delete();
        $db->table('activity_logs')->where('user_id', $id)->delete();
        $users->delete($id);
        $db->transComplete();
        if (!$db->transStatus()) return redirect()->to('/admin/users')->with('error', 'Penghapusan akun gagal. Tidak ada perubahan yang dikonfirmasi.');
        $this->logAdmin('admin.user_delete', 'Menghapus akun kosong #' . $id . ' (' . $target['email'] . ')');
        return redirect()->to('/admin/users')->with('message', 'Akun pengguna berhasil dihapus.');
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
