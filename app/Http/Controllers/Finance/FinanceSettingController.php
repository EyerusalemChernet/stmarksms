<?php
namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;

class FinanceSettingController extends Controller
{
    public function index()
    {
        $d['expense_categories'] = ExpenseCategory::withCount('expenses')->get();
        $d['income_categories']  = IncomeCategory::withCount('incomes')->get();
        return view('pages.finance.settings.index', $d);
    }

    public function storeExpenseCategory(Request $req)
    {
        $req->validate(['name' => 'required|string|max:100|unique:expense_categories,name']);
        ExpenseCategory::create($req->only(['name', 'description']));
        return back()->with('flash_success', 'Expense category added.');
    }

    public function destroyExpenseCategory($id)
    {
        ExpenseCategory::findOrFail($id)->delete();
        return back()->with('flash_success', 'Category deleted.');
    }

    public function storeIncomeCategory(Request $req)
    {
        $req->validate(['name' => 'required|string|max:100|unique:income_categories,name']);
        IncomeCategory::create($req->only(['name', 'description']));
        return back()->with('flash_success', 'Income category added.');
    }

    public function destroyIncomeCategory($id)
    {
        IncomeCategory::findOrFail($id)->delete();
        return back()->with('flash_success', 'Category deleted.');
    }
}
