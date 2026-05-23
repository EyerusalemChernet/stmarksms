<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

    /**
     * HR Module Audit Log Dashboard
     * Displays all HR-related audit logs with filtering and export
     */
    public function hrAuditLog(Request $request)
    {
        $query = AuditLog::with('user')
            ->whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr']);

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by module
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        // Get unique modules and actions for filter dropdowns
        $modules = AuditLog::whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr'])
            ->distinct('module')
            ->pluck('module')
            ->sort();

        $actions = AuditLog::whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr'])
            ->distinct('action')
            ->pluck('action')
            ->sort();

        return view('pages.super_admin.audit_logs.hr_audit_log', compact('logs', 'modules', 'actions'));
    }

    /**
     * HR Manager Audit Log Dashboard
     * Displays HR-related audit logs that HR managers can view
     * Shows changes made by admins and super admins
     */
    public function hrManagerAuditLog(Request $request)
    {
        $query = AuditLog::with('user')
            ->whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr']);

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by module
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filter by user (who made the change)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        // Get unique modules and actions for filter dropdowns
        $modules = AuditLog::whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr'])
            ->distinct('module')
            ->pluck('module')
            ->sort();

        $actions = AuditLog::whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr'])
            ->distinct('action')
            ->pluck('action')
            ->sort();

        return view('pages.hr_manager.audit_logs.index', compact('logs', 'modules', 'actions'));
    }

    /**
     * Export HR audit logs to CSV
     */
    public function exportHrAuditLog(Request $request)
    {
        $query = AuditLog::with('user')
            ->whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr']);

        // Apply same filters as index
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderByDesc('created_at')->get();

        // Generate CSV
        $filename = 'hr-audit-log-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'User', 'Action', 'Module', 'Description', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user->name ?? 'System',
                    ucfirst($log->action),
                    ucfirst($log->module),
                    $log->description,
                    $log->ip_address,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export HR Manager audit logs to CSV
     */
    public function exportHrManagerAuditLog(Request $request)
    {
        $query = AuditLog::with('user')
            ->whereIn('module', ['payroll', 'employee', 'leave', 'recruitment', 'performance', 'training', 'attendance', 'contract', 'hr']);

        // Apply same filters as index
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderByDesc('created_at')->get();

        // Generate CSV
        $filename = 'hr-audit-log-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'User', 'Action', 'Module', 'Description', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user->name ?? 'System',
                    ucfirst($log->action),
                    ucfirst($log->module),
                    $log->description,
                    $log->ip_address,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
