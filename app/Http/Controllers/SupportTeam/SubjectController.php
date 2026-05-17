<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Requests\Subject\SubjectBulkCreate;
use App\Http\Requests\Subject\SubjectCreate;
use App\Http\Requests\Subject\SubjectUpdate;
use App\Models\Subject;
use App\Models\Department;
use App\Repositories\MyClassRepo;
use App\Http\Controllers\Controller;

class SubjectController extends Controller
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->middleware('teamSA', ['except' => ['destroy',] ]);
        $this->middleware('super_admin', ['only' => ['destroy',] ]);

        $this->my_class = $my_class;
    }

    public function index()
    {
        $d['my_classes'] = $this->my_class->all();
        $d['departments'] = Department::orderBy('name')->get();
        $d['subjects'] = $this->my_class->getAllSubjects();

        return view('pages.support_team.subjects.index', $d);
    }

    public function store(SubjectCreate $req)
    {
        $data = $req->only(['name', 'slug', 'my_class_id', 'department_id']);
        $data['teacher_id'] = null;
        $this->my_class->createSubject($data);

        return Qs::jsonStoreOk();
    }

    public function storeBulk(SubjectBulkCreate $req)
    {
        $base = [
            'name'          => $req->name,
            'slug'          => $req->slug,
            'department_id' => $req->department_id,
            'teacher_id'    => null,
        ];

        $created = 0;
        $skipped = 0;

        foreach ($req->my_class_ids as $classId) {
            $exists = Subject::where('my_class_id', $classId)
                ->where(function ($q) use ($req) {
                    $q->where('name', $req->name)->orWhere('slug', $req->slug);
                })
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $this->my_class->createSubject(array_merge($base, ['my_class_id' => $classId]));
            $created++;
        }

        if ($created === 0) {
            return response()->json([
                'ok'  => false,
                'msg' => 'No subjects were added. The same name or short name may already exist for every selected class.',
            ]);
        }

        $message = "{$created} class(es) received this subject.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (duplicate name or short name).";
        }

        return response()->json(['ok' => true, 'msg' => $message]);
    }

    public function edit($id)
    {
        $d['s'] = $sub = $this->my_class->findSubject($id);
        $d['my_classes'] = $this->my_class->all();
        $d['departments'] = Department::orderBy('name')->get();

        return is_null($sub) ? Qs::goWithDanger('subjects.index') : view('pages.support_team.subjects.edit', $d);
    }

    public function update(SubjectUpdate $req, $id)
    {
        $data = $req->only(['name', 'slug', 'my_class_id', 'department_id']);
        $data['teacher_id'] = null;
        $this->my_class->updateSubject($id, $data);

        return Qs::jsonUpdateOk();
    }

    public function destroy($id)
    {
        $this->my_class->deleteSubject($id);
        return back()->with('flash_success', __('msg.del_ok'));
    }
}
