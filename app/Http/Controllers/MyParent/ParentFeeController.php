<?php

namespace App\Http\Controllers\MyParent;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\StudentFeeInvoice;
use App\Models\StudentRecord;
use App\Services\DiscountService;
use App\Services\PenaltyService;
use Illuminate\Support\Facades\Auth;

class ParentFeeController extends Controller
{
    /** All school fee invoices for this parent's children */
    public function index()
    {
        $parentId = Auth::id();
        $childIds = StudentRecord::where('my_parent_id', $parentId)->where('grad', 0)->pluck('user_id');

        $familyInfo = DiscountService::getFamilyInfoForParent($parentId);
        $penaltyInfo = PenaltyService::penaltySummaryForParent();

        $invoices = StudentFeeInvoice::with([
            'student',
            'fee_structure.category',
            'fee_structure.my_class',
            'payments',
        ])
            ->whereIn('student_id', $childIds)
            ->orderByDesc('created_at')
            ->get();

        foreach ($invoices as $invoice) {
            if (in_array($invoice->status, ['unpaid', 'partial'], true)) {
                PenaltyService::syncPenaltyForInvoice($invoice);
            }
        }

        $totals = [
            'original'  => $invoices->sum('original_amount'),
            'discount'  => $invoices->sum('discount'),
            'fine'      => $invoices->sum('fine'),
            'net'       => $invoices->sum('net_amount'),
            'paid'      => $invoices->sum('amount_paid'),
            'balance'   => $invoices->sum('balance'),
            'unpaid'    => $invoices->whereIn('status', ['unpaid', 'partial'])->count(),
        ];

        return view('pages.parent.fees', compact('invoices', 'familyInfo', 'penaltyInfo', 'totals'));
    }

    /** Single invoice detail for a parent's child */
    public function show($id)
    {
        $parentId = Auth::id();
        $id       = Qs::decodeHash($id);

        $invoice = StudentFeeInvoice::with([
            'student',
            'fee_structure.category',
            'fee_structure.my_class',
            'payments',
        ])->findOrFail($id);

        $ownsChild = StudentRecord::where('user_id', $invoice->student_id)
            ->where('my_parent_id', $parentId)
            ->where('grad', 0)
            ->exists();

        if (!$ownsChild) {
            return redirect()->route('parent.fees')->with('flash_danger', 'Invoice not found.');
        }

        if (in_array($invoice->status, ['unpaid', 'partial'], true)) {
            PenaltyService::syncPenaltyForInvoice($invoice);
            $invoice->refresh();
        }

        $sr = StudentRecord::where('user_id', $invoice->student_id)->where('grad', 0)->first();
        $discountType = $sr ? DiscountService::getDiscountTypeForStudent($sr) : null;
        $familyInfo   = DiscountService::getFamilyInfoForParent($parentId);
        $penaltyInfo  = PenaltyService::penaltySummaryForParent();
        $isOverdue    = PenaltyService::isOverdue($invoice);

        return view('pages.parent.fee_invoice', compact(
            'invoice', 'discountType', 'familyInfo', 'penaltyInfo', 'isOverdue'
        ));
    }
}
