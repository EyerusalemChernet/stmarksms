<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\StaffPayroll;
use App\Services\AttendanceService;
use App\Services\PayrollService;
use App\Services\PayrollCalculator;
use App\Services\PayrollValidator;
use App\Services\PayrollReport;
use Illuminate\Http\Request;
use PDF;

class PayrollController extends Controller
{
    protected PayrollService $payrollService;
    protected AttendanceService $attendanceService;

    public function __construct(PayrollService $payrollService, AttendanceService $attendanceService)
    {
        $this->middleware('hr_manager');
        $this->payrollService    = $payrollService;
        $this->attendanceService = $attendanceService;
    }

    // ── LIST ─────────────────────────────────────────────────────────────────

    public function index(Request $req)
    {
        $month  = $req->get('month', now()->format('Y-m'));
        $status = $req->get('status', 'all');
        $report_type = $req->get('report', 'summary');

        $employees = Employee::where('status', 'active')
            ->with(['employmentDetails.department', 'employmentDetails.position'])
            ->orderBy('first_name')
            ->get();

        $payrolls = StaffPayroll::where('month', $month)
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->with('employee')
            ->get()
            ->keyBy('employee_id');

        \Log::info("Payroll index debug", [
            'month' => $month,
            'status' => $status,
            'total_employees' => $employees->count(),
            'total_payrolls' => $payrolls->count(),
            'payroll_keys' => $payrolls->keys()->toArray(),
        ]);

        $statusCounts = array_merge(
            ['draft' => 0, 'approved' => 0, 'paid' => 0],
            StaffPayroll::where('month', $month)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray()
        );

        // ── Use advanced reporting ────────────────────────────────────────────
        try {
            $report = new PayrollReport($month, $payrolls);
            $reports = [
                'summary' => $report->getSummaryReport(),
                'attendance' => $report->getAttendanceReport(),
                'departments' => $report->getDepartmentReport(),
                'overtime' => $report->getOvertimeReport(),
                'compliance' => $report->getComplianceReport(),
            ];
        } catch (\Exception $e) {
            // If reporting fails, provide empty reports
            $reports = [
                'summary' => null,
                'attendance' => null,
                'departments' => null,
                'overtime' => null,
                'compliance' => null,
            ];
        }

        // ── Export ───────────────────────────────────────────────────────────
        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.payroll_pdf',
                compact('employees', 'payrolls', 'month', 'status', 'statusCounts', 'reports'));
            return $pdf->setPaper('a4', 'landscape')->download("payroll_{$month}.pdf");
        }

        if ($req->get('export') === 'csv') {
            return $this->exportCsv($employees, $payrolls, $month);
        }

        return view('pages.hr.payroll', compact('employees', 'month', 'payrolls', 'status', 'statusCounts', 'reports', 'report_type'));
    }

    // ── GENERATE ─────────────────────────────────────────────────────────────

    public function generate(Request $req)
    {
        $req->validate(['month' => 'required|date_format:Y-m']);

        $result = $this->payrollService->generateBulk($req->month, $this->attendanceService);

        $msg = "Payroll generated for {$req->month}. "
             . "{$result['generated']} record(s) created, {$result['skipped']} skipped (already existed).";

        return redirect()->route('hr.payroll', ['month' => $req->month, 'status' => 'all'])
            ->with('flash_success', $msg);
    }

    // ── EDIT / VIEW ───────────────────────────────────────────────────────────

    public function edit($id)
    {
        // Validate ID
        if (!is_numeric($id) || (int)$id <= 0) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Invalid payroll ID.');
        }

        $id = (int)$id;
        
        // Fetch payroll with relationships
        $payroll = StaffPayroll::with([
            'employee.employmentDetails.department',
            'employee.employmentDetails.position',
            'items',
            'approvedBy'
        ])->find($id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', "Payroll record #{$id} not found. The payroll may have been deleted or doesn't exist yet. Please generate payroll for the desired month.");
        }
        
        // Validate payroll has employee
        if (!$payroll->employee) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', "Payroll record is corrupted: associated employee not found. Contact system administrator.");
        }

        // ── Validate payroll integrity ───────────────────────────────────────
        $validator = new PayrollValidator();
        $validation = $validator->validatePayrollIntegrity($payroll);
        $warnings = $validator->getWarnings();

        // ── Calculate breakdown ──────────────────────────────────────────────
        $calculator = new PayrollCalculator($payroll->employee, $payroll->month);
        $calculations = $calculator->getCalculations();

        // ── Get analytics ────────────────────────────────────────────────────
        $earnings = $payroll->getEarningsBreakdown();
        $deductions = $payroll->getDeductionsBreakdown();
        $tax_rate = $payroll->getEffectiveTaxRate();
        $processing_time = $payroll->getProcessingTime();
        $status_info = $payroll->getStatusInfo();

        return view('pages.hr.payroll_edit', compact(
            'payroll', 'validation', 'warnings', 'calculations',
            'earnings', 'deductions', 'tax_rate', 'processing_time', 'status_info'
        ));
    }

    // ── UPDATE BASE SALARY / NOTES ────────────────────────────────────────────

    public function update(Request $req, $id)
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Invalid payroll ID.');
        }

        $payroll = StaffPayroll::findOr((int)$id, function() {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Payroll record not found.');
        });

        $req->validate([
            'base_salary' => 'required|numeric|min:0',
            'notes'       => 'nullable|string|max:500',
        ]);

        if (!$payroll->isDraft()) {
            return back()->with('flash_danger', 'Only draft payrolls can be edited.');
        }

        $payroll->update([
            'base_salary' => $req->base_salary,
            'notes'       => $req->notes,
        ]);

        $this->payrollService->recalculateFromItems($payroll);

        return back()->with('flash_success', 'Payroll updated.');
    }

    // ── ADD LINE ITEM ─────────────────────────────────────────────────────────

    public function addItem(Request $req, $id)
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            return back()->with('flash_danger', 'Invalid payroll ID.');
        }

        $payroll = StaffPayroll::find((int)$id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Payroll record not found.');
        }

        $req->validate([
            'type'   => 'required|in:earning,deduction',
            'label'  => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'nullable|string|max:255',
        ]);

        try {
            $this->payrollService->addItem(
                $payroll,
                $req->type,
                $req->label,
                (float) $req->amount,
                $req->note
            );
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        }

        return back()->with('flash_success', 'Item added.');
    }

    // ── REMOVE LINE ITEM ──────────────────────────────────────────────────────

    public function removeItem(Request $req, $id)
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            return back()->with('flash_danger', 'Invalid payroll ID.');
        }

        $payroll = StaffPayroll::find((int)$id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Payroll record not found.');
        }

        $req->validate(['item_id' => 'required|integer|exists:payroll_items,id']);

        try {
            $this->payrollService->removeItem($payroll, (int) $req->item_id);
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        }

        return back()->with('flash_success', 'Item removed.');
    }

    // ── APPROVE ───────────────────────────────────────────────────────────────

    public function approve($id)
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            return back()->with('flash_danger', 'Invalid payroll ID.');
        }

        $payroll = StaffPayroll::find((int)$id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Payroll record not found.');
        }

        try {
            $this->payrollService->approve($payroll, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        }

        return back()->with('flash_success', 'Payroll approved.');
    }

    // ── MARK PAID ─────────────────────────────────────────────────────────────

    public function markPaid($id)
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            return back()->with('flash_danger', 'Invalid payroll ID.');
        }

        $payroll = StaffPayroll::find((int)$id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Payroll record not found.');
        }

        try {
            $this->payrollService->markPaid($payroll, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        }

        return back()->with('flash_success', 'Payroll marked as paid.');
    }

    // ── REVERT TO DRAFT ───────────────────────────────────────────────────────

    public function revertToDraft($id)
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            return back()->with('flash_danger', 'Invalid payroll ID.');
        }

        $payroll = StaffPayroll::find((int)$id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Payroll record not found.');
        }

        try {
            $this->payrollService->revertToDraft($payroll);
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        }

        return back()->with('flash_success', 'Payroll reverted to draft.');
    }

    // ── ADVANCED REPORTS ────────────────────────────────────────────────────

    public function reports(Request $req)
    {
        $month = $req->get('month', now()->format('Y-m'));
        $report_type = $req->get('type', 'summary');

        $payrolls = StaffPayroll::where('month', $month)
            ->with(['employee', 'employee.employmentDetails.department', 'approvedBy'])
            ->get();

        $report = new PayrollReport($month, $payrolls);

        $report_data = match($report_type) {
            'attendance' => $report->getAttendanceReport(),
            'departments' => $report->getDepartmentReport(),
            'overtime' => $report->getOvertimeReport(),
            'compliance' => $report->getComplianceReport(),
            'comparison' => $report->getComparisonReport(now()->subMonth()->format('Y-m')),
            default => $report->getSummaryReport(),
        };

        if ($req->get('export') === 'json') {
            return response()->json($report_data);
        }

        return view('pages.hr.payroll_reports', compact(
            'month', 'report_type', 'report_data', 'payrolls'
        ));
    }

    // ── CSV EXPORT ────────────────────────────────────────────────────────────

    protected function exportCsv($employees, $payrolls, $month)
    {
        $filename = "payroll_{$month}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($employees, $payrolls, $month) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ["Payroll Report — {$month}"]);
            fputcsv($h, []);
            fputcsv($h, [
                'Employee', 'Code', 'Department', 'Base Salary', 'Allowances',
                'Deductions', 'Income Tax', 'Employee Pension', 'Net Pay',
                'Present', 'Absent', 'Leave', 'Status',
            ]);

            foreach ($employees as $emp) {
                $pr = $payrolls->get($emp->id);
                if (!$pr) {
                    fputcsv($h, [
                        $emp->full_name, $emp->employee_code,
                        $emp->employmentDetails?->department?->name ?? '',
                        '', '', '', '', '', '', '', '', '', 'Not generated',
                    ]);
                    continue;
                }
                fputcsv($h, [
                    $emp->full_name,
                    $emp->employee_code,
                    $emp->employmentDetails?->department?->name ?? '',
                    $pr->base_salary,
                    $pr->allowances,
                    $pr->deductions,
                    $pr->income_tax,
                    $pr->employee_pension,
                    $pr->net_pay,
                    $pr->present_days,
                    $pr->absent_days,
                    $pr->leave_days,
                    ucfirst($pr->status),
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── SHOW (Detail View) ────────────────────────────────────────────────────

    public function show($id)
    {
        // Validate ID
        if (!is_numeric($id) || (int)$id <= 0) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Invalid payroll ID.');
        }

        $id = (int)$id;

        // Fetch payroll
        $payroll = StaffPayroll::with([
            'employee.employmentDetails.department',
            'employee.employmentDetails.position',
            'items',
            'approvedBy'
        ])->find($id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', "Payroll record #{$id} not found.");
        }

        // Get analytics
        $earnings = $payroll->getEarningsBreakdown();
        $deductions = $payroll->getDeductionsBreakdown();
        $tax_rate = $payroll->getEffectiveTaxRate();
        $processing_time = $payroll->getProcessingTime();
        $status_info = $payroll->getStatusInfo();

        return view('pages.hr.payroll_show', compact(
            'payroll', 'earnings', 'deductions', 'tax_rate', 'processing_time', 'status_info'
        ));
    }

    // ── PDF EXPORT ────────────────────────────────────────────────────────────

    public function pdf($id)
    {
        // Validate ID
        if (!is_numeric($id) || (int)$id <= 0) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Invalid payroll ID.');
        }

        $id = (int)$id;

        // Fetch payroll
        $payroll = StaffPayroll::with([
            'employee.employmentDetails.department',
            'items',
            'approvedBy'
        ])->find($id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', "Payroll record #{$id} not found.");
        }

        // Get analytics
        $earnings = $payroll->getEarningsBreakdown();
        $deductions = $payroll->getDeductionsBreakdown();
        $tax_rate = $payroll->getEffectiveTaxRate();

        // Generate PDF
        $pdf = PDF::loadView('pages.hr.payroll_pdf', compact(
            'payroll', 'earnings', 'deductions', 'tax_rate'
        ));

        return $pdf->setPaper('a4', 'portrait')->download(
            "payroll_{$payroll->employee->employee_code}_{$payroll->month}.pdf"
        );
    }

    // ── CSV EXPORT (Single Payroll) ───────────────────────────────────────────

    public function export($id)
    {
        // Validate ID
        if (!is_numeric($id) || (int)$id <= 0) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', 'Invalid payroll ID.');
        }

        $id = (int)$id;

        // Fetch payroll
        $payroll = StaffPayroll::with(['employee', 'items'])->find($id);

        if (!$payroll) {
            return redirect()->route('hr.payroll')
                ->with('flash_danger', "Payroll record #{$id} not found.");
        }

        $filename = "payroll_{$payroll->employee->employee_code}_{$payroll->month}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($payroll) {
            $h = fopen('php://output', 'w');
            
            // Header
            fputcsv($h, ["Payroll Details — {$payroll->month}"]);
            fputcsv($h, []);
            
            // Employee Info
            fputcsv($h, ["Employee", $payroll->employee->full_name]);
            fputcsv($h, ["Code", $payroll->employee->employee_code]);
            fputcsv($h, ["Department", $payroll->employee->employmentDetails?->department?->name ?? '—']);
            fputcsv($h, ["Month", $payroll->month]);
            fputcsv($h, ["Status", ucfirst($payroll->status)]);
            fputcsv($h, []);
            
            // Financial Summary
            fputcsv($h, ["Base Salary", $payroll->base_salary]);
            fputcsv($h, ["Allowances", $payroll->allowances]);
            fputcsv($h, ["Gross Pay", $payroll->base_salary + $payroll->allowances]);
            fputcsv($h, []);
            
            // Deductions
            fputcsv($h, ["Income Tax", $payroll->income_tax]);
            fputcsv($h, ["Employee Pension (7%)", $payroll->employee_pension]);
            fputcsv($h, ["Other Deductions", $payroll->deductions - $payroll->income_tax - $payroll->employee_pension]);
            fputcsv($h, ["Total Deductions", $payroll->deductions]);
            fputcsv($h, []);
            
            // Net Pay
            fputcsv($h, ["Net Pay", $payroll->net_pay]);
            fputcsv($h, []);
            
            // Attendance
            fputcsv($h, ["Present Days", $payroll->present_days]);
            fputcsv($h, ["Absent Days", $payroll->absent_days]);
            fputcsv($h, ["Leave Days", $payroll->leave_days]);
            fputcsv($h, ["Overtime Hours", $payroll->overtime_hours]);
            
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }
}

