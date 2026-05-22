<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\FinancePermission as FP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('finance_access');
    }

    // ── EXPENSE CATEGORIES ────────────────────────────────────────────────────

    public function categories()
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();
        return view('pages.finance.expenses.categories', compact('categories'));
    }

    public function storeCategory(Request $req)
    {
        $req->validate([
            'name'        => 'required|string|max:100|unique:expense_categories',
            'description' => 'nullable|string',
        ]);
        ExpenseCategory::create($req->only('name', 'description'));
        return back()->with('flash_success', 'Category created successfully.');
    }

    public function updateCategory(Request $req, $id)
    {
        $cat = ExpenseCategory::findOrFail($id);
        $req->validate([
            'name'        => 'required|string|max:100|unique:expense_categories,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $cat->update($req->only('name', 'description'));
        return back()->with('flash_success', 'Category updated successfully.');
    }

    public function destroyCategory($id)
    {
        $cat = ExpenseCategory::withCount('expenses')->findOrFail($id);
        if ($cat->expenses_count > 0) {
            return back()->with('flash_danger', 'Cannot delete: this category has expenses attached.');
        }
        $cat->delete();
        return back()->with('flash_success', 'Category deleted successfully.');
    }

    // ── EXPENSES ──────────────────────────────────────────────────────────────

    public function index(Request $req)
    {
        $canApprove = FP::has('approve_expenses');
        $categories = ExpenseCategory::orderBy('name')->get();
        $query      = Expense::with(['category', 'creator', 'approver'])->latest('expense_date');

        if (!$canApprove) {
            $query->where(function ($q) {
                $q->where('created_by', Auth::id())
                  ->orWhere('status', 'approved');
            });
        }

        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }
        if ($req->filled('category_id')) {
            $query->where('expense_category_id', $req->category_id);
        }
        if ($req->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $req->date_from);
        }
        if ($req->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $req->date_to);
        }
        if ($req->filled('recurring')) {
            $query->where('recurring', (bool) $req->recurring);
        }
        if ($req->filled('search')) {
            $query->where('title', 'like', '%' . $req->search . '%');
        }

        $total    = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->appends($req->query());

        $pendingCount = $canApprove
            ? Expense::where('status', 'pending')->count()
            : Expense::where('status', 'pending')->where('created_by', Auth::id())->count();

        return view('pages.finance.expenses.index', compact(
            'expenses', 'categories', 'total', 'canApprove', 'pendingCount'
        ));
    }

    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('pages.finance.expenses.create', compact('categories'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'expense_category_id'  => 'required|exists:expense_categories,id',
            'title'                => 'required|string|max:200',
            'amount'               => 'required|numeric|min:0',
            'expense_date'         => 'required|date',
            'description'          => 'nullable|string',
            'receipt_file'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'recurring'            => 'nullable|boolean',
            'recurrence_interval'  => 'nullable|in:monthly,quarterly,annually',
        ]);

        $data = $req->only('expense_category_id', 'title', 'amount', 'expense_date', 'description', 'recurring', 'recurrence_interval');
        $data['recurring']   = $req->boolean('recurring');
        $data['created_by']  = Auth::id();
        $data['year']        = Qs::financeYearForDate($data['expense_date']);
        $data['receipt_no']  = 'EXP-' . strtoupper(uniqid());

        if (FP::has('approve_expenses')) {
            $data['status']      = 'approved';
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
            $data['is_locked']   = true;
        } else {
            $data['status'] = 'pending';
        }

        if ($req->hasFile('receipt_file')) {
            $data['receipt_file'] = $req->file('receipt_file')->store('expenses/receipts', 'public');
        }

        Expense::create($data);

        $message = $data['status'] === 'pending'
            ? 'Expense submitted. Awaiting admin approval.'
            : 'Expense added successfully.';

        return redirect()->route('expenses.index')->with('flash_success', $message);
    }

    public function edit($id)
    {
        $expense = Expense::findOrFail($id);
        $this->authorizeExpenseEdit($expense);

        $categories = ExpenseCategory::orderBy('name')->get();
        return view('pages.finance.expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $req, $id)
    {
        $expense = Expense::findOrFail($id);
        $this->authorizeExpenseEdit($expense);

        $req->validate([
            'expense_category_id'  => 'required|exists:expense_categories,id',
            'title'                => 'required|string|max:200',
            'amount'               => 'required|numeric|min:0',
            'expense_date'         => 'required|date',
            'description'          => 'nullable|string',
            'receipt_file'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'recurring'            => 'nullable|boolean',
            'recurrence_interval'  => 'nullable|in:monthly,quarterly,annually',
        ]);

        $data = $req->only('expense_category_id', 'title', 'amount', 'expense_date', 'description', 'recurring', 'recurrence_interval');
        $data['recurring'] = $req->boolean('recurring');

        if ($req->hasFile('receipt_file')) {
            if ($expense->receipt_file) {
                Storage::disk('public')->delete($expense->receipt_file);
            }
            $data['receipt_file'] = $req->file('receipt_file')->store('expenses/receipts', 'public');
        }

        $expense->update($data);
        return redirect()->route('expenses.index')->with('flash_success', 'Expense updated successfully.');
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $this->authorizeExpenseEdit($expense);

        if ($expense->receipt_file) {
            Storage::disk('public')->delete($expense->receipt_file);
        }
        $expense->delete();
        return back()->with('flash_success', 'Expense deleted successfully.');
    }

    public function approve(Request $req, $id)
    {
        FP::require('approve_expenses');

        $expense = Expense::findOrFail($id);

        if ($expense->created_by === Auth::id()) {
            return back()->with('flash_danger', 'You cannot approve your own expense.');
        }
        if (!$expense->isPending()) {
            return back()->with('flash_danger', 'This expense has already been reviewed.');
        }

        $req->validate(['approval_note' => 'nullable|string|max:500']);

        $expense->update([
            'status'        => 'approved',
            'approved_by'   => Auth::id(),
            'approved_at'   => now(),
            'approval_note' => $req->approval_note,
            'is_locked'     => true,
        ]);

        FP::audit('approved', 'expense', Expense::class, $expense->id, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with('flash_success', 'Expense approved.');
    }

    public function reject(Request $req, $id)
    {
        FP::require('approve_expenses');

        $expense = Expense::findOrFail($id);

        if ($expense->created_by === Auth::id()) {
            return back()->with('flash_danger', 'You cannot reject your own expense.');
        }
        if (!$expense->isPending()) {
            return back()->with('flash_danger', 'This expense has already been reviewed.');
        }

        $req->validate(['rejection_reason' => 'required|string|min:5|max:500']);

        $expense->update([
            'status'           => 'rejected',
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
            'rejection_reason' => $req->rejection_reason,
        ]);

        FP::audit('rejected', 'expense', Expense::class, $expense->id, ['status' => 'pending'], ['status' => 'rejected']);

        return back()->with('flash_success', 'Expense rejected.');
    }

    public function exportCsv(Request $req)
    {
        $query = $this->buildListQuery($req);
        $expenses = $query->get();
        $filename = 'expenses_' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $callback = function () use ($expenses) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Date', 'Category', 'Title', 'Amount (ETB)', 'Status', 'Recurring', 'Description']);
            foreach ($expenses as $e) {
                fputcsv($h, [
                    $e->expense_date->format('d M Y'),
                    $e->category->name ?? '-',
                    $e->title,
                    $e->amount,
                    ucfirst($e->status),
                    $e->recurring ? 'Yes' : 'No',
                    $e->description ?? '',
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildListQuery(Request $req)
    {
        $canApprove = FP::has('approve_expenses');
        $query      = Expense::with('category')->latest('expense_date');

        if (!$canApprove) {
            $query->where(function ($q) {
                $q->where('created_by', Auth::id())
                  ->orWhere('status', 'approved');
            });
        }

        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }
        if ($req->filled('category_id')) {
            $query->where('expense_category_id', $req->category_id);
        }
        if ($req->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $req->date_from);
        }
        if ($req->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $req->date_to);
        }

        return $query;
    }

    private function authorizeExpenseEdit(Expense $expense): void
    {
        if (FP::has('approve_expenses')) {
            if ($expense->is_locked && $expense->isApproved()) {
                abort(403, 'Approved expenses cannot be edited.');
            }
            return;
        }

        if ($expense->created_by !== Auth::id() || !$expense->isPending()) {
            abort(403, 'You can only edit your own pending expenses.');
        }
    }
}
