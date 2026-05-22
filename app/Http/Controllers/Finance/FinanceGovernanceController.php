<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FinanceGovernanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('finance_access')->except([
            'auditLogs', 'transactionLogs', 'restoreIndex', 'chapaSettings'
        ]);
    }

    /**
     * Show refunds list
     */
    public function refunds(Request $request)
    {
        return view('pages.finance.governance.refunds');
    }

    /**
     * Show reconciliation page
     */
    public function reconciliation(Request $request)
    {
        return view('pages.finance.governance.reconciliation');
    }

    /**
     * Show receipts index
     */
    public function receiptsIndex(Request $request)
    {
        return view('pages.finance.governance.receipts');
    }

    /**
     * Show audit logs (admin only)
     */
    public function auditLogs(Request $request)
    {
        $this->authorize('isAdmin');
        return view('pages.finance.governance.audit_logs');
    }

    /**
     * Show transaction logs (admin only)
     */
    public function transactionLogs(Request $request)
    {
        $this->authorize('isAdmin');
        return view('pages.finance.governance.transaction_logs');
    }

    /**
     * Show restore index (admin only)
     */
    public function restoreIndex(Request $request)
    {
        $this->authorize('isAdmin');
        return view('pages.finance.governance.restore');
    }

    /**
     * Show Chapa settings (admin only)
     */
    public function chapaSettings(Request $request)
    {
        $this->authorize('isAdmin');
        return view('pages.finance.governance.chapa_settings');
    }
}
