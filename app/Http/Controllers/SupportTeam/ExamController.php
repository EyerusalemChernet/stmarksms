<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Requests\Exam\ExamCreate;
use App\Http\Requests\Exam\ExamUpdate;
use App\Models\AuditLog;
use App\Repositories\ExamRepo;
use App\Http\Controllers\Controller;
use App\Services\RulesEngine;

class ExamController extends Controller
{
    protected $exam;
    public function __construct(ExamRepo $exam)
    {
        $this->middleware('teamSA', ['except' => ['destroy',] ]);
        $this->middleware('super_admin', ['only' => ['destroy',] ]);

        $this->exam = $exam;
    }

    public function index()
    {
        $d['exams']          = $this->exam->all();
        $d['current_session'] = Qs::getSetting('current_session');
        $d['session_exams']  = \App\Models\Exam::where('year', $d['current_session'])->pluck('term')->toArray();
        return view('pages.support_team.exams.index', $d);
    }

    public function store(ExamCreate $req)
    {
        $data         = $req->only(['name', 'term', 'start_date', 'end_date', 'description', 'status']);
        $data['year'] = Qs::getSetting('current_session');
        $data['created_by'] = auth()->id();

        // Validate session
        $validation = RulesEngine::validateExamSession($data['year']);
        if (!$validation['valid']) {
            return back()->with('flash_danger', $validation['message']);
        }

        // Prevent duplicate: same term + year
        if (\App\Models\Exam::where('term', $data['term'])->where('year', $data['year'])->exists()) {
            return back()->withInput()->with('flash_danger',
                "Semester {$data['term']} already exists for session {$data['year']}. Edit the existing exam instead."
            );
        }

        $this->exam->create($data);
        AuditLog::log('created', 'exams', "Exam '{$data['name']}' (Semester {$data['term']}) created for session {$data['year']}");
        return back()->with('flash_success', __('msg.store_ok'));
    }

    public function edit($id)
    {
        $d['ex'] = $this->exam->find($id);
        return view('pages.support_team.exams.edit', $d);
    }

    public function update(ExamUpdate $req, $id)
    {
        $data = $req->only(['name', 'term', 'start_date', 'end_date', 'description', 'status']);
        $this->exam->update($id, $data);
        AuditLog::log('updated', 'exams', "Exam ID {$id} updated");
        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function destroy($id)
    {
        $this->exam->delete($id);
        return back()->with('flash_success', __('msg.del_ok'));
    }
}
