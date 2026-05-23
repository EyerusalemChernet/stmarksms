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

        // Get late fee settings
        $d['late_fee_percentage'] = \App\Models\Setting::where('key', 'late_fee_percentage')->first()->value ?? 0;
        $d['late_fee_fixed_amount'] = \App\Models\Setting::where('key', 'late_fee_fixed_amount')->first()->value ?? 0;
        $d['grace_period_days'] = \App\Models\Setting::where('key', 'grace_period_days')->first()->value ?? 0;

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
        \App\Services\FinancePermission::require('delete_expenses');

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

    public function updateLateFeeRules(Request $req)
    {
        $req->validate([
            'late_fee_percentage' => 'required|numeric|min:0|max:100',
            'late_fee_fixed_amount' => 'required|numeric|min:0',
            'grace_period_days' => 'required|integer|min:0',
        ]);

        // Store settings in database or config
        // For now, we'll use a simple approach with a settings table or config file
        // This is a placeholder implementation
        \App\Models\Setting::updateOrCreate(
            ['key' => 'late_fee_percentage'],
            ['value' => $req->late_fee_percentage]
        );
        \App\Models\Setting::updateOrCreate(
            ['key' => 'late_fee_fixed_amount'],
            ['value' => $req->late_fee_fixed_amount]
        );
        \App\Models\Setting::updateOrCreate(
            ['key' => 'grace_period_days'],
            ['value' => $req->grace_period_days]
        );

        return back()->with('flash_success', 'Late fee rules updated.');
    }
}
