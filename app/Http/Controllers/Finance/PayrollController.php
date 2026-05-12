<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\User;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('hr_manager');
    }

    // â”€â”€ SALARY STRUCTURES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function salaryStructures()
    {
        $staff      = User::whereIn('user_type', ['teacher', 'admin', 'hr_manager'])
            ->orderBy('name')->get();
        $structures = SalaryStructure::with('staff')->where('active', true)->get()
            ->keyBy('user_id');
        return view('pages.finance.payroll.salary_structures', compact('staff', 'structures'));
    }

    public function storeSalaryStructure(Request $req)
    {
        $req->validate([
            'user_id'                => 'required|exists:users,id',
            'basic_salary'           => 'required|numeric|min:0',
            'housing_allowance'      => 'nullable|numeric|min:0',
            'transport_allowance'    => 'nullable|numeric|min:0',
            'other_allowances'       => 'nullable|numeric|min:0',
            'income_tax_pct'         => 'nullable|numeric|min:0|max:100',
            'loan_repayment'         => 'nullable|numeric|min:0',
            'absence_deduction_rate' => 'nullable|numeric|min:0',
        ]);

        // Deactivate existing
        SalaryStructure::where('user_id', $req->user_id)->update(['active' => false]);

        SalaryStructure::create([
            'user_id'                => $req->user_id,
            'basic_salary'           => $req->basic_salary,
            'housing_allowance'      => $req->housing_allowance ?? 0,
            'transport_allowance'    => $req->transport_allowance ?? 0,
            'other_allowances'       => $req->other_allowances ?? 0,
            'income_tax_pct'         => $req->income_tax_pct ?? 0,
            'loan_repayment'         => $req->loan_repayment ?? 0,
            'absence_deduction_rate' => $req->absence_deduction_rate ?? 0,
            'active'                 => true,
        ]);

        return back()->with('flash_success', 'Salary structure saved successfully.');
    }

    // â”€â”€ PAYROLL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function index(Request $req)
    {
        $query = Payroll::with('staff')->where('voided', false);

        if ($req->filled('month')) $query->where('month', $req->month);
        if ($req->filled('year'))  $query->where('year', $req->year);

        $payrolls    = $query->orderByDesc('year')->orderByDesc('month')->paginate(20)->appends($req->query());
        $total_net   = $query->sum('net_salary');
        $total_gross = $query->sum('gross_salary');

        return view('pages.finance.payroll.index', compact('payrolls', 'total_net', 'total_gross'));
    }

    public function create()
    {
        $staff      = User::whereIn('user_type', ['teacher', 'admin', 'hr_manager'])
            ->orderBy('name')->get();
        $structures = SalaryStructure::with('staff')->where('active', true)->get()->keyBy('user_id');
        return view('pages.finance.payroll.create', compact('staff', 'structures'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'user_id'      => 'required|exists:users,id',
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2000',
            'bonus'        => 'nullable|numeric|min:0',
            'absence_days' => 'nullable|integer|min:0|max:31',
        ]);

        // Duplicate check
        $exists = Payroll::where('user_id', $req->user_id)
            ->where('month', $req->month)
            ->where('year', $req->year)
            ->where('voided', false)
            ->exists();

        if ($exists) {
            return back()->with('flash_danger', 'Payroll for this staff member, month, and year already exists.');
        }

        $struct = SalaryStructure::where('user_id', $req->user_id)->where('active', true)->first();
        if (!$struct) {
            return back()->with('flash_danger', 'No active salary structure found for this staff member.');
        }

        $bonus        = $req->bonus ?? 0;
        $absenceDays  = $req->absence_days ?? 0;
        $gross        = $struct->basic_salary + $struct->housing_allowance + $struct->transport_allowance + $struct->other_allowances + $bonus;
        $incomeTax    = round($struct->income_tax_pct / 100 * $gross, 2);
        $absenceDed   = round($absenceDays * $struct->absence_deduction_rate, 2);
        $totalDed     = $incomeTax + $struct->loan_repayment + $absenceDed;
        $net          = max(0, $gross - $totalDed);

        Payroll::create([
            'user_id'            => $req->user_id,
            'month'              => $req->month,
            'year'               => $req->year,
            'basic_salary'       => $struct->basic_salary,
            'housing_allowance'  => $struct->housing_allowance,
            'transport_allowance'=> $struct->transport_allowance,
            'other_allowances'   => $struct->other_allowances,
            'bonus'              => $bonus,
            'gross_salary'       => $gross,
            'income_tax'         => $incomeTax,
            'loan_repayment'     => $struct->loan_repayment,
            'absence_deduction'  => $absenceDed,
            'total_deductions'   => $totalDed,
            'net_salary'         => $net,
            'absence_days'       => $absenceDays,
            'voided'             => false,
            'processed_at'       => now(),
        ]);

        return redirect()->route('payroll.index')->with('flash_success', 'Payroll processed successfully.');
    }

    public function show($id)
    {
        $payroll = Payroll::with('staff')->findOrFail($id);
        return view('pages.finance.payroll.show', compact('payroll'));
    }

    public function void($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->update(['voided' => true]);
        return back()->with('flash_success', 'Payroll voided successfully.');
    }

    public function exportCsv(Request $req)
    {
        $query = Payroll::with('staff')->where('voided', false);
        if ($req->filled('month')) $query->where('month', $req->month);
        if ($req->filled('year'))  $query->where('year', $req->year);

        $payrolls = $query->orderByDesc('year')->orderByDesc('month')->get();
        $filename = 'payroll_' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $callback = function () use ($payrolls, $months) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Staff', 'Month', 'Year', 'Gross (ETB)', 'Tax (ETB)', 'Loan (ETB)', 'Absence Ded. (ETB)', 'Total Ded. (ETB)', 'Net (ETB)']);
            foreach ($payrolls as $p) {
                fputcsv($h, [
                    $p->staff->name ?? '-',
                    $months[$p->month],
                    $p->year,
                    $p->gross_salary,
                    $p->income_tax,
                    $p->loan_repayment,
                    $p->absence_deduction,
                    $p->total_deductions,
                    $p->net_salary,
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updateSalaryStructure(Request $req, $id)
    {
        $struct = \App\Models\SalaryStructure::findOrFail($id);
        $req->validate([
            'basic_salary'           => 'required|numeric|min:0',
            'housing_allowance'      => 'nullable|numeric|min:0',
            'transport_allowance'    => 'nullable|numeric|min:0',
            'other_allowances'       => 'nullable|numeric|min:0',
            'income_tax_pct'         => 'nullable|numeric|min:0|max:100',
            'loan_repayment'         => 'nullable|numeric|min:0',
            'absence_deduction_rate' => 'nullable|numeric|min:0',
        ]);
        $struct->update([
            'basic_salary'           => $req->basic_salary,
            'housing_allowance'      => $req->housing_allowance ?? 0,
            'transport_allowance'    => $req->transport_allowance ?? 0,
            'other_allowances'       => $req->other_allowances ?? 0,
            'income_tax_pct'         => $req->income_tax_pct ?? 0,
            'loan_repayment'         => $req->loan_repayment ?? 0,
            'absence_deduction_rate' => $req->absence_deduction_rate ?? 0,
        ]);
        return back()->with('flash_success', 'Salary structure updated successfully.');
    }

    public function destroySalaryStructure($id)
    {
        \App\Models\SalaryStructure::findOrFail($id)->delete();
        return back()->with('flash_success', 'Salary structure deleted successfully.');
    }
}