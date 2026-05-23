<?php

namespace App\Http\Controllers\SupportTeam\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LegacyPaymentRedirectController extends Controller
{
    public function __construct()
    {
        $this->middleware('finance_access');
    }

    public function index()
    {
        return redirect()->route('fees.invoices');
    }

    public function manage($class_id = null)
    {
        return redirect()->route('fees.invoices', ['class_id' => $class_id]);
    }

    public function invoice($id, $year = null)
    {
        return redirect()->route('fees.invoice', $id);
    }

    public function receipts($id)
    {
        return redirect()->route('fees.receipt', $id);
    }

    public function selectYear(Request $request)
    {
        return redirect()->route('fees.invoices', $request->all());
    }

    public function selectClass(Request $request)
    {
        return redirect()->route('fees.invoices', $request->all());
    }

    public function show($id)
    {
        return redirect()->route('fees.invoice', $id);
    }

    public function create()
    {
        return redirect()->route('fees.invoices');
    }

    public function store(Request $request)
    {
        return redirect()->route('fees.invoices');
    }

    public function edit($id)
    {
        return redirect()->route('fees.invoice', $id);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('fees.invoice', $id);
    }

    public function destroy($id)
    {
        return redirect()->route('fees.invoices');
    }
}