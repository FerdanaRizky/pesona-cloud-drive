<?php
namespace App\Controllers;

use App\Models\DriveFileModel;

class Share extends BaseController
{
    private const PREVIEW_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'video/mp4', 'video/webm', 'video/ogg',
    ];

    public function create(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan.');

        $token = bin2hex(random_bytes(32));
        $expiry = (string) $this->request->getPost('expires_days');
        $expiresAt = in_array($expiry, ['1', '7', '30'], true)
            ? date('Y-m-d H:i:s', strtotime('+' . (int) $expiry . ' days'))
            : null;

        db_connect()->table('file_shares')->insert([
            'file_id' => $id,
            'owner_id' => $uid,
            'shared_with_email' => null,
            'token_hash' => hash('sha256', $token),
            'permission' => 'viewer',
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->log($uid, 'file.share', 'Membuat tautan berbagi: ' . $file['original_name']);
        return redirect()->back()->with('share_link', site_url('s/' . $token))->with('message', 'Tautan berbagi berhasil dibuat.');
    }

    public function revoke(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan.');
        db_connect()->table('file_shares')->where('file_id', $id)->where('owner_id', $uid)->delete();
        $this->log($uid, 'file.share_revoke', 'Mencabut tautan berbagi: ' . $file['original_name']);
        return redirect()->back()->with('message', 'Tautan berbagi dicabut.');
    }

    public function open(string $token)
    {
        $share = $this->findShare($token);
        if (!$share) return $this->response->setStatusCode(404)->setBody('Tautan tidak tersedia atau sudah kedaluwarsa.');
        return view('drive/shared', [
            'title' => 'File dibagikan - ' . $share['original_name'],
            'share' => $share,
            'canPreview' => in_array($share['mime_type'], self::PREVIEW_TYPES, true),
            'token' => $token,
        ]);
    }

    public function content(string $token)
    {
        $share = $this->findShare($token);
        if (!$share || !is_file($share['storage_path'])) return $this->response->setStatusCode(404);
        if (!in_array($share['mime_type'], self::PREVIEW_TYPES, true)) return $this->response->setStatusCode(415);
        $name = str_replace(["\r", "\n", '"'], '', basename($share['original_name']));
        $body = file_get_contents($share['storage_path']);
        if ($body === false) return $this->response->setStatusCode(404);

        return $this->response
            ->setHeader('Content-Type', $share['mime_type'])
            ->setHeader('Content-Disposition', 'inline; filename="' . addslashes($name) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox")
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody($body);
    }

    public function download(string $token)
    {
        $share = $this->findShare($token);
        if (!$share) return $this->response->setStatusCode(404);
        return $this->response->download($share['storage_path'], null)->setFileName($share['original_name']);
    }

    private function findShare(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
        $share = db_connect()->table('file_shares s')
            ->select('s.id AS share_id, s.owner_id, s.expires_at, f.id AS file_id, f.original_name, f.mime_type, f.size_bytes, f.storage_path, f.is_trashed')
            ->join('drive_files f', 'f.id = s.file_id')
            ->where('s.token_hash', hash('sha256', $token))
            ->get()->getRowArray();

        if (!$share || (int) $share['is_trashed'] === 1) return null;
        if (!empty($share['expires_at']) && strtotime($share['expires_at']) < time()) return null;
        if (!is_file($share['storage_path'])) return null;
        return $share;
    }

    private function log(int $uid, string $action, string $details): void
    {
        db_connect()->table('activity_logs')->insert([
            'user_id' => $uid,
            'action' => $action,
            'details' => mb_substr($details, 0, 2000),
            'ip_address' => $this->request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
