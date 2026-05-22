<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinancePermission as FP;
use App\Services\PenaltyService;
use Illuminate\Http\Request;

class PenaltyRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('finance_access');
    }

    public function index()
    {
        FP::require('view_penalty_rules');

        $canManage = FP::has('manage_penalty_rules');
        $rules     = PenaltyService::getRules();

        return view('pages.finance.penalties.index', compact('canManage', 'rules'));
    }

    public function update(Request $req)
    {
        FP::require('manage_penalty_rules');

        $req->validate([
            'late_fee_grace_days' => 'required|integer|min:0|max:365',
            'late_fee_type'       => 'required|in:percent,fixed',
            'late_fee_amount'     => 'required|numeric|min:0',
        ]);

        PenaltyService::setRules(
            $req->has('late_fee_enabled'),
            (int) $req->late_fee_grace_days,
            $req->late_fee_type,
            (float) $req->late_fee_amount
        );

        FP::audit('updated', 'penalty_rules', null, null, [], $req->only([
            'late_fee_enabled', 'late_fee_grace_days', 'late_fee_type', 'late_fee_amount',
        ]));

        return redirect()->route('penalties.index')
            ->with('flash_success', 'Penalty rules saved successfully.');
    }

    public function applyNow(Request $req)
    {
        FP::require('manage_penalty_rules');

        if (!PenaltyService::isEnabled()) {
            return back()->with('flash_danger', 'Penalty rules are disabled. Enable them first.');
        }

        $count = PenaltyService::applyToOverdueInvoices();

        return back()->with('flash_success', "Penalties applied to {$count} overdue invoice(s).");
    }
}
