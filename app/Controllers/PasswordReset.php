<?php
namespace App\Controllers;

use App\Models\UserModel;

class PasswordReset extends BaseController
{
    public function requestForm()
    {
        if (session()->get('user_id')) return redirect()->to('/dashboard');
        return view('auth/forgot_password', ['title' => 'Lupa Password - Pesona Cloud Drive']);
    }

    public function sendLink()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            return redirect()->back()->withInput()->with('error', 'Masukkan alamat email yang valid.');
        }

        $db = db_connect();
        $ip = $this->request->getIPAddress();
        $since = date('Y-m-d H:i:s', time() - 3600);
        $recent = $db->table('password_reset_tokens')
            ->where('created_at >=', $since)
            ->groupStart()->where('email', $email)->orWhere('ip_address', $ip)->groupEnd()
            ->countAllResults();

        // Generic response and per-email/IP throttling prevent account enumeration and abuse.
        if ($recent < 3) {
            $user = (new UserModel())->where('email', $email)->where('status', 'active')->first();
            $plainToken = bin2hex(random_bytes(32));
            $db->table('password_reset_tokens')->insert([
                'user_id' => $user ? (int) $user['id'] : null,
                'email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'used_at' => null,
                'ip_address' => $ip,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($user) {
                $baseUrl = rtrim((string) config('App')->baseURL, '/');
                $resetUrl = $baseUrl . '/reset-password/' . $plainToken;
                $from = trim((string) env('email.fromEmail', ''));
                $fromName = trim((string) env('email.fromName', 'Pesona Cloud Drive'));
                $smtpHost = trim((string) env('email.SMTPHost', ''));
                $smtpUser = trim((string) env('email.SMTPUser', ''));
                $smtpPass = (string) env('email.SMTPPass', '');
                $smtpPort = (int) env('email.SMTPPort', 587);
                $smtpCrypto = strtolower(trim((string) env('email.SMTPCrypto', 'tls')));

                if ($from !== '' && $smtpHost !== '' && $smtpUser !== '' && $smtpPass !== '') {
                    $mailer = \Config\Services::email([
                        'protocol' => 'smtp',
                        'SMTPHost' => $smtpHost,
                        'SMTPUser' => $smtpUser,
                        'SMTPPass' => $smtpPass,
                        'SMTPPort' => $smtpPort,
                        'SMTPCrypto' => $smtpCrypto,
                        'SMTPTimeout' => 15,
                        'mailType' => 'html',
                        'charset' => 'UTF-8',
                        'wordWrap' => true,
                        'CRLF' => "\r\n",
                        'newline' => "\r\n",
                    ], false);
                    $mailer->setFrom($from, $fromName);
                    $mailer->setTo($email);
                    $mailer->setSubject('Reset password Pesona Cloud Drive');
                    $mailer->setMessage(view('emails/password_reset', [
                        'name' => $user['name'],
                        'resetUrl' => $resetUrl,
                    ]));
                    if (! $mailer->send()) {
                        log_message('error', 'Password reset email could not be sent: {errors}', ['errors' => $mailer->printDebugger(['headers'])]);
                    }
                    $mailer->clear(true);
                } else {
                    log_message('error', 'Password reset requested but SMTP settings are incomplete.');
                }
            }
        }

        return redirect()->to('/forgot-password')->with('message', 'Jika alamat email terdaftar dan aktif, tautan reset password akan dikirim. Periksa kotak masuk atau folder spam.');
    }

    public function resetForm(string $token)
    {
        if (session()->get('user_id')) return redirect()->to('/dashboard');
        $record = $this->findValidToken($token);
        if (! $record) {
            return view('auth/reset_password', [
                'title' => 'Reset Password - Pesona Cloud Drive',
                'validToken' => false,
            ]);
        }
        return view('auth/reset_password', [
            'title' => 'Reset Password - Pesona Cloud Drive',
            'validToken' => true,
            'token' => $token,
        ]);
    }

    public function updatePassword(string $token)
    {
        $password = (string) $this->request->getPost('password');
        $confirm = (string) $this->request->getPost('password_confirm');
        if (strlen($password) < 10) {
            return redirect()->back()->withInput()->with('error', 'Password minimal 10 karakter.');
        }
        if ($password !== $confirm) {
            return redirect()->back()->withInput()->with('error', 'Konfirmasi password tidak sama.');
        }

        $db = db_connect();
        $record = $this->findValidToken($token);
        if (! $record) {
            return redirect()->to('/forgot-password')->with('error', 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.');
        }

        $db->transStart();
        (new UserModel())->update((int) $record['user_id'], [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => false,
        ]);
        $db->table('password_reset_tokens')
            ->where('user_id', (int) $record['user_id'])
            ->where('used_at', null)
            ->update(['used_at' => date('Y-m-d H:i:s')]);
        $db->table('activity_logs')->insert([
            'user_id' => (int) $record['user_id'],
            'action' => 'auth.password_reset',
            'details' => 'Password direset melalui tautan email.',
            'ip_address' => $this->request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('/forgot-password')->with('error', 'Password belum berhasil diperbarui. Silakan coba lagi.');
        }

        return redirect()->to('/login')->with('message', 'Password berhasil diperbarui. Silakan masuk dengan password baru.');
    }

    private function findValidToken(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) return null;
        $record = db_connect()->table('password_reset_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->where('used_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->where('user_id IS NOT NULL', null, false)
            ->get()->getRowArray();
        if (! $record) return null;

        $user = (new UserModel())->where('id', (int) $record['user_id'])
            ->where('status', 'active')->first();
        return $user ? $record : null;
    }
}
