<?php

namespace App\Http\Controllers\SupportTeam;

use App\Http\Controllers\Controller;
use App\Models\ApplicationNote;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class RecruitmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('hr_manager');
    }

    // ── JOB POSTINGS ─────────────────────────────────────────────────────────

    public function postings(Request $req)
    {
        $search   = trim($req->get('search',''));
        $statusF  = $req->get('status_filter','all');

        $postings = JobPosting::with(['department','position'])
            ->withCount('applications')
            ->when($search, fn($q) => $q->where('title','like',"%{$search}%")
                                        ->orWhereHas('department', fn($i) => $i->where('name','like',"%{$search}%")))
            ->when($statusF !== 'all', fn($q) => $q->where('status',$statusF))
            ->orderByDesc('created_at')->get();

        $departments = Department::orderBy('name')->get();
        $positions   = Position::orderBy('name')->get();

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.postings_pdf', compact('postings','search'));
            return $pdf->download('job_postings.pdf');
        }
        if ($req->get('export') === 'csv') {
            return $this->exportPostingsCsv($postings);
        }

        return view('pages.hr.recruitment.postings', compact('postings','departments','positions','search','statusF'));
    }

    protected function exportPostingsCsv($postings)
    {
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=job_postings_'.now()->format('Y-m-d').'.csv'];
        $callback = function () use ($postings) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Title','Department','Employment Type','Vacancies','Applications','Deadline','Status']);
            foreach ($postings as $p) {
                fputcsv($h, [
                    $p->title,
                    $p->department?->name ?? '—',
                    ucwords(str_replace('_',' ',$p->employment_type)),
                    $p->vacancies,
                    $p->applications_count,
                    $p->deadline?->format('Y-m-d') ?? '—',
                    ucfirst($p->status),
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function storePosting(Request $req)
    {
        $req->validate([
            'title'           => 'required|string|max:150',
            'department_id'   => 'nullable|exists:departments,id',
            'position_id'     => 'nullable|exists:positions,id',
            'description'     => 'nullable|string',
            'requirements'    => 'nullable|string',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'vacancies'       => 'required|integer|min:1',
            'deadline'        => 'nullable|date',
            'status'          => 'required|in:open,closed,on_hold',
        ]);
        $posting = JobPosting::create(array_merge(
            $req->only('title','department_id','position_id','description','requirements','employment_type','vacancies','deadline','status'),
            ['created_by' => auth()->id()]
        ));
        AuditLog::log('created','hr',"Job posting created: {$posting->title}");
        return redirect()->route('hr.recruitment.postings')->with('flash_success',"Job posting '{$posting->title}' created.");
    }

    public function editPosting($hrId)
    {
        $posting     = JobPosting::findOrFail($hrId);
        $departments = Department::orderBy('name')->get();
        $positions   = Position::orderBy('name')->get();
        return view('pages.hr.recruitment.posting_edit', compact('posting','departments','positions'));
    }

    public function updatePosting(Request $req, $hrId)
    {
        $req->validate([
            'title'           => 'required|string|max:150',
            'department_id'   => 'nullable|exists:departments,id',
            'position_id'     => 'nullable|exists:positions,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'vacancies'       => 'required|integer|min:1',
            'deadline'        => 'nullable|date',
            'status'          => 'required|in:open,closed,on_hold',
        ]);
        $posting = JobPosting::findOrFail($hrId);
        $posting->update($req->only('title','department_id','position_id','description','requirements','employment_type','vacancies','deadline','status'));
        AuditLog::log('updated','hr',"Job posting updated: {$posting->title}");
        return redirect()->route('hr.recruitment.postings')->with('flash_success','Job posting updated.');
    }

    public function destroyPosting($hrId)
    {
        JobPosting::findOrFail($hrId)->delete();
        AuditLog::log('deleted','hr',"Job posting deleted: ID {$hrId}");
        return back()->with('flash_success','Job posting deleted.');
    }

    // ── JOB APPLICATIONS ─────────────────────────────────────────────────────

    public function applications(Request $req)
    {
        $status     = $req->get('status','all');
        $postingId  = $req->get('posting_id');
        $search     = trim($req->get('search',''));

        $query = JobApplication::with('jobPosting')
            ->when($status !== 'all', fn($q) => $q->where('status',$status))
            ->when($postingId, fn($q) => $q->where('job_posting_id',$postingId))
            ->when($search, fn($q) => $q->where(fn($i) =>
                $i->where('first_name','like',"%{$search}%")
                  ->orWhere('last_name','like',"%{$search}%")
                  ->orWhere('email','like',"%{$search}%")
                  ->orWhere('phone','like',"%{$search}%")
                  ->orWhereHas('jobPosting', fn($j) => $j->where('title','like',"%{$search}%"))
            ))
            ->orderByDesc('applied_at');

        $statusCounts = array_merge(
            ['applied'=>0,'shortlisted'=>0,'interviewed'=>0,'hired'=>0,'rejected'=>0],
            JobApplication::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status')->toArray()
        );
        $postings = JobPosting::orderByDesc('created_at')->get(['id','title']);

        if ($req->get('export') === 'pdf') {
            $applications = $query->get();
            $pdf = PDF::loadView('pages.hr.exports.applications_pdf', compact('applications','status','search'));
            return $pdf->setPaper('a4','landscape')->download("applications_{$status}.pdf");
        }
        if ($req->get('export') === 'csv') {
            $applications = $query->get();
            return $this->exportApplicationsCsv($applications, $status);
        }

        $applications = $query->paginate(20);
        return view('pages.hr.recruitment.applications', compact('applications','status','statusCounts','postings','postingId','search'));
    }

    protected function exportApplicationsCsv($applications, $status)
    {
        $filename = "applications_{$status}_".now()->format('Y-m-d').".csv";
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$filename}"];
        $callback = function () use ($applications) {
            $h = fopen('php://output','w');
            fputcsv($h, ['ID','First Name','Last Name','Email','Phone','Job Posting','Applied Date','Interview Date','Status']);
            foreach ($applications as $a) {
                fputcsv($h, [
                    $a->id, $a->first_name, $a->last_name,
                    $a->email ?? '', $a->phone ?? '',
                    $a->jobPosting?->title ?? '—',
                    $a->applied_at->format('Y-m-d'),
                    $a->interview_date?->format('Y-m-d') ?? '',
                    ucfirst($a->status),
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function createApplication($postingId = null)
    {
        $postings = JobPosting::where('status','open')->orderBy('title')->get();
        $selected = $postingId ? JobPosting::find($postingId) : null;
        return view('pages.hr.recruitment.application_create', compact('postings','selected'));
    }

    public function storeApplication(Request $req)
    {
        $req->validate([
            'job_posting_id' => 'required|exists:job_postings,id',
            'first_name'     => 'required|string|max:80',
            'last_name'      => 'required|string|max:80',
            'email'          => 'nullable|email|max:100',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
            'cover_letter'   => 'nullable|string',
        ]);
        $application = JobApplication::create(array_merge(
            $req->only('job_posting_id','first_name','last_name','email','phone','address','cover_letter'),
            ['status'=>'applied','applied_at'=>now()->toDateString()]
        ));
        ApplicationNote::create(['application_id'=>$application->id,'user_id'=>auth()->id(),'status_changed_to'=>'applied','note'=>'Application received.']);
        AuditLog::log('created','hr',"Application received: {$application->full_name}");
        return redirect()->route('hr.recruitment.applications.show', $application->id)->with('flash_success','Application submitted.');
    }

    public function showApplication($hrId)
    {
        $application = JobApplication::with(['jobPosting.department','notes.author','reviewedBy'])->findOrFail($hrId);
        return view('pages.hr.recruitment.application_show', compact('application'));
    }

    public function updateStatus(Request $req, $hrId)
    {
        $req->validate(['status'=>'required|in:applied,shortlisted,interviewed,hired,rejected','note'=>'nullable|string|max:500','interview_date'=>'nullable|date']);
        $application = JobApplication::findOrFail($hrId);
        $oldStatus   = $application->status;
        $application->update(['status'=>$req->status,'reviewed_by'=>auth()->id(),'interview_date'=>$req->interview_date ?? $application->interview_date]);
        ApplicationNote::create(['application_id'=>$hrId,'user_id'=>auth()->id(),'status_changed_to'=>$req->status,'note'=>$req->note ?? "Status changed from {$oldStatus} to {$req->status}."]);
        AuditLog::log('updated','hr',"Application #{$hrId} status: {$oldStatus} → {$req->status}");
        return back()->with('flash_success',"Status updated to ".ucfirst($req->status).".");
    }

    public function addNote(Request $req, $hrId)
    {
        $req->validate(['note'=>'required|string|max:1000']);
        ApplicationNote::create(['application_id'=>$hrId,'user_id'=>auth()->id(),'status_changed_to'=>null,'note'=>$req->note]);
        return back()->with('flash_success','Note added.');
    }

    public function convertToEmployee($hrId)
    {
        $application = JobApplication::with('jobPosting')->findOrFail($hrId);
        if (!$application->isHired()) return back()->with('flash_danger','Only hired applicants can be converted.');
        return redirect()->route('hr.employees.create')->with('prefill', [
            'first_name'     => $application->first_name,
            'last_name'      => $application->last_name,
            'email'          => $application->email,
            'phone'          => $application->phone,
            'address'        => $application->address,
            'department_id'  => $application->jobPosting?->department_id,
            'position_id'    => $application->jobPosting?->position_id,
            'employment_type'=> $application->jobPosting?->employment_type ?? 'full_time',
            'hire_date'      => now()->toDateString(),
            '_from_application' => $application->id,
        ]);
    }
}
