<?php
namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\Income;
use App\Models\MyClass;
use App\Models\PayrollRecord;
use App\Models\StudentFeeInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function index(Request $req)
    {
        $session = $req->session_filter ?? Qs::getCurrentSession();
        $year    = (int) explode('-', $session)[0];

        $d['session']              = $session;
        $d['total_fees_collected'] = FeePayment::whereYear('paid_at', $year)->sum('amount');
        $d['total_pending']        = StudentFeeInvoice::where('session',$session)->whereIn('status',['unpaid','partial'])->sum('balance');
        $d['total_expenses']       = Expense::where('year',$session)->sum('amount');
        $d['total_income']         = Income::where('year',$session)->sum('amount');
        $d['salary_paid']          = PayrollRecord::where('status','paid')->whereYear('created_at',$year)->sum('net_salary');
        $d['net_balance']          = $d['total_fees_collected'] + $d['total_income'] - $d['total_expenses'] - $d['salary_paid'];

        // Daily collection (last 30 days)
        $d['daily_collection'] = FeePayment::selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->where('paid_at', '>=', now()->subDays(29))
            ->groupBy('day')->orderBy('day')->get();

        // Monthly summary
        $monthly = array_fill(0, 12, ['fees'=>0,'expenses'=>0,'income'=>0]);
        $mExprFee = DB::getDriverName() === 'sqlite' ? 'strftime("%m", paid_at) + 0' : 'MONTH(paid_at)';
        $frows = FeePayment::selectRaw($mExprFee.' as m, SUM(amount) as t')->whereYear('paid_at',$year)->groupBy('m')->get();
        foreach ($frows as $r) $monthly[$r->m-1]['fees'] = (float)$r->t;
        $mExprExp = DB::getDriverName() === 'sqlite' ? 'strftime("%m", expense_date) + 0' : 'MONTH(expense_date)';
        $erows = Expense::selectRaw($mExprExp.' as m, SUM(amount) as t')->whereYear('expense_date',$year)->groupBy('m')->get();
        foreach ($erows as $r) $monthly[$r->m-1]['expenses'] = (float)$r->t;
        $mExprInc = DB::getDriverName() === 'sqlite' ? 'strftime("%m", income_date) + 0' : 'MONTH(income_date)';
        $irows = Income::selectRaw($mExprInc.' as m, SUM(amount) as t')->whereYear('income_date',$year)->groupBy('m')->get();
        foreach ($irows as $r) $monthly[$r->m-1]['income'] = (float)$r->t;
        $d['monthly'] = $monthly;

        // Payroll summary
        $d['payroll_summary'] = PayrollRecord::with('staff')
            ->selectRaw('staff_id, SUM(net_salary) as total, COUNT(*) as months, MAX(status) as status')
            ->whereYear('created_at',$year)->groupBy('staff_id')->get();

        // Expense breakdown by category
        $d['expense_by_cat'] = Expense::with('category')->where('year',$session)
            ->selectRaw('category_id, SUM(amount) as total')->groupBy('category_id')->get();

        // Pending by class
        $d['pending_by_class'] = StudentFeeInvoice::with('fee_structure.my_class')
            ->where('session',$session)->whereIn('status',['unpaid','partial'])
            ->selectRaw('fee_structure_id, SUM(balance) as total, COUNT(*) as cnt')
            ->groupBy('fee_structure_id')->get();

        $d['classes'] = MyClass::orderBy('name')->get();
        return view('pages.finance.reports', $d);
    }
}
