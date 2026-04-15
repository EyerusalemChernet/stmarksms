<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Rule;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('teamSA');
    }

    public function index()
    {
        $d['rules'] = Rule::orderBy('type')->get();
        return view('pages.super_admin.rules.index', $d);
    }

    public function store(Request $req)
    {
        $this->validate($req, [
            'name'      => 'required|string|max:100',
            'type'      => 'required|string',
            'condition' => 'required|string',
            'value'     => 'required|numeric',
            'action'    => 'required|string',
        ]);

        Rule::create($req->only(['name', 'type', 'condition', 'value', 'action', 'description']));
        return back()->with('flash_success', 'Rule created successfully.');
    }

    public function update(Request $req, $id)
    {
        $rule = Rule::findOrFail($id);
        $rule->update($req->only(['name', 'type', 'condition', 'value', 'action', 'description', 'active']));
        return back()->with('flash_success', 'Rule updated.');
    }

    public function destroy($id)
    {
        Rule::destroy($id);
        return back()->with('flash_success', 'Rule deleted.');
    }
}
