<?php
namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\User;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $req)
    {
        $month = $req->month ?? date('Y-m');
        $d['records'] = PayrollRecord::where('month', $month)->with('staff')->get();
        $d['month'] = $month;
        $d['total_net'] = $d['records']->sum('net_salary');
        $d['total_paid'] = $d['records']->where('status', 'paid')->sum('net_salary');
        return view('pages.finance.payroll.index', $d);
    }

    public function create()
    {
        $d['staff'] = User::whereIn('user_type', Qs::getStaff())->orderBy('name')->get();
        return view('pages.finance.payroll.create', $d);
    }

    public function store(Request $req)
    {
        $req->validate([
            'staff_id'     => 'required|exists:users,id',
            'month'        => 'required',
            'basic_salary' => 'required|numeric|min:0',
            'allowances'   => 'nullable|numeric|min:0',
            'deductions'   => 'nullable|numeric|min:0',
        ]);
        $data = $req->only(['staff_id', 'month', 'basic_salary', 'allowances', 'deductions', 'payment_method', 'notes']);
        $data['allowances'] = $data['allowances'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = $data['basic_salary'] + $data['allowances'] - $data['deductions'];
        PayrollRecord::create($data);
        return redirect()->route('finance.payroll.index')->with('flash_success', 'Payroll record created.');
    }

    public function edit($id)
    {
        $d['record'] = PayrollRecord::findOrFail($id);
        $d['staff'] = User::whereIn('user_type', Qs::getStaff())->orderBy('name')->get();
        return view('pages.finance.payroll.edit', $d);
    }

    public function update(Request $req, $id)
    {
        $req->validate(['basic_salary' => 'required|numeric|min:0']);
        $data = $req->only(['basic_salary', 'allowances', 'deductions', 'payment_method', 'notes', 'status']);
        $data['allowances'] = $data['allowances'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = $data['basic_salary'] + $data['allowances'] - $data['deductions'];
        PayrollRecord::findOrFail($id)->update($data);
        return redirect()->route('finance.payroll.index')->with('flash_success', 'Payroll updated.');
    }

    public function destroy($id)
    {
        PayrollRecord::findOrFail($id)->delete();
        return redirect()->route('finance.payroll.index')->with('flash_success', 'Record deleted.');
    }

    public function markPaid($id)
    {
        PayrollRecord::findOrFail($id)->update(['status' => 'paid']);
        return back()->with('flash_success', 'Marked as paid.');
    }

    /*  SALARY SLIP  */
    public function payslip($id)
    {
        $d['record'] = PayrollRecord::with('staff')->findOrFail($id);
        $d['settings'] = \App\Models\Setting::all()->pluck('description', 'type');
        return view('pages.finance.payroll.payslip', $d);
    }
}
