<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('hr_manager');
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
        $categories = ExpenseCategory::orderBy('name')->get();
        $query      = Expense::with('category')->latest('expense_date');

        if ($req->filled('category_id')) $query->where('expense_category_id', $req->category_id);
        if ($req->filled('date_from'))   $query->whereDate('expense_date', '>=', $req->date_from);
        if ($req->filled('date_to'))     $query->whereDate('expense_date', '<=', $req->date_to);
        if ($req->filled('recurring'))   $query->where('recurring', (bool)$req->recurring);
        if ($req->filled('search'))      $query->where('title', 'like', '%' . $req->search . '%');

        $total    = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->appends($req->query());

        return view('pages.finance.expenses.index', compact('expenses', 'categories', 'total'));
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
        $data['recurring'] = $req->boolean('recurring');

        if ($req->hasFile('receipt_file')) {
            $data['receipt_file'] = $req->file('receipt_file')->store('expenses/receipts', 'public');
        }

        Expense::create($data);
        return redirect()->route('expenses.index')->with('flash_success', 'Expense added successfully.');
    }

    public function edit($id)
    {
        $expense    = Expense::findOrFail($id);
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('pages.finance.expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $req, $id)
    {
        $expense = Expense::findOrFail($id);
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
            if ($expense->receipt_file) Storage::disk('public')->delete($expense->receipt_file);
            $data['receipt_file'] = $req->file('receipt_file')->store('expenses/receipts', 'public');
        }

        $expense->update($data);
        return redirect()->route('expenses.index')->with('flash_success', 'Expense updated successfully.');
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        if ($expense->receipt_file) Storage::disk('public')->delete($expense->receipt_file);
        $expense->delete();
        return back()->with('flash_success', 'Expense deleted successfully.');
    }

    public function exportCsv(Request $req)
    {
        $query = Expense::with('category')->latest('expense_date');
        if ($req->filled('category_id')) $query->where('expense_category_id', $req->category_id);
        if ($req->filled('date_from'))   $query->whereDate('expense_date', '>=', $req->date_from);
        if ($req->filled('date_to'))     $query->whereDate('expense_date', '<=', $req->date_to);

        $expenses = $query->get();
        $filename = 'expenses_' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $callback = function () use ($expenses) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Date', 'Category', 'Title', 'Amount (ETB)', 'Recurring', 'Description']);
            foreach ($expenses as $e) {
                fputcsv($h, [
                    $e->expense_date->format('d M Y'),
                    $e->category->name ?? '-',
                    $e->title,
                    $e->amount,
                    $e->recurring ? 'Yes' : 'No',
                    $e->description ?? '',
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }
}
