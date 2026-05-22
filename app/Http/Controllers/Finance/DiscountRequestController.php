<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\DiscountRequest;
use App\Models\StudentFeeInvoice;
use App\Services\DiscountService;
use App\Services\FinancePermission as FP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiscountRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('finance_access')->except([
            'approve', 'reject',
        ]);
    }

    // ── LIST ──────────────────────────────────────────────────────────────────

    public function index(Request $req)
    {
        FP::require('view_invoices');

        $canApprove = FP::has('approve_fee_discount');

        $query = DiscountRequest::with(['invoice.fee_structure.category', 'student', 'requester', 'reviewer'])
            ->latest();

        if ($req->filled('status'))  $query->where('status', $req->status);
        if ($req->filled('type'))    $query->where('discount_type', $req->type);
        if ($req->filled('search'))  $query->whereHas('student', fn($q) => $q->where('name', 'like', '%'.$req->search.'%'));

        // Accountants see only their own requests; admins see all for approval
        if (!$canApprove) {
            $query->where('requested_by', Auth::id());
        }

        $requests     = $query->paginate(20)->appends($req->query());
        $pendingCount = $canApprove
            ? DiscountRequest::where('status', 'pending')->count()
            : DiscountRequest::where('status', 'pending')->where('requested_by', Auth::id())->count();

        return view('pages.finance.discounts.index', compact('requests', 'canApprove', 'pendingCount'));
    }



    // ── CREATE (Accountant) ───────────────────────────────────────────────────

    public function create($invoice_id)
    {
        FP::require('apply_discounts');
        $invoice_id = \App\Helpers\Qs::decodeHash($invoice_id);
        $invoice = StudentFeeInvoice::with(['student', 'fee_structure.category'])->findOrFail($invoice_id);

        if ($invoice->balance <= 0) {
            return back()->with('flash_danger', 'This invoice is already fully paid.');
        }

        $types = [
            'sibling'        => 'Sibling Discount',
            'employee_child' => 'Employee Child Discount',
            'scholarship'    => 'Scholarship',
            'hardship'       => 'Financial Hardship',
            'other'          => 'Other',
        ];

        // Check for existing pending request
        $existing = DiscountRequest::where('invoice_id', $invoice->id)
            ->where('status', 'pending')->first();

        return view('pages.finance.discounts.create', compact('invoice', 'types', 'existing'));
    }

    public function store(Request $req, $invoice_id)
    {
        FP::require('apply_discounts');
        $invoice_id = \App\Helpers\Qs::decodeHash($invoice_id);
        $invoice = StudentFeeInvoice::findOrFail($invoice_id);

        if ($invoice->balance <= 0) {
            return back()->with('flash_danger', 'Invoice is already paid.');
        }

        // Prevent duplicate pending request
        $existing = DiscountRequest::where('invoice_id', $invoice->id)
            ->where('status', 'pending')->exists();
        if ($existing) {
            return back()->with('flash_danger', 'A pending discount request already exists for this invoice.');
        }

        $req->validate([
            'discount_type'    => 'required|in:sibling,employee_child,scholarship,hardship,other',
            'requested_amount' => 'required|numeric|min:1|max:' . $invoice->balance,
            'reason'           => 'required|string|min:10|max:1000',
            'supporting_info'  => 'nullable|string|max:500',
        ]);

        $dr = DiscountRequest::create([
            'invoice_id'       => $invoice->id,
            'student_id'       => $invoice->student_id,
            'requested_by'     => Auth::id(),
            'discount_type'    => $req->discount_type,
            'requested_amount' => $req->requested_amount,
            'reason'           => $req->reason,
            'supporting_info'  => $req->supporting_info,
            'status'           => 'pending',
        ]);

        FP::audit('created', 'discount_request', DiscountRequest::class, $dr->id, [], $dr->toArray());

        return redirect()->route('discount_requests.index')
            ->with('flash_success', 'Discount request submitted. Awaiting admin approval.');
    }

    // ── APPROVE (Admin / Super Admin) ─────────────────────────────────────────

    public function approve(Request $req, $request_id)
    {
        FP::require('approve_fee_discount');

        $dr = DiscountRequest::with('invoice')->findOrFail($request_id);

        // Prevent self-approval
        if ($dr->requested_by === Auth::id()) {
            return back()->with('flash_danger', 'You cannot approve your own discount request.');
        }

        if (!$dr->isPending()) {
            return back()->with('flash_danger', 'This request has already been reviewed.');
        }

        $req->validate([
            'approved_amount' => 'required|numeric|min:1|max:' . $dr->invoice->balance,
            'admin_note'      => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($dr, $req) {
            $invoice = $dr->invoice;
            $before  = $invoice->toArray();

            // Apply discount to invoice
            $invoice->discount        = $invoice->discount + $req->approved_amount; // add to any existing auto discount
            $invoice->discount_reason = $invoice->discount_reason ? $invoice->discount_reason . ' & ' . DiscountRequest::typeLabel($dr->discount_type) : DiscountRequest::typeLabel($dr->discount_type) . ' — ' . $dr->reason;
            $invoice->recalculateNetAmount();
            $invoice->syncStatus();

            // Update request
            $dr->update([
                'status'          => 'approved',
                'approved_amount' => $req->approved_amount,
                'admin_note'      => $req->admin_note,
                'reviewed_by'     => Auth::id(),
                'reviewed_at'     => now(),
            ]);

            FP::audit('approved', 'discount_request', DiscountRequest::class, $dr->id,
                ['status' => 'pending'],
                ['status' => 'approved', 'approved_amount' => $req->approved_amount, 'reviewed_by' => Auth::id()]
            );
        });

        return back()->with('flash_success', 'Discount approved and applied to invoice.');
    }

    // ── REJECT (Admin / Super Admin) ──────────────────────────────────────────

    public function reject(Request $req, $request_id)
    {
        FP::require('approve_fee_discount');

        $dr = DiscountRequest::findOrFail($request_id);

        if ($dr->requested_by === Auth::id()) {
            return back()->with('flash_danger', 'You cannot reject your own discount request.');
        }

        if (!$dr->isPending()) {
            return back()->with('flash_danger', 'This request has already been reviewed.');
        }

        $req->validate(['admin_note' => 'required|string|min:5|max:500']);

        $dr->update([
            'status'      => 'rejected',
            'admin_note'  => $req->admin_note,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        FP::audit('rejected', 'discount_request', DiscountRequest::class, $dr->id,
            ['status' => 'pending'],
            ['status' => 'rejected', 'reason' => $req->admin_note]
        );

        return back()->with('flash_success', 'Discount request rejected.');
    }

    // ── DISCOUNT RULES (Super Admin defines; Accountant view-only) ───────────

    public function ruleIndex(Request $req)
    {
        FP::require('view_discount_rules');

        $canManage       = FP::has('manage_discount_rules');
        $currentSibling  = DiscountService::getSiblingPct();
        $currentEmployee = DiscountService::getEmployeePct();
        $eligible        = DiscountService::getEligibleStudentsSummary();

        return view('pages.finance.discounts.rules', compact(
            'canManage', 'currentSibling', 'currentEmployee', 'eligible'
        ));
    }

    public function ruleStore(Request $req)
    {
        FP::require('manage_discount_rules');

        $req->validate([
            'sibling_discount_pct'  => 'required|numeric|min:0|max:100',
            'employee_discount_pct' => 'required|numeric|min:0|max:100',
        ]);

        DiscountService::setDiscountRules(
            (float) $req->sibling_discount_pct,
            (float) $req->employee_discount_pct
        );

        $updated = 0;
        try {
            $updated = DiscountService::recalculateAllOpenInvoices();
        } catch (\Throwable $e) {
            report($e);
        }

        FP::audit('updated', 'discount_rules', null, null, [], [
            'sibling_pct'  => $req->sibling_discount_pct,
            'employee_pct' => $req->employee_discount_pct,
            'invoices_updated' => $updated,
        ]);

        return redirect()->route('discount_rules.index')
            ->with('flash_success', "Discount rules saved. Sibling {$req->sibling_discount_pct}%, Employee child {$req->employee_discount_pct}%. {$updated} open invoice(s) updated.");
    }

    /** @deprecated Proposal workflow removed — rules are set by Super Admin only */
    private function closeRedundantInvoiceDiscountRequests(): void
    {
        DiscountRequest::where('status', 'pending')
            ->whereIn('discount_type', ['sibling', 'employee_child'])
            ->update([
                'status'      => 'rejected',
                'admin_note'  => 'Not required — sibling and employee-child discounts use approved global rules.',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
    }

    private function notifyUser(int $userId, string $subject, string $body): void
    {
        try {
            \App\Models\Message::create(['sender_id'=>Auth::id(),'receiver_id'=>$userId,'subject'=>$subject,'body'=>$body,'read'=>false]);
        } catch (\Exception $e) {}
    }
}
