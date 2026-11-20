<?php
namespace App\Controllers;

class Activity extends BaseController
{
    public function index()
    {
        $uid = (int) session()->get('user_id');
        $builder = db_connect()->table('activity_logs l')->select('l.*, u.name AS user_name, u.email AS user_email')->join('users u', 'u.id = l.user_id', 'left');
        if (session()->get('role') !== 'admin') $builder->where('l.user_id', $uid);
        $logs = $builder->orderBy('l.created_at', 'DESC')->limit(250)->get()->getResultArray();
        return view('drive/activity', ['title'=>'Riwayat Aktivitas', 'logs'=>$logs]);
    }
}
