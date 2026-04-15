<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('teamSA');
    }

    public function index()
    {
        $logs = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('pages.super_admin.audit_logs.index', compact('logs'));
    }
}
