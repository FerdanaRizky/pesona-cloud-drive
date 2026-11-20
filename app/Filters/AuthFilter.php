<?php
namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = (int) session()->get('user_id');
        if ($userId < 1) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = (new UserModel())->find($userId);
        if (!$user || $user['status'] !== 'active') {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Akun tidak aktif atau tidak ditemukan.');
        }

        if (session()->get('role') !== $user['role']) {
            session()->set('role', $user['role']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
