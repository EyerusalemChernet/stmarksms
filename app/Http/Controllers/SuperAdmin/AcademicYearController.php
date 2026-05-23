<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function __construct()
    {
        $this->middleware('super_admin');
    }

    public function index()
    {
        $years = AcademicYear::orderByDesc('name')->get()->map(function ($y) {
            $y->enrollment_count = Enrollment::where('academic_year_id', $y->id)->count();
            return $y;
        });

        return view('pages.super_admin.academic_years.index', compact('years'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'year_name' => 'required|string|max:20|unique:academic_years,name',
        ], [
            'year_name.unique' => 'An academic year with this name already exists.',
        ]);

        AcademicYear::create([
            'name'      => $req->year_name,
            'is_active' => 0,
        ]);

        return back()->with('flash_success', "Academic year \"{$req->year_name}\" created.");
    }

    public function activate(AcademicYear $year)
    {
        $year->activate();
        return back()->with('flash_success', "\"{$year->name}\" is now the active academic year.");
    }

    public function destroy(AcademicYear $year)
    {
        if (Enrollment::where('academic_year_id', $year->id)->exists()) {
            return back()->with('flash_danger', 'Cannot delete an academic year that has enrollment records.');
        }
        $year->delete();
        return back()->with('flash_success', 'Academic year deleted.');
    }
}
