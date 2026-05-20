<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Models\Department;
use App\Models\MasterSubject;
use App\Models\MyClass;
use App\Models\Subject;
use App\Repositories\MyClassRepo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->middleware('teamSA', ['except' => ['destroy', 'destroyMaster']]);
        $this->middleware('super_admin', ['only' => ['destroy', 'destroyMaster']]);
        $this->my_class = $my_class;
    }

    /* ─────────────────────────────────────────────────────────────
     |  MAIN INDEX — three tabs:
     |    1. Master Subjects catalog
     |    2. Assign master → classes
     |    3. View assignments per class
     ───────────────────────────────────────────────────────────── */
    public function index()
    {
        $d['masters']     = MasterSubject::orderBy('name')->withCount('classSubjects')->get();
        $d['my_classes']  = $this->my_class->all();
        $d['departments'] = Department::orderBy('name')->get();
        $d['subjects']    = $this->my_class->getAllSubjects(); // for per-class view tab

        return view('pages.support_team.subjects.index', $d);
    }

    /* ─────────────────────────────────────────────────────────────
     |  MASTER SUBJECTS CRUD
     ───────────────────────────────────────────────────────────── */

    /** Create a new master subject (global catalog entry) */
    public function storeMaster(Request $req)
    {
        $req->validate([
            'name' => 'required|string|min:2|max:100|unique:master_subjects,name',
            'code' => 'nullable|string|max:20|unique:master_subjects,code',
            'description' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'A subject with this name already exists in the catalog.',
            'code.unique' => 'This subject code is already used.',
        ]);

        MasterSubject::create([
            'name'        => trim($req->name),
            'code'        => $req->code ? strtoupper(trim($req->code)) : null,
            'description' => $req->description,
        ]);

        return response()->json(['ok' => true, 'msg' => 'Subject "' . $req->name . '" added to catalog.']);
    }

    /** Update a master subject */
    public function updateMaster(Request $req, MasterSubject $master)
    {
        $req->validate([
            'name' => 'required|string|min:2|max:100|unique:master_subjects,name,' . $master->id,
            'code' => 'nullable|string|max:20|unique:master_subjects,code,' . $master->id,
            'description' => 'nullable|string|max:255',
        ]);

        $master->update([
            'name'        => trim($req->name),
            'code'        => $req->code ? strtoupper(trim($req->code)) : null,
            'description' => $req->description,
        ]);

        // Sync name/slug on all class-subject assignments
        Subject::where('master_subject_id', $master->id)->update([
            'name' => trim($req->name),
            'slug' => $req->code ? strtoupper(trim($req->code)) : null,
        ]);

        return response()->json(['ok' => true, 'msg' => 'Subject updated.']);
    }

    /** Delete a master subject (and all its class assignments) */
    public function destroyMaster(MasterSubject $master)
    {
        // Remove all class assignments first
        Subject::where('master_subject_id', $master->id)->delete();
        $master->delete();

        return back()->with('flash_success', 'Subject "' . $master->name . '" and all its class assignments deleted.');
    }

    /* ─────────────────────────────────────────────────────────────
     |  ASSIGN MASTER SUBJECT → CLASSES
     ───────────────────────────────────────────────────────────── */

    /** Assign one master subject to one or more classes */
    public function assign(Request $req)
    {
        $req->validate([
            'master_subject_id' => 'required|exists:master_subjects,id',
            'class_ids'         => 'required|array|min:1',
            'class_ids.*'       => 'integer|exists:my_classes,id',
            'department_id'     => 'nullable|exists:departments,id',
        ]);

        $master  = MasterSubject::findOrFail($req->master_subject_id);
        $created = 0;
        $skipped = 0;

        foreach ($req->class_ids as $classId) {
            // Skip if already assigned
            if (Subject::where('master_subject_id', $master->id)
                       ->where('my_class_id', $classId)->exists()) {
                $skipped++;
                continue;
            }

            Subject::create([
                'master_subject_id' => $master->id,
                'name'              => $master->name,
                'slug'              => $master->code,
                'my_class_id'       => $classId,
                'department_id'     => $req->department_id,
                'teacher_id'        => null,
            ]);
            $created++;
        }

        if ($created === 0) {
            return response()->json([
                'ok'  => false,
                'msg' => 'No new assignments made — subject is already assigned to all selected classes.',
            ]);
        }

        $msg = '"' . $master->name . '" assigned to ' . $created . ' class(es).';
        if ($skipped) $msg .= ' ' . $skipped . ' already had it.';

        return response()->json(['ok' => true, 'msg' => $msg]);
    }

    /** Remove a single class assignment (not the master) */
    public function destroy($id)
    {
        $this->my_class->deleteSubject($id);
        return back()->with('flash_success', __('msg.del_ok'));
    }

    /* ─────────────────────────────────────────────────────────────
     |  EDIT A CLASS ASSIGNMENT (change department)
     ───────────────────────────────────────────────────────────── */
    public function edit($id)
    {
        $d['s']           = $sub = $this->my_class->findSubject($id);
        $d['my_classes']  = $this->my_class->all();
        $d['departments'] = Department::orderBy('name')->get();

        return is_null($sub) ? Qs::goWithDanger('subjects.index')
                             : view('pages.support_team.subjects.edit', $d);
    }

    public function update(Request $req, $id)
    {
        $req->validate([
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $this->my_class->updateSubject($id, [
            'department_id' => $req->department_id,
        ]);

        return Qs::jsonUpdateOk();
    }
}
