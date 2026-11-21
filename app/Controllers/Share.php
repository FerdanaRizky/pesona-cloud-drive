<?php
namespace App\Controllers;

use App\Models\DriveFileModel;

class Share extends BaseController
{
    private const PREVIEW_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'video/mp4', 'video/webm', 'video/ogg',
    ];

    public function index()
    {
        $uid = (int) session()->get('user_id');
        $rows = db_connect()->table('file_shares s')
            ->select('s.id AS share_id, s.expires_at, s.created_at, s.token_ciphertext, f.id AS file_id, f.original_name, f.mime_type, f.size_bytes')
            ->join('drive_files f', 'f.id = s.file_id')
            ->where('s.owner_id', $uid)
            ->orderBy('s.created_at', 'DESC')->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['expired'] = !empty($row['expires_at']) && strtotime($row['expires_at']) < time();
            $row['active'] = !$row['expired'];
            $row['expiry_option'] = empty($row['expires_at']) ? 'never' : '7';
            if (!empty($row['expires_at'])) {
                $remaining = strtotime($row['expires_at']) - time();
                $row['expiry_option'] = $remaining <= 86400 ? '1' : ($remaining <= 7 * 86400 ? '7' : '30');
            }
            $row['share_url'] = null;
            if (!empty($row['token_ciphertext'])) {
                try { $row['share_url'] = site_url('s/' . service('encrypter')->decrypt($row['token_ciphertext'])); } catch (\Throwable $e) { $row['share_url'] = null; }
            }
        }
        unset($row);
        return view('drive/shares', ['title' => 'Manajemen Link Berbagi', 'shares' => $rows]);
    }

    public function update(int $id)
    {
        $uid = (int) session()->get('user_id');
        $share = db_connect()->table('file_shares')->where('id', $id)->where('owner_id', $uid)->get()->getRowArray();
        if (!$share) return redirect()->back()->with('error', 'Link berbagi tidak ditemukan.');
        $expiry = (string) $this->request->getPost('expires_days');
        if (!in_array($expiry, ['1', '7', '30', 'never'], true)) {
            return redirect()->back()->with('error', 'Masa berlaku tidak valid.');
        }
        $expiresAt = $expiry === 'never' ? null : date('Y-m-d H:i:s', strtotime('+' . (int) $expiry . ' days'));
        db_connect()->table('file_shares')->where('id', $id)->where('owner_id', $uid)->update(['expires_at' => $expiresAt]);
        $this->log($uid, 'file.share_update', 'Mengubah masa berlaku link berbagi #' . $id);
        return redirect()->to('/shares')->with('message', 'Masa berlaku link berhasil diperbarui.');
    }


    public function restore(int $id)
    {
        $uid = (int) session()->get('user_id');
        $share = db_connect()->table('file_shares s')
            ->select('s.id, s.token_ciphertext, f.is_trashed')
            ->join('drive_files f', 'f.id = s.file_id')
            ->where('s.id', $id)->where('s.owner_id', $uid)->get()->getRowArray();

        if (!$share || (int) $share['is_trashed'] === 1) {
            return redirect()->to('/shares')->with('error', 'Link berbagi tidak ditemukan atau file sudah di Sampah.');
        }

        if (empty($share['token_ciphertext'])) {
            return redirect()->to('/shares')->with('error', 'URL tautan ini belum tersimpan. Tautan lama tidak dapat dipulihkan; buat tautan baru dari menu File Saya.');
        }

        try {
            $token = service('encrypter')->decrypt($share['token_ciphertext']);
            if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
                throw new \\RuntimeException('Invalid saved share token.');
            }
        } catch (\\Throwable $e) {
            return redirect()->to('/shares')->with('error', 'URL tautan tidak dapat dibaca. Periksa konfigurasi encryption key aplikasi.');
        }

        return redirect()->to('/shares')
            ->with('share_id', $id)
            ->with('message', 'Link berbagi ditampilkan pada baris file.');
    }

    public function revokeOne(int $id)
    {
        $uid = (int) session()->get('user_id');
        $share = db_connect()->table('file_shares s')
            ->select('s.id, f.original_name')->join('drive_files f', 'f.id=s.file_id')
            ->where('s.id', $id)->where('s.owner_id', $uid)->get()->getRowArray();
        if (!$share) return redirect()->back()->with('error', 'Link berbagi tidak ditemukan.');
        db_connect()->table('file_shares')->where('id', $id)->where('owner_id', $uid)->delete();
        $this->log($uid, 'file.share_revoke', 'Mencabut satu link berbagi: ' . $share['original_name']);
        return redirect()->to('/shares')->with('message', 'Link berbagi berhasil dicabut.');
    }


    public function bulkUpdate()
    {
        $uid = (int) session()->get('user_id');
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('share_ids')))));
        $expiry = (string) $this->request->getPost('expires_days');
        if (!$ids) return redirect()->back()->with('error', 'Pilih minimal satu link berbagi.');
        if (!in_array($expiry, ['1', '7', '30', 'never'], true)) return redirect()->back()->with('error', 'Masa berlaku tidak valid.');
        $expiresAt = $expiry === 'never' ? null : date('Y-m-d H:i:s', strtotime('+' . (int) $expiry . ' days'));
        $db = db_connect(); $updated = 0;
        foreach ($ids as $id) {
            $share = $db->table('file_shares')->where('id', $id)->where('owner_id', $uid)->get()->getRowArray();
            if (!$share) continue;
            $db->table('file_shares')->where('id', $id)->where('owner_id', $uid)->update(['expires_at' => $expiresAt]);
            $this->log($uid, 'file.share_update', 'Memperbarui masa berlaku link berbagi #' . $id);
            $updated++;
        }
        return redirect()->to('/shares')->with('message', $updated . ' masa berlaku link berhasil diperbarui.');
    }

    public function bulkRevoke()
    {
        $uid = (int) session()->get('user_id');
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('share_ids')))));
        if (!$ids) return redirect()->back()->with('error', 'Pilih minimal satu link berbagi.');
        $db = db_connect(); $revoked = 0;
        foreach ($ids as $id) {
            $share = $db->table('file_shares')->select('id')->where('id', $id)->where('owner_id', $uid)->get()->getRowArray();
            if (!$share) continue;
            $db->table('file_shares')->where('id', $id)->where('owner_id', $uid)->delete();
            $this->log($uid, 'file.share_revoke', 'Mencabut link berbagi #' . $id);
            $revoked++;
        }
        return redirect()->to('/shares')->with('message', $revoked . ' link berbagi berhasil dicabut.');
    }

    public function create(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->where('is_trashed', 0)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan.');
        $token = bin2hex(random_bytes(32));
        $expiry = (string) $this->request->getPost('expires_days');
        $expiresAt = in_array($expiry, ['1', '7', '30'], true)
            ? date('Y-m-d H:i:s', strtotime('+' . (int) $expiry . ' days')) : null;
        db_connect()->table('file_shares')->insert([
            'file_id' => $id, 'owner_id' => $uid, 'shared_with_email' => null,
            'token_hash' => hash('sha256', $token), 'token_ciphertext' => service('encrypter')->encrypt($token), 'permission' => 'viewer',
            'expires_at' => $expiresAt, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $shareId = (int) db_connect()->insertID();
        $this->log($uid, 'file.share', 'Membuat tautan berbagi: ' . $file['original_name']);
        return redirect()->to('/shares')->with('share_id', $shareId)->with('message', 'Tautan berbagi berhasil dibuat.');
    }

    public function revoke(int $id)
    {
        $uid = (int) session()->get('user_id');
        $file = (new DriveFileModel())->where('id', $id)->where('user_id', $uid)->first();
        if (!$file) return redirect()->back()->with('error', 'File tidak ditemukan.');
        db_connect()->table('file_shares')->where('file_id', $id)->where('owner_id', $uid)->delete();
        $this->log($uid, 'file.share_revoke', 'Mencabut seluruh tautan berbagi: ' . $file['original_name']);
        return redirect()->back()->with('message', 'Seluruh tautan berbagi file berhasil dicabut.');
    }

    public function open(string $token)
    {
        $share = $this->findShare($token);
        if (!$share) return $this->response->setStatusCode(404)->setBody('Tautan tidak tersedia atau sudah kedaluwarsa.');
        $officePreview = \App\Libraries\OfficePreview::supports($share);
        $canPreview = in_array($share['mime_type'], self::PREVIEW_TYPES, true) || $officePreview;
        return view('drive/shared', ['title' => 'File dibagikan - ' . $share['original_name'], 'share' => $share, 'canPreview' => $canPreview, 'officePreview' => $officePreview, 'token' => $token]);
    }

    public function content(string $token)
    {
        $share = $this->findShare($token);
        if (!$share) return $this->response->setStatusCode(404);
        if (in_array($share['mime_type'], self::PREVIEW_TYPES, true)) return $this->inlineFile($share);
        if (!\App\Libraries\OfficePreview::supports($share)) return $this->response->setStatusCode(404);
        $pdf = \App\Libraries\OfficePreview::convert($share);
        if (!$pdf) return $this->response->setStatusCode(503)->setBody('Office preview is not available.');
        return $this->inlineFile(['storage_path'=>$pdf, 'original_name'=>$share['original_name'] . '.pdf', 'mime_type'=>'application/pdf']);
    }

    public function download(string $token)
    {
        $share = $this->findShare($token);
        if (!$share) return $this->response->setStatusCode(404);
        $accelerated = \App\Libraries\PrivateFileDelivery::response($this->response, $share['storage_path'], $share['mime_type'], $share['original_name'], 'attachment');
        if ($accelerated !== null) return $accelerated;
        return $this->response->download($share['storage_path'], null)->setFileName($share['original_name']);
    }

    private function inlineFile(array $file)
    {
        $accelerated = \App\Libraries\PrivateFileDelivery::response($this->response, $file['storage_path'], $file['mime_type'], $file['original_name'], 'inline');
        if ($accelerated !== null) {
            return $accelerated->setHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }
        $name = str_replace(["\r", "\n", '"'], '', basename($file['original_name']));
        $body = file_get_contents($file['storage_path']);
        if ($body === false) return $this->response->setStatusCode(404);
        return $this->response->setHeader('Content-Type', $file['mime_type'])
            ->setHeader('Content-Disposition', 'inline; filename="' . addslashes($name) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox")
            ->setHeader('Cache-Control', 'private, no-store')->setBody($body);
    }

    private function findShare(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
        $share = db_connect()->table('file_shares s')
            ->select('s.id AS share_id, s.owner_id, s.expires_at, f.id AS file_id, f.original_name, f.mime_type, f.size_bytes, f.storage_path, f.is_trashed')
            ->join('drive_files f', 'f.id = s.file_id')
            ->where('s.token_hash', hash('sha256', $token))->get()->getRowArray();
        if (!$share || (int) $share['is_trashed'] === 1) return null;
        if (!empty($share['expires_at']) && strtotime($share['expires_at']) < time()) return null;
        if (!is_file($share['storage_path'])) return null;
        return $share;
    }

    private function log(int $uid, string $action, string $details): void
    {
        db_connect()->table('activity_logs')->insert([
            'user_id' => $uid, 'action' => $action, 'details' => mb_substr($details, 0, 2000),
            'ip_address' => $this->request->getIPAddress(), 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}