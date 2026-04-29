<?php
namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $req)
    {
        $year = $req->year ?? Qs::getCurrentSession();
        $query = Expense::with('category')->where('year', $year);
        if ($req->category_id) $query->where('category_id', $req->category_id);
        $d['expenses'] = $query->orderBy('expense_date', 'desc')->get();
        $d['categories'] = ExpenseCategory::all();
        $d['total'] = $d['expenses']->sum('amount');
        $d['year'] = $year;
        return view('pages.finance.expenses.index', $d);
    }

    public function create()
    {
        $d['categories'] = ExpenseCategory::all();
        return view('pages.finance.expenses.create', $d);
    }

    public function store(Request $req)
    {
        $req->validate([
            'category_id'  => 'required|exists:expense_categories,id',
            'title'        => 'required|string|max:200',
            'amount'       => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);
        $data = $req->only(['category_id', 'title', 'amount', 'expense_date', 'description', 'receipt_no']);
        $data['year'] = Qs::getCurrentSession();
        $data['created_by'] = Auth::id();
        Expense::create($data);
        return redirect()->route('finance.expenses.index')->with('flash_success', 'Expense recorded.');
    }

    public function edit($id)
    {
        $d['expense'] = Expense::findOrFail($id);
        $d['categories'] = ExpenseCategory::all();
        return view('pages.finance.expenses.edit', $d);
    }

    public function update(Request $req, $id)
    {
        $req->validate(['title' => 'required', 'amount' => 'required|numeric', 'expense_date' => 'required|date']);
        Expense::findOrFail($id)->update($req->only(['category_id', 'title', 'amount', 'expense_date', 'description', 'receipt_no']));
        return redirect()->route('finance.expenses.index')->with('flash_success', 'Expense updated.');
    }

    public function destroy($id)
    {
        Expense::findOrFail($id)->delete();
        return redirect()->route('finance.expenses.index')->with('flash_success', 'Expense deleted.');
    }

    // Category management
    public function categories()
    {
        $d['categories'] = ExpenseCategory::withCount('expenses')->get();
        return view('pages.finance.expenses.categories', $d);
    }

    public function storeCategory(Request $req)
    {
        $req->validate(['name' => 'required|string|max:100|unique:expense_categories,name']);
        ExpenseCategory::create($req->only(['name', 'description']));
        return back()->with('flash_success', 'Category created.');
    }

    public function destroyCategory($id)
    {
        ExpenseCategory::findOrFail($id)->delete();
        return back()->with('flash_success', 'Category deleted.');
    }
}
