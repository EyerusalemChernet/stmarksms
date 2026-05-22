<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\StudentFeeInvoice;
use App\Services\ChapaService;
use App\Services\FeeUnificationService;
use Illuminate\Http\Request;

class ChapaPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function history(Request $request)
    {
        $payments = FeePayment::with(['invoice.student', 'invoice.fee_structure.category'])
            ->where('payment_method', 'chapa')
            ->latest('paid_at')
            ->paginate(25);

        return view('pages.finance.payments.chapa_history', compact('payments'));
    }

    public function initiateFeePay(Request $request, $invoice_id, ChapaService $chapa)
    {
        $invoice = $this->resolveInvoice($invoice_id);

        if (auth()->user()->user_type === 'parent' && !$chapa->parentOwnsInvoice($invoice)) {
            abort(403);
        }

        return $chapa->initiateForInvoice(
            $invoice,
            auth()->user()->user_type === 'parent' ? 'parent.fee.chapa.return' : 'chapa.fee.return',
            auth()->user()->user_type === 'parent'
                ? ['id' => Qs::hash($invoice->id)]
                : ['invoice_id' => Qs::hash($invoice->id)]
        );
    }

    public function returnFeePay(Request $request, $invoice_id, ChapaService $chapa)
    {
        $invoice = $this->resolveInvoice($invoice_id);

        if ($invoice->chapa_ref) {
            $chapa->processByTxRef($invoice->chapa_ref);
            $invoice->refresh();
        }

        $route = auth()->user()->user_type === 'parent' ? 'parent.fee' : 'fees.invoice';
        $param = auth()->user()->user_type === 'parent' ? $invoice->id : $invoice->id;

        if ($invoice->chapa_status === 'success') {
            return redirect()->route($route, Qs::hash($param))
                ->with('flash_success', 'Payment completed successfully via Chapa.');
        }

        return redirect()->route($route, Qs::hash($param))
            ->with('flash_danger', 'Payment could not be verified. Please contact the school office.');
    }

    public function webhook(Request $request, ChapaService $chapa)
    {
        $txRef = $request->input('trx_ref') ?? $request->input('tx_ref');
        if ($txRef) {
            $chapa->processByTxRef($txRef);
        }

        return response()->json(['status' => 'ok']);
    }

    public function initiateSalaryPay(Request $request, $payroll_id)
    {
        return back()->with('flash_danger', 'Chapa salary payments are not available yet.');
    }

    public function returnSalaryPay(Request $request, $payroll_id)
    {
        return view('pages.finance.payments.chapa_return');
    }

    public function initiateExpensePay(Request $request, $expense_id)
    {
        return back()->with('flash_danger', 'Chapa expense payments are not available yet.');
    }

    public function returnExpensePay(Request $request, $expense_id)
    {
        return view('pages.finance.payments.chapa_return');
    }

    protected function resolveInvoice($invoice_id): StudentFeeInvoice
    {
        $id = is_numeric($invoice_id) ? (int) $invoice_id : Qs::decodeHash($invoice_id);
        return StudentFeeInvoice::findOrFail($id);
    }
}
