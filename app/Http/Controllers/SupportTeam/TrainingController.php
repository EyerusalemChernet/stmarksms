<?php

namespace App\Http\Controllers\SupportTeam;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeTraining;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use PDF;

class TrainingController extends Controller
{
    public function __construct()
    {
        $this->middleware('hr_manager');
    }

    // ── TRAINING PROGRAMS ────────────────────────────────────────────────────

    public function programs(Request $req)
    {
        $search   = trim($req->get('search', ''));
        $category = $req->get('category', 'all');

        $programs = TrainingProgram::withCount([
                'enrollments',
                'completedEnrollments as completed_count',
            ])
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%")
                                        ->orWhere('provider', 'like', "%{$search}%"))
            ->when($category !== 'all', fn($q) => $q->where('category', $category))
            ->orderBy('title')->get();

        $categories = ['technical','pedagogical','leadership','compliance','certification','soft_skills','other'];

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.training.programs_pdf', compact('programs','search'));
            return $pdf->download('training_programs.pdf');
        }
        if ($req->get('export') === 'csv') {
            return $this->exportProgramsCsv($programs);
        }

        return view('pages.hr.training.programs', compact('programs','search','category','categories'));
    }

    protected function exportProgramsCsv($programs)
    {
        $headers = ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=training_programs.csv'];
        $callback = function () use ($programs) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Title','Category','Provider','Duration (h)','Cost','Mandatory','Enrollments','Completed']);
            foreach ($programs as $p) {
                fputcsv($h, [
                    $p->title, $p->categoryLabel(), $p->provider ?? '',
                    $p->duration_hours ?? '', $p->cost ?? '',
                    $p->is_mandatory ? 'Yes' : 'No',
                    $p->enrollments_count, $p->completed_count,
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function storeProgram(Request $req)
    {
        $req->validate([
            'title'          => 'required|string|max:200',
            'category'       => 'required|in:technical,pedagogical,leadership,compliance,certification,soft_skills,other',
            'description'    => 'nullable|string',
            'provider'       => 'nullable|string|max:150',
            'duration_hours' => 'nullable|integer|min:1',
            'cost'           => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:10',
            'is_mandatory'   => 'nullable|boolean',
        ]);
        $program = TrainingProgram::create(array_merge(
            $req->only('title','category','description','provider','duration_hours','cost','currency'),
            ['is_mandatory' => $req->boolean('is_mandatory'), 'is_active' => true]
        ));
        AuditLog::log('created', 'hr', "Training program created: {$program->title}");
        return back()->with('flash_success', "Program '{$program->title}' created.");
    }

    public function editProgram($hrId)
    {
        $program = TrainingProgram::findOrFail($hrId);
        $categories = ['technical','pedagogical','leadership','compliance','certification','soft_skills','other'];
        return view('pages.hr.training.program_edit', compact('program','categories'));
    }

    public function updateProgram(Request $req, $hrId)
    {
        $req->validate([
            'title'          => 'required|string|max:200',
            'category'       => 'required|in:technical,pedagogical,leadership,compliance,certification,soft_skills,other',
            'description'    => 'nullable|string',
            'provider'       => 'nullable|string|max:150',
            'duration_hours' => 'nullable|integer|min:1',
            'cost'           => 'nullable|numeric|min:0',
            'is_mandatory'   => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
        ]);
        $program = TrainingProgram::findOrFail($hrId);
        $program->update(array_merge(
            $req->only('title','category','description','provider','duration_hours','cost','currency'),
            ['is_mandatory' => $req->boolean('is_mandatory'), 'is_active' => $req->boolean('is_active', true)]
        ));
        AuditLog::log('updated', 'hr', "Training program updated: {$program->title}");
        return redirect()->route('hr.training.programs')->with('flash_success', 'Program updated.');
    }

    public function destroyProgram($hrId)
    {
        $program = TrainingProgram::findOrFail($hrId);
        AuditLog::log('deleted', 'hr', "Training program deleted: {$program->title}");
        $program->delete();
        return back()->with('flash_success', 'Program deleted.');
    }

    // ── ENROLLMENTS ──────────────────────────────────────────────────────────

    public function enrollments(Request $req)
    {
        $search    = trim($req->get('search', ''));
        $status    = $req->get('status', 'all');
        $programId = $req->get('program_id');

        $query = EmployeeTraining::with(['employee.employmentDetails.department', 'program'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($programId, fn($q) => $q->where('training_program_id', $programId))
            ->when($search, fn($q) => $q->whereHas('employee', fn($i) =>
                $i->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
            ))
            ->orderByDesc('start_date');

        $statusCounts = array_merge(
            ['enrolled'=>0,'in_progress'=>0,'completed'=>0,'failed'=>0,'cancelled'=>0],
            EmployeeTraining::selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total','status')->toArray()
        );
        $programs = TrainingProgram::where('is_active', true)->orderBy('title')->get(['id','title']);

        if ($req->get('export') === 'pdf') {
            $enrollments = $query->get();
            $pdf = PDF::loadView('pages.hr.training.enrollments_pdf', compact('enrollments','status','search'));
            return $pdf->setPaper('a4','landscape')->download("training_enrollments_{$status}.pdf");
        }
        if ($req->get('export') === 'csv') {
            $enrollments = $query->get();
            return $this->exportEnrollmentsCsv($enrollments, $status);
        }

        $enrollments = $query->paginate(20);
        return view('pages.hr.training.enrollments', compact(
            'enrollments','status','statusCounts','programs','programId','search'
        ));
    }

    protected function exportEnrollmentsCsv($enrollments, $status)
    {
        $filename = "training_enrollments_{$status}_".now()->format('Y-m-d').".csv";
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$filename}"];
        $callback = function () use ($enrollments) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Employee','Code','Program','Category','Start Date','End Date','Completion Date','Status','Score','Passed','Certificate No.','Cert. Expiry']);
            foreach ($enrollments as $e) {
                fputcsv($h, [
                    $e->employee->full_name,
                    $e->employee->employee_code,
                    $e->program->title,
                    $e->program->categoryLabel(),
                    $e->start_date?->format('Y-m-d') ?? '',
                    $e->end_date?->format('Y-m-d') ?? '',
                    $e->completion_date?->format('Y-m-d') ?? '',
                    $e->statusLabel(),
                    $e->score ?? '',
                    $e->passed === null ? '' : ($e->passed ? 'Yes' : 'No'),
                    $e->certificate_number ?? '',
                    $e->certificate_expiry?->format('Y-m-d') ?? '',
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function enroll(Request $req)
    {
        $req->validate([
            'employee_id'         => 'required|exists:employees,id',
            'training_program_id' => 'required|exists:training_programs,id',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'notes'               => 'nullable|string|max:500',
        ]);

        // Prevent duplicate active enrollment
        $exists = EmployeeTraining::where('employee_id', $req->employee_id)
            ->where('training_program_id', $req->training_program_id)
            ->whereNotIn('status', ['completed','failed','cancelled'])
            ->exists();

        if ($exists) {
            return back()->with('flash_danger', 'This employee is already enrolled in this program.');
        }

        $enrollment = EmployeeTraining::create([
            'employee_id'         => $req->employee_id,
            'training_program_id' => $req->training_program_id,
            'status'              => 'enrolled',
            'start_date'          => $req->start_date,
            'end_date'            => $req->end_date,
            'enrolled_by'         => auth()->id(),
            'notes'               => $req->notes,
        ]);

        $emp     = Employee::find($req->employee_id);
        $program = TrainingProgram::find($req->training_program_id);
        AuditLog::log('created', 'hr', "Employee {$emp->employee_code} enrolled in: {$program->title}");

        return back()->with('flash_success', "{$emp->full_name} enrolled in '{$program->title}'.");
    }

    public function updateEnrollment(Request $req, $hrId)
    {
        $req->validate([
            'status'             => 'required|in:enrolled,in_progress,completed,failed,cancelled',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date',
            'completion_date'    => 'nullable|date',
            'score'              => 'nullable|numeric|min:0|max:100',
            'passed'             => 'nullable|boolean',
            'certificate_number' => 'nullable|string|max:100',
            'certificate_expiry' => 'nullable|date',
            'notes'              => 'nullable|string|max:500',
        ]);

        $enrollment = EmployeeTraining::findOrFail($hrId);
        $enrollment->update($req->only(
            'status','start_date','end_date','completion_date',
            'score','passed','certificate_number','certificate_expiry','notes'
        ));

        AuditLog::log('updated', 'hr', "Training enrollment #{$hrId} updated to: {$req->status}");
        return back()->with('flash_success', 'Training record updated.');
    }

    public function destroyEnrollment($hrId)
    {
        EmployeeTraining::findOrFail($hrId)->delete();
        AuditLog::log('deleted', 'hr', "Training enrollment #{$hrId} deleted");
        return back()->with('flash_success', 'Training record removed.');
    }

    // ── EMPLOYEE TRAINING HISTORY ────────────────────────────────────────────

    public function employeeTraining($employeeId)
    {
        $employee  = Employee::with('employmentDetails.department')->findOrFail($employeeId);
        $trainings = EmployeeTraining::with('program')
            ->where('employee_id', $employeeId)
            ->orderByDesc('start_date')->get();
        $programs  = TrainingProgram::where('is_active', true)->orderBy('title')->get();

        $stats = [
            'total'     => $trainings->count(),
            'completed' => $trainings->where('status','completed')->count(),
            'ongoing'   => $trainings->whereIn('status',['enrolled','in_progress'])->count(),
            'hours'     => $trainings->where('status','completed')
                ->sum(fn($t) => $t->program->duration_hours ?? 0),
        ];

        return view('pages.hr.training.employee_training', compact('employee','trainings','programs','stats'));
    }
}
