<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    /**
     * Show trash index
     */
    public function index(Request $request)
    {
        return view('pages.super_admin.trash.index');
    }

    /**
     * Restore a trashed record
     */
    public function restore(Request $request, $type, $id)
    {
        // TODO: Implement restore logic
        return redirect()->route('trash.index')->with('flash_success', 'Record restored');
    }

    /**
     * Permanently delete a trashed record
     */
    public function destroy(Request $request, $type, $id)
    {
        // TODO: Implement permanent delete logic
        return redirect()->route('trash.index')->with('flash_success', 'Record permanently deleted');
    }
}
