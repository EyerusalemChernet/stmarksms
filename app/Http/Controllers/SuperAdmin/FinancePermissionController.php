<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FinancePermissionController extends Controller
{
    /**
     * Show finance permissions index
     */
    public function index(Request $request)
    {
        return view('pages.super_admin.finance_permissions.index');
    }

    /**
     * Update finance permissions
     */
    public function update(Request $request)
    {
        // TODO: Implement finance permissions update logic
        return redirect()->route('finance.permissions.index')->with('flash_success', 'Finance permissions updated');
    }
}
