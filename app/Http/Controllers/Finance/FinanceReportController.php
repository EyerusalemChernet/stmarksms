<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\MyClass;
use App\Models\StudentFeeInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('finance_access');
    }

    public function index()
    {
        return view('pages.finance.reports.index');
    }

    // ── INCOME REPORT ─────────────────────────────────────────────────────────

    public function income(Request $req)
    {
        $date_from = $req->get('date_from', now()->startOfMonth()->toDateString());
        $date_to   = $req->get('date_to', now()->toDateString());

        $payments = FeePayment::with(['invoice.fee_structure.category'])
            ->whereDate('paid_at', '>=', $date_from)
            ->whereDate('paid_at', '<=', $date_to)
            ->get();

        $total = $payments->sum('amount');

        $byCategory = $payments->groupBy(fn($p) => optional(optional($p->invoice->fee_structure)->category)->name ?? 'Unknown')
            ->map(fn($g) => ['total' => $g->sum('amount'), 'count' => $g->count()]);

        if ($req->get('export') === 'csv') {
            return $this->streamCsv("income_{$date_from}_{$date_to}.csv",
                ['Category', 'Transactions', 'Amount (ETB)'],
                $byCategory->map(fn($r, $name) => [$name, $r['count'], $r['total']])->values()->toArray()
            );
        }

        return view('pages.finance.reports.income', compact('payments', 'total', 'byCategory', 'date_from', 'date_to'));
    }

    // ── EXPENSE REPORT ────────────────────────────────────────────────────────

    public function expenses(Request $req)
    {
        $date_from = $req->get('date_from', now()->startOfMonth()->toDateString());
        $date_to   = $req->get('date_to', now()->toDateString());

        $expenses = Expense::with('category')->approved()
            ->whereDate('expense_date', '>=', $date_from)
            ->whereDate('expense_date', '<=', $date_to)
            ->get();

        $total = $expenses->sum('amount');

        $byCategory = $expenses->groupBy(fn($e) => optional($e->category)->name ?? 'Unknown')
            ->map(fn($g) => ['total' => $g->sum('amount'), 'count' => $g->count()]);

        if ($req->get('export') === 'csv') {
            return $this->streamCsv("expenses_{$date_from}_{$date_to}.csv",
                ['Category', 'Transactions', 'Amount (ETB)'],
                $byCategory->map(fn($r, $name) => [$name, $r['count'], $r['total']])->values()->toArray()
            );
        }

        return view('pages.finance.reports.expenses', compact('expenses', 'total', 'byCategory', 'date_from', 'date_to'));
    }

    // ── PROFIT / LOSS ─────────────────────────────────────────────────────────

    public function profitLoss(Request $req)
    {
        $date_from = $req->get('date_from', now()->startOfYear()->toDateString());
        $date_to   = $req->get('date_to', now()->toDateString());

        $income   = FeePayment::whereDate('paid_at', '>=', $date_from)->whereDate('paid_at', '<=', $date_to)->sum('amount');
        $expenses = Expense::approved()->whereDate('expense_date', '>=', $date_from)->whereDate('expense_date', '<=', $date_to)->sum('amount');
        $profit   = $income - $expenses;

        // Monthly breakdown
        $monthly = [];
        $isLite  = DB::connection()->getDriverName() === 'sqlite';
        for ($m = 1; $m <= 12; $m++) {
            $pad = str_pad($m, 2, '0', STR_PAD_LEFT);
            $incQ = FeePayment::whereYear('paid_at', now()->year);
            $expQ = Expense::approved()->whereYear('expense_date', now()->year);
            if ($isLite) {
                $incQ->whereRaw("strftime('%m', paid_at) = ?", [$pad]);
                $expQ->whereRaw("strftime('%m', expense_date) = ?", [$pad]);
            } else {
                $incQ->whereMonth('paid_at', $m);
                $expQ->whereMonth('expense_date', $m);
            }
            $monthly[$m] = [
                'income'   => $incQ->sum('amount'),
                'expenses' => $expQ->sum('amount'),
            ];
            $monthly[$m]['profit'] = $monthly[$m]['income'] - $monthly[$m]['expenses'];
        }

        if ($req->get('export') === 'csv') {
            $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return $this->streamCsv("profit_loss_" . now()->year . ".csv",
                ['Month', 'Income (ETB)', 'Expenses (ETB)', 'Profit/Loss (ETB)'],
                collect($monthly)->map(fn($r, $m) => [$months[$m], $r['income'], $r['expenses'], $r['profit']])->values()->toArray()
            );
        }

        return view('pages.finance.reports.profit_loss', compact('income', 'expenses', 'profit', 'monthly', 'date_from', 'date_to'));
    }

    // ── OUTSTANDING FEES ──────────────────────────────────────────────────────

    public function outstanding(Request $req)
    {
        $session  = $req->get('session', Qs::getCurrentSession());
        $class_id = $req->get('class_id');
        $classes  = MyClass::orderBy('name')->get();

        $query = StudentFeeInvoice::with(['student', 'fee_structure.category', 'fee_structure.my_class'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('session', $session);

        if ($class_id) {
            $query->whereHas('fee_structure', fn($q) => $q->where('my_class_id', $class_id));
        }

        $invoices      = $query->get();
        $total_balance = $invoices->sum('balance');

        if ($req->get('export') === 'csv') {
            return $this->streamCsv("outstanding_fees_{$session}.csv",
                ['Student', 'Class', 'Fee Type', 'Net (ETB)', 'Paid (ETB)', 'Balance (ETB)', 'Status', 'Due Date'],
                $invoices->map(fn($inv) => [
                    $inv->student->name ?? '-',
                    optional($inv->fee_structure->my_class)->name ?? '-',
                    optional($inv->fee_structure->category)->name ?? '-',
                    $inv->net_amount,
                    $inv->amount_paid,
                    $inv->balance,
                    strtoupper($inv->status),
                    $inv->due_date ?? '-',
                ])->toArray()
            );
        }

        return view('pages.finance.reports.outstanding', compact('invoices', 'total_balance', 'classes', 'session', 'class_id'));
    }


    // ── HELPER ────────────────────────────────────────────────────────────────

    protected function streamCsv(string $filename, array $headers, array $rows)
    {
        $httpHeaders = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback    = function () use ($headers, $rows) {
            $h = fopen('php://output', 'w');
            fputcsv($h, $headers);
            foreach ($rows as $row) fputcsv($h, $row);
            fclose($h);
        };
        return response()->stream($callback, 200, $httpHeaders);
    }
}
