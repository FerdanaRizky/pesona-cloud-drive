<?php
namespace App\Controllers;

class Activity extends BaseController
{
    public function index()
    {
        $uid = (int) session()->get('user_id');
        $isAdmin = session()->get('role') === 'admin';
        $request = $this->request;
        $from = trim((string) $request->getGet('from'));
        $to = trim((string) $request->getGet('to'));
        $userId = $isAdmin ? (int) $request->getGet('user_id') : $uid;
        $action = trim((string) $request->getGet('action'));
        $allowedDate = static fn ($v) => $v === '' || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
        if (!$allowedDate($from)) $from = '';
        if (!$allowedDate($to)) $to = '';
        $builder = db_connect()->table('activity_logs l')
            ->select('l.*, u.name AS user_name, u.email AS user_email')
            ->join('users u', 'u.id = l.user_id', 'left');
        if (!$isAdmin || $userId > 0) $builder->where('l.user_id', $userId ?: $uid);
        if ($from !== '') $builder->where('l.created_at >=', $from . ' 00:00:00');
        if ($to !== '') $builder->where('l.created_at <=', $to . ' 23:59:59');
        if ($action !== '' && preg_match('/^[a-z0-9._-]{1,100}$/i', $action)) $builder->where('l.action', $action);
        $logs = $builder->orderBy('l.created_at', 'DESC')->limit(500)->get()->getResultArray();
        $users = $isAdmin ? db_connect()->table('users')->select('id,name,email')->orderBy('name')->get()->getResultArray() : [];
        $actions = db_connect()->table('activity_logs')->distinct()->select('action')->orderBy('action')->get()->getResultArray();
        return view('drive/activity', [
            'title' => 'Audit Log', 'logs' => $logs, 'users' => $users, 'actions' => $actions,
            'filters' => ['from'=>$from, 'to'=>$to, 'user_id'=>$userId, 'action'=>$action], 'isAdmin'=>$isAdmin,
        ]);
    }
}