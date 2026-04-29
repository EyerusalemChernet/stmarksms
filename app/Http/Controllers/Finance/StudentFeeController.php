<?php
namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\FeePayment;
use App\Models\MyClass;
use App\Models\StudentFeeInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentFeeController extends Controller
{
    /*  FEE CATEGORIES  */
    public function categories()
    {
        $d['categories'] = FeeCategory::withCount('structures')->get();
        return view('pages.finance.fees.categories', $d);
    }

    public function storeCategory(Request $req)
    {
        $req->validate(['name' => 'required|string|max:100', 'code' => 'required|string|max:10|unique:fee_categories,code']);
        FeeCategory::create($req->only(['name', 'code', 'description']));
        return back()->with('flash_success', 'Fee category created.');
    }

    public function updateCategory(Request $req, $id)
    {
        $req->validate(['name' => 'required|string|max:100']);
        FeeCategory::findOrFail($id)->update($req->only(['name', 'description', 'active']));
        return back()->with('flash_success', 'Category updated.');
    }

    public function destroyCategory($id)
    {
        FeeCategory::findOrFail($id)->delete();
        return back()->with('flash_success', 'Category deleted.');
    }

    /*  FEE STRUCTURES  */
    public function structures()
    {
        $d['structures'] = FeeStructure::with(['category', 'my_class'])->orderBy('session', 'desc')->get();
        $d['categories'] = FeeCategory::where('active', true)->get();
        $d['classes']    = MyClass::orderBy('name')->get();
        $d['session']    = Qs::getCurrentSession();
        return view('pages.finance.fees.structures', $d);
    }

    public function storeStructure(Request $req)
    {
        $req->validate([
            'fee_category_id' => 'required|exists:fee_categories,id',
            'my_class_id'     => 'required|exists:my_classes,id',
            'amount'          => 'required|numeric|min:1',
            'installments'    => 'required|integer|min:1|max:12',
        ]);
        $data = $req->only(['fee_category_id', 'my_class_id', 'amount', 'installments']);
        $data['session'] = Qs::getCurrentSession();
        FeeStructure::updateOrCreate(
            ['fee_category_id' => $data['fee_category_id'], 'my_class_id' => $data['my_class_id'], 'session' => $data['session']],
            $data
        );
        return back()->with('flash_success', 'Fee structure saved.');
    }

    public function destroyStructure($id)
    {
        FeeStructure::findOrFail($id)->delete();
        return back()->with('flash_success', 'Structure deleted.');
    }

    /*  INVOICES  */
    public function invoices(Request $req)
    {
        $session = $req->session_filter ?? Qs::getCurrentSession();
        $query   = StudentFeeInvoice::with(['student', 'fee_structure.category', 'fee_structure.my_class'])
                    ->where('session', $session);
        if ($req->status)   $query->where('status', $req->status);
        if ($req->class_id) $query->whereHas('fee_structure', fn($q) => $q->where('my_class_id', $req->class_id));
        $d['invoices'] = $query->orderBy('created_at', 'desc')->get();
        $d['classes']  = MyClass::orderBy('name')->get();
        $d['session']  = $session;
        return view('pages.finance.fees.invoices', $d);
    }

    /*  ASSIGN FEE TO STUDENT  */
    public function assignFee(Request $req)
    {
        $req->validate([
            'student_id'       => 'required|exists:users,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'discount'         => 'nullable|numeric|min:0',
            'fine'             => 'nullable|numeric|min:0',
            'due_date'         => 'nullable|date',
        ]);
        $structure = FeeStructure::findOrFail($req->fee_structure_id);
        $discount  = (float)($req->discount ?? 0);
        $fine      = (float)($req->fine ?? 0);
        $net       = $structure->amount - $discount + $fine;

        $exists = StudentFeeInvoice::where('student_id', $req->student_id)
                    ->where('fee_structure_id', $req->fee_structure_id)->exists();
        if ($exists) return back()->with('flash_danger', 'Invoice already exists for this student and fee.');

        StudentFeeInvoice::create([
            'invoice_no'       => 'INV-' . strtoupper(uniqid()),
            'student_id'       => $req->student_id,
            'fee_structure_id' => $req->fee_structure_id,
            'session'          => Qs::getCurrentSession(),
            'original_amount'  => $structure->amount,
            'discount'         => $discount,
            'discount_reason'  => $req->discount_reason,
            'fine'             => $fine,
            'fine_reason'      => $req->fine_reason,
            'net_amount'       => $net,
            'amount_paid'      => 0,
            'balance'          => $net,
            'status'           => 'unpaid',
            'due_date'         => $req->due_date,
        ]);
        return back()->with('flash_success', 'Fee assigned to student.');
    }

    /*  BULK ASSIGN FEES TO ENTIRE CLASS  */
    public function bulkAssign(Request $req)
    {
        $req->validate([
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'my_class_id'      => 'required|exists:my_classes,id',
        ]);
        $structure = FeeStructure::findOrFail($req->fee_structure_id);
        $students  = \App\Models\StudentRecord::where('my_class_id', $req->my_class_id)->get();
        $count = 0;
        foreach ($students as $sr) {
            $exists = StudentFeeInvoice::where('student_id', $sr->user_id)
                        ->where('fee_structure_id', $structure->id)->exists();
            if (!$exists) {
                StudentFeeInvoice::create([
                    'invoice_no'       => 'INV-' . strtoupper(uniqid()),
                    'student_id'       => $sr->user_id,
                    'fee_structure_id' => $structure->id,
                    'session'          => Qs::getCurrentSession(),
                    'original_amount'  => $structure->amount,
                    'discount'         => 0,
                    'net_amount'       => $structure->amount,
                    'amount_paid'      => 0,
                    'balance'          => $structure->amount,
                    'status'           => 'unpaid',
                ]);
                $count++;
            }
        }
        return back()->with('flash_success', "$count invoices created for the class.");
    }

    /*  STUDENT PAYMENT HISTORY  */
    public function studentHistory($student_id)
    {
        $d['invoices'] = StudentFeeInvoice::with(['fee_structure.category', 'fee_structure.my_class', 'payments'])
                            ->where('student_id', $student_id)->orderBy('created_at', 'desc')->get();
        $d['student']  = \App\User::findOrFail($student_id);
        $d['total_due']  = $d['invoices']->sum('net_amount');
        $d['total_paid'] = $d['invoices']->sum('amount_paid');
        $d['balance']    = $d['total_due'] - $d['total_paid'];
        return view('pages.finance.fees.student_history', $d);
    }

    /*  INVOICE DETAIL + PAY  */
    public function invoiceDetail($id)
    {
        $d['invoice']  = StudentFeeInvoice::with(['student', 'fee_structure.category', 'fee_structure.my_class', 'payments.collector'])
                            ->findOrFail($id);
        $d['installment_no'] = $d['invoice']->payments()->count() + 1;
        return view('pages.finance.fees.invoice_detail', $d);
    }

    /*  RECORD PAYMENT  */
    public function recordPayment(Request $req, $invoice_id)
    {
        $req->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,chapa',
        ]);
        $invoice = StudentFeeInvoice::findOrFail($invoice_id);
        if ($invoice->status === 'paid') return back()->with('flash_danger', 'Invoice is already fully paid.');

        $maxPayable = $invoice->balance;
        $amount     = min((float)$req->amount, $maxPayable);

        FeePayment::create([
            'receipt_no'      => 'RCP-' . strtoupper(uniqid()),
            'invoice_id'      => $invoice->id,
            'student_id'      => $invoice->student_id,
            'collected_by'    => Auth::id(),
            'amount'          => $amount,
            'installment_no'  => $invoice->payments()->count() + 1,
            'payment_method'  => $req->payment_method,
            'transaction_ref' => $req->transaction_ref,
            'notes'           => $req->notes,
            'paid_at'         => now(),
        ]);

        $invoice->syncStatus();
        return redirect()->route('fees.invoice', $invoice_id)->with('flash_success', 'Payment of ETB ' . number_format($amount, 2) . ' recorded.');
    }

    /*  APPLY DISCOUNT  */
    public function applyDiscount(Request $req, $invoice_id)
    {
        $req->validate(['discount' => 'required|numeric|min:0', 'discount_reason' => 'nullable|string|max:200']);
        $invoice = StudentFeeInvoice::findOrFail($invoice_id);
        $net = $invoice->original_amount - (float)$req->discount + $invoice->fine;
        $invoice->update([
            'discount'        => $req->discount,
            'discount_reason' => $req->discount_reason,
            'net_amount'      => max(0, $net),
            'balance'         => max(0, $net - $invoice->amount_paid),
        ]);
        $invoice->syncStatus();
        return back()->with('flash_success', 'Discount applied.');
    }

    /*  APPLY FINE  */
    public function applyFine(Request $req, $invoice_id)
    {
        $req->validate(['fine' => 'required|numeric|min:0', 'fine_reason' => 'nullable|string|max:200']);
        $invoice = StudentFeeInvoice::findOrFail($invoice_id);
        $net = $invoice->original_amount - $invoice->discount + (float)$req->fine;
        $invoice->update([
            'fine'        => $req->fine,
            'fine_reason' => $req->fine_reason,
            'net_amount'  => $net,
            'balance'     => max(0, $net - $invoice->amount_paid),
        ]);
        $invoice->syncStatus();
        return back()->with('flash_success', 'Fine applied.');
    }

    /*  RECEIPT  */
    public function receipt($payment_id)
    {
        $d['payment'] = FeePayment::with(['invoice.fee_structure.category', 'invoice.fee_structure.my_class', 'student', 'collector'])
                            ->findOrFail($payment_id);
        $d['settings'] = \App\Models\Setting::all()->pluck('description', 'type');
        return view('pages.finance.fees.receipt', $d);
    }

    /*  PENDING BALANCE LIST  */
    public function pendingList(Request $req)
    {
        $session = $req->session_filter ?? Qs::getCurrentSession();
        $query   = StudentFeeInvoice::with(['student', 'fee_structure.category', 'fee_structure.my_class'])
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->where('session', $session);
        if ($req->class_id) $query->whereHas('fee_structure', fn($q) => $q->where('my_class_id', $req->class_id));
        $d['invoices']     = $query->orderBy('balance', 'desc')->get();
        $d['classes']      = MyClass::orderBy('name')->get();
        $d['session']      = $session;
        $d['total_pending'] = $d['invoices']->sum('balance');
        return view('pages.finance.fees.pending', $d);
    }

    /*  PAYMENTS LIST  */
    public function payments(Request $req)
    {
        $session = Qs::getCurrentSession();
        $query   = FeePayment::with(['invoice.fee_structure.category', 'student', 'collector'])
                    ->whereHas('invoice', fn($q) => $q->where('session', $session));

        if ($req->method) $query->where('payment_method', $req->method);
        if ($req->date_from) $query->whereDate('paid_at', '>=', $req->date_from);
        if ($req->date_to)   $query->whereDate('paid_at', '<=', $req->date_to);
        if ($req->search) {
            $query->whereHas('student', function($q) use ($req) {
                $q->where('name', 'LIKE', "%{$req->search}%");
            });
        }

        $d['payments']    = $query->orderBy('paid_at', 'desc')->paginate(20);
        $d['total_today'] = FeePayment::whereDate('paid_at', now())->sum('amount');
        $d['total_month'] = FeePayment::whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount');

        return view('pages.finance.fees.payments', $d);
    }
}

