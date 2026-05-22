<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\PaymentRecord;
use App\Models\StudentFeeInvoice;
use App\Services\FeeUnificationService;
use Illuminate\Http\Request;

class LegacyPaymentRedirectController extends Controller
{
    public function index()
    {
        return redirect()->route('fees.structures')
            ->with('flash_success', 'School fees are now managed under Finance → Fee Management.');
    }

    public function create()
    {
        return redirect()->route('fees.structures');
    }

    public function store(Request $request)
    {
        return redirect()->route('fees.structures')
            ->with('flash_success', 'Create fee types under Finance → Fee Categories & Structures.');
    }

    public function show($id)
    {
        return redirect()->route('fees.invoices');
    }

    public function edit($id)
    {
        return redirect()->route('fees.structures');
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('fees.structures');
    }

    public function destroy($id)
    {
        return redirect()->route('fees.structures');
    }

    public function manage($class_id = null)
    {
        $params = $class_id ? ['class_id' => $class_id] : [];
        return redirect()->route('fees.invoices', $params)
            ->with('flash_success', 'Fee collection has moved to Finance → Invoices.');
    }

    public function invoice($student_id, $year = null)
    {
        $uid = Qs::decodeHash($student_id);

        $invoice = StudentFeeInvoice::where('student_id', $uid)
            ->when($year, fn($q) => $q->where('session', $year))
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderByDesc('id')
            ->first();

        if ($invoice) {
            return redirect()->route('fees.invoice', $invoice->id);
        }

        PaymentRecord::where('student_id', $uid)->whereNull('migrated_to_invoice_id')->each(function ($pr) {
            try {
                (new FeeUnificationService())->migratePaymentRecord($pr);
            } catch (\Throwable $e) {
                // skip broken rows
            }
        });

        $invoice = StudentFeeInvoice::where('student_id', $uid)
            ->when($year, fn($q) => $q->where('session', $year))
            ->orderByDesc('id')
            ->first();

        if ($invoice) {
            return redirect()->route('fees.invoice', $invoice->id);
        }

        return redirect()->route('fees.invoices')
            ->with('flash_danger', 'No fee invoices found for this student. Assign fees from Fee Structures.');
    }

    public function receipts($id)
    {
        $pr_id = Qs::decodeHash($id);
        try {
            $invoice = FeeUnificationService::ensureInvoiceForPaymentRecord($pr_id);
            return redirect()->route('fees.receipt', $invoice->payments()->latest('id')->value('id') ?? 0)
                ->with('flash_success', 'Showing receipt from unified fee system.');
        } catch (\Throwable $e) {
            return redirect()->route('fees.payments');
        }
    }

    public function selectYear(Request $request)
    {
        return redirect()->route('fees.structures');
    }

    public function selectClass(Request $request)
    {
        return redirect()->route('fees.structures')
            ->with('flash_success', 'Use Fee Structures and Bulk Assign to assign fees to a class.');
    }
}
