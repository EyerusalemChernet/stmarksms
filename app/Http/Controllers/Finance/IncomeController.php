<?php
namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Qs;

class IncomeController extends Controller
{
    public function index(Request $req)
    {
        $year = $req->year ?? Qs::getCurrentSession();
        $d['year']       = $year;
        $d['categories'] = IncomeCategory::all();
        
        $query = Income::with(['category', 'creator'])->where('year', $year);
        if ($req->category_id) {
            $query->where('category_id', $req->category_id);
        }
        
        $d['incomes'] = $query->orderBy('income_date', 'desc')->get();
        $d['total']   = $d['incomes']->sum('amount');
        
        return view('pages.finance.income.index', $d);
    }

    public function create()
    {
        $d['categories'] = IncomeCategory::all();
        return view('pages.finance.income.create', $d);
    }

    public function store(Request $req)
    {
        $req->validate([
            'title'       => 'required|string|max:200',
            'category_id' => 'required|exists:income_categories,id',
            'amount'      => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $data = $req->only(['title', 'category_id', 'amount', 'income_date', 'description']);
        $data['year']         = Qs::getCurrentSession();
        $data['created_by']   = Auth::id();
        $data['reference_no'] = 'INC-' . strtoupper(uniqid());

        Income::create($data);
        return redirect()->route('finance.income.index')->with('flash_success', 'Income record added successfully.');
    }

    public function edit($id)
    {
        $d['income']     = Income::findOrFail($id);
        $d['categories'] = IncomeCategory::all();
        return view('pages.finance.income.edit', $d);
    }

    public function update(Request $req, $id)
    {
        $req->validate([
            'title'       => 'required|string|max:200',
            'category_id' => 'required|exists:income_categories,id',
            'amount'      => 'required|numeric|min:0',
            'income_date' => 'required|date',
        ]);

        $income = Income::findOrFail($id);
        $income->update($req->only(['title', 'category_id', 'amount', 'income_date', 'description']));

        return redirect()->route('finance.income.index')->with('flash_success', 'Income record updated.');
    }

    public function destroy($id)
    {
        Income::findOrFail($id)->delete();
        return back()->with('flash_success', 'Income record deleted.');
    }
}
