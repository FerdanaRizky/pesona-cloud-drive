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

        // Normalize the path so the forced-password-change exception also
        // works when CodeIgniter is served with index.php in the URL.
        $path = trim($request->getUri()->getPath(), '/');
        $path = preg_replace('#^index\\.php/?#', '', $path);

        if ((int) $user['must_change_password'] === 1 && $path !== 'account/password') {
            return redirect()->to('/account/password')->with('error', 'Demi keamanan, silakan ganti password sementara terlebih dahulu.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
