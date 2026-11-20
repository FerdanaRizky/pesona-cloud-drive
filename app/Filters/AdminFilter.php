<?php
namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = (int) session()->get('user_id');
        if ($userId < 1) {
            return redirect()->to('/login')->with('error', 'Silakan login sebagai administrator.');
        }

        $user = (new UserModel())->find($userId);
        if (!$user || $user['status'] !== 'active') {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Akun tidak aktif atau tidak ditemukan.');
        }

        if ($user['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses administrator.');
        }

        session()->set('role', $user['role']);
        if ((int) $user['must_change_password'] === 1) {
            return redirect()->to('/account/password')->with('error', 'Demi keamanan, silakan ganti password sementara terlebih dahulu.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
