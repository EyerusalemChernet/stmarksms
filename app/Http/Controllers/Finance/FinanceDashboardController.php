<?php
namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\Income;
use App\Models\PayrollRecord;
use App\Models\StudentFeeInvoice;
use App\Models\TransportRecord;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        $session = Qs::getCurrentSession();
        $year    = (int) explode('-', $session)[0];

        $d['total_fees_collected'] = FeePayment::whereYear('paid_at', $year)->sum('amount');
        $d['total_pending']        = StudentFeeInvoice::where('session', $session)->whereIn('status',['unpaid','partial'])->sum('balance');
        $d['total_expenses']       = Expense::where('year', $session)->sum('amount');
        $d['salary_paid']          = PayrollRecord::where('status','paid')->whereYear('created_at', $year)->sum('net_salary');
        $d['other_income']         = Income::where('year', $session)->sum('amount');
        $d['net_balance']          = $d['total_fees_collected'] + $d['other_income'] - $d['total_expenses'] - $d['salary_paid'];

        // Monthly fee collection for chart (12 months)
        $monthly = array_fill(0, 12, 0);
        $monthExpr = DB::getDriverName() === 'sqlite' ? 'strftime("%m", paid_at) + 0' : 'MONTH(paid_at)';
        $rows = FeePayment::selectRaw($monthExpr.' as m, SUM(amount) as total')
                    ->whereYear('paid_at', $year)->groupBy('m')->get();
        foreach ($rows as $r) $monthly[$r->m - 1] = (float)$r->total;
        $d['monthly_collection'] = $monthly;

        // Monthly expenses
        $mexp = array_fill(0, 12, 0);
        $monthExprExp = DB::getDriverName() === 'sqlite' ? 'strftime("%m", expense_date) + 0' : 'MONTH(expense_date)';
        $erows = Expense::selectRaw($monthExprExp.' as m, SUM(amount) as total')
                    ->whereYear('expense_date', $year)->groupBy('m')->get();
        foreach ($erows as $r) $mexp[$r->m - 1] = (float)$r->total;
        $d['monthly_expenses'] = $mexp;

        // Recent payments
        $d['recent_payments'] = FeePayment::with(['student','invoice.fee_structure.category'])
                                    ->orderBy('paid_at','desc')->take(8)->get();

        // Fee status breakdown
        $d['status_counts'] = [
            'paid'    => StudentFeeInvoice::where('session',$session)->where('status','paid')->count(),
            'partial' => StudentFeeInvoice::where('session',$session)->where('status','partial')->count(),
            'unpaid'  => StudentFeeInvoice::where('session',$session)->where('status','unpaid')->count(),
        ];

        return view('pages.finance.dashboard', $d);
    }
}
