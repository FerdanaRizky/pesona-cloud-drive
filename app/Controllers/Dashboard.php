<?php
namespace App\\Controllers;

use App\\Models\\DriveFileModel;
use App\\Models\\DriveFolderModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $uid = (int) session()->get('user_id');
        $db = db_connect();
        $user = $db->table('users')->where('id', $uid)->get()->getRowArray();

        // Keep the dashboard scoped to the signed-in user's active items.
        $folders = (new DriveFolderModel())
            ->where('user_id', $uid)->where('is_trashed', 0)
            ->orderBy('updated_at', 'DESC')->orderBy('name', 'ASC')
            ->findAll(8);

        $files = (new DriveFileModel())
            ->where('user_id', $uid)->where('is_trashed', 0)
            ->orderBy('updated_at', 'DESC')->orderBy('created_at', 'DESC')
            ->findAll(12);

        $fileIds = array_map(static fn($file) => (int) $file['id'], $files);
        $sharedIds = [];
        if ($fileIds) {
            $sharedRows = $db->table('file_shares')->select('file_id')
                ->where('owner_id', $uid)->whereIn('file_id', $fileIds)
                ->groupBy('file_id')->get()->getResultArray();
            $sharedIds = array_fill_keys(array_map(static fn($row) => (int) $row['file_id'], $sharedRows), true);
        }
        foreach ($files as &$file) {
            $file['has_share'] = isset($sharedIds[(int) $file['id']]);
        }
        unset($file);

        return view('drive/dashboard', [
            'title' => 'Beranda',
            'user' => $user,
            'folders' => $folders,
            'files' => $files,
        ]);
    }
}
