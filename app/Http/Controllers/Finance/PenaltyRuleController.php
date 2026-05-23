<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinancePermission as FP;
use App\Services\ParentOverdueFeeNotifier;
use App\Services\PenaltyService;
use Illuminate\Support\Facades\Auth;
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
            'late_fee_grace_days'        => 'required|integer|min:0|max:365',
            'late_fee_type'              => 'required|in:percent,fixed',
            'late_fee_amount'            => 'required|numeric|min:0',
            'late_fee_default_due_days'  => 'required|integer|min:1|max:365',
            'late_fee_notify_after_days' => 'required|integer|min:0|max:365',
            'late_fee_penalty_frequency' => 'required|in:once,daily,weekly',
        ]);

        PenaltyService::setRules(
            $req->has('late_fee_enabled'),
            (int) $req->late_fee_grace_days,
            $req->late_fee_type,
            (float) $req->late_fee_amount,
            (int) $req->late_fee_default_due_days,
            (int) $req->late_fee_notify_after_days,
            $req->late_fee_penalty_frequency
        );

        FP::audit('updated', 'penalty_rules', null, null, [], $req->only([
            'late_fee_enabled', 'late_fee_grace_days', 'late_fee_type', 'late_fee_amount',
            'late_fee_default_due_days', 'late_fee_notify_after_days', 'late_fee_penalty_frequency',
        ]));

        $notifyStats = ['messages' => 0, 'penalties' => 0];
        if ($req->has('late_fee_enabled')) {
            ParentOverdueFeeNotifier::postParentsPolicyAnnouncement(Auth::id());
            $notifyStats = ParentOverdueFeeNotifier::run();
        }

        $msg = 'Penalty rules saved successfully.';
        if ($notifyStats['messages'] > 0) {
            $msg .= ' ' . $notifyStats['messages'] . ' parent reminder(s) sent for overdue fees.';
        }

        return redirect()->route('penalties.index')->with('flash_success', $msg);
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
