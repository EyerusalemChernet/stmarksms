<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Services\ChapaService;
use App\Services\FeeUnificationService;
use Illuminate\Http\Request;

class ChapaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Legacy route: migrate payment record to invoice, then start Chapa checkout.
     */
    public function initiate(Request $req, $pr_id, ChapaService $chapa)
    {
        $pr_id = Qs::decodeHash($pr_id);
        $invoice = FeeUnificationService::ensureInvoiceForPaymentRecord($pr_id);

        return $chapa->initiateForInvoice($invoice, 'chapa.return', ['pr_id' => Qs::hash($pr_id)]);
    }

    public function callback(Request $req, ChapaService $chapa)
    {
        $txRef = $req->input('trx_ref') ?? $req->input('tx_ref');
        if (!$txRef) {
            return response('Missing tx_ref', 400);
        }

        $chapa->processByTxRef($txRef);
        return response('OK', 200);
    }

    public function returnUrl(Request $req, $pr_id, ChapaService $chapa)
    {
        $pr_id = Qs::decodeHash($pr_id);
        $invoice = FeeUnificationService::ensureInvoiceForPaymentRecord($pr_id);

        if ($invoice->chapa_ref) {
            $chapa->processByTxRef($invoice->chapa_ref);
            $invoice->refresh();
        }

        if ($invoice->chapa_status === 'success') {
            return redirect()->route('fees.invoice', $invoice->id)
                ->with('flash_success', 'Payment completed successfully via Chapa.');
        }

        return redirect()->route('fees.invoice', $invoice->id)
            ->with('flash_danger', 'Payment could not be verified. Please contact the school office.');
    }
}
