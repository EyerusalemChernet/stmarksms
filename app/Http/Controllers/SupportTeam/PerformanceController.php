<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PerformanceCategory;
use App\Models\PerformanceReview;
use App\Models\PerformanceScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class PerformanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('hr_manager')->except(['myPerformance']);
        $this->middleware('auth')->only(['myPerformance']);
    }

    // ── SCORE CATEGORIES ─────────────────────────────────────────────────────

    public function categories(Request $req)
    {
        $search     = trim($req->get('search',''));
        $categories = PerformanceCategory::when($search, fn($q) =>
                $q->where('name','like',"%{$search}%")
                  ->orWhere('description','like',"%{$search}%"))
            ->orderBy('name')->get();

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.perf_categories_pdf', compact('categories','search'));
            return $pdf->download('performance_categories.pdf');
        }
        if ($req->get('export') === 'csv') {
            return $this->exportCategoriesCsv($categories);
        }

        return view('pages.hr.performance.categories', compact('categories','search'));
    }

    protected function exportCategoriesCsv($categories)
    {
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=performance_categories.csv'];
        $callback = function () use ($categories) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Name','Weight','Description','Active']);
            foreach ($categories as $c) {
                fputcsv($h, [$c->name, $c->weight, $c->description ?? '', $c->is_active ? 'Yes' : 'No']);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function storeCategory(Request $req)
    {
        $req->validate(['name'=>'required|string|max:100|unique:performance_categories,name','weight'=>'required|numeric|min:0.1|max:100','description'=>'nullable|string|max:255']);
        $cat = PerformanceCategory::create($req->only('name','weight','description'));
        AuditLog::log('created','hr',"Performance category created: {$cat->name}");
        return back()->with('flash_success',"Category '{$cat->name}' created.");
    }

    public function updateCategory(Request $req, $hrId)
    {
        $req->validate(['name'=>'required|string|max:100|unique:performance_categories,name,'.$hrId,'weight'=>'required|numeric|min:0.1|max:100','description'=>'nullable|string|max:255','is_active'=>'nullable|boolean']);
        PerformanceCategory::findOrFail($hrId)->update($req->only('name','weight','description','is_active'));
        AuditLog::log('updated','hr',"Performance category updated: ID {$hrId}");
        return back()->with('flash_success','Category updated.');
    }

    public function destroyCategory($hrId)
    {
        PerformanceCategory::findOrFail($hrId)->delete();
        AuditLog::log('deleted','hr',"Performance category deleted: ID {$hrId}");
        return back()->with('flash_success','Category deleted.');
    }

    // ── PERFORMANCE REVIEWS ──────────────────────────────────────────────────

    public function reviews(Request $req)
    {
        $period           = $req->get('period', now()->format('Y-m'));
        $search           = trim($req->get('search',''));

        $query = PerformanceReview::with(['employee','reviewer'])
            ->when($req->get('period'), fn($q) => $q->where('period',$period))
            ->when($search, fn($q) => $q->whereHas('employee', fn($i) =>
                $i->where('first_name','like',"%{$search}%")
                  ->orWhere('last_name','like',"%{$search}%")
                  ->orWhere('employee_code','like',"%{$search}%")
            ))
            ->orderByDesc('period');

        $availablePeriods = PerformanceReview::selectRaw('period')->groupBy('period')->orderByDesc('period')->pluck('period');

        if ($req->get('export') === 'pdf') {
            $reviews = $query->get();
            $pdf = PDF::loadView('pages.hr.exports.perf_reviews_pdf', compact('reviews','period','search'));
            return $pdf->setPaper('a4','landscape')->download("performance_reviews_{$period}.pdf");
        }
        if ($req->get('export') === 'csv') {
            $reviews = $query->get();
            return $this->exportReviewsCsv($reviews, $period);
        }

        $reviews = $query->paginate(20);
        return view('pages.hr.performance.reviews', compact('reviews','period','availablePeriods','search'));
    }

    protected function exportReviewsCsv($reviews, $period)
    {
        $filename = "performance_reviews_{$period}.csv";
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$filename}"];
        $callback = function () use ($reviews) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Employee','Code','Period','Overall Score','Grade','Reviewer','Date']);
            foreach ($reviews as $r) {
                fputcsv($h, [
                    $r->employee->full_name,
                    $r->employee->employee_code,
                    $r->period,
                    number_format($r->overall_score, 2),
                    $r->gradeLabel(),
                    $r->reviewer?->name ?? '—',
                    $r->created_at->format('Y-m-d'),
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function createReview()
    {
        $employees  = Employee::where('status','active')->with('employmentDetails.department')->orderBy('first_name')->get();
        $categories = PerformanceCategory::where('is_active',true)->orderBy('name')->get();
        if ($categories->isEmpty()) return redirect()->route('hr.performance.categories')->with('flash_danger','Please create at least one performance category first.');
        return view('pages.hr.performance.review_create', compact('employees','categories'));
    }

    public function storeReview(Request $req)
    {
        $req->validate(['employee_id'=>'required|exists:employees,id','period'=>'required|date_format:Y-m','notes'=>'nullable|string|max:1000','scores'=>'required|array','scores.*'=>'required|numeric|min:0|max:10']);
        if (PerformanceReview::where('employee_id',$req->employee_id)->where('period',$req->period)->exists()) {
            return back()->withInput()->with('flash_danger','A review already exists for this employee and period.');
        }
        DB::transaction(function () use ($req) {
            $review = PerformanceReview::create(['employee_id'=>$req->employee_id,'reviewer_id'=>auth()->id(),'period'=>$req->period,'notes'=>$req->notes,'overall_score'=>0]);
            foreach ($req->scores as $categoryId => $score) {
                $category = PerformanceCategory::find($categoryId);
                if (!$category) continue;
                PerformanceScore::create(['review_id'=>$review->id,'category_id'=>$categoryId,'score'=>$score,'weighted_score'=>$score * $category->weight]);
            }
            $review->recalculate();
            AuditLog::log('created','hr',"Performance review created for employee #{$req->employee_id} — period {$req->period}");
        });
        return redirect()->route('hr.performance.reviews')->with('flash_success','Performance review saved.');
    }

    public function showReview($hrId)
    {
        $review = PerformanceReview::with(['employee','reviewer','scores.category'])->findOrFail($hrId);
        return view('pages.hr.performance.review_show', compact('review'));
    }

    public function editReview($hrId)
    {
        $review     = PerformanceReview::with('scores.category')->findOrFail($hrId);
        $categories = PerformanceCategory::where('is_active',true)->orderBy('name')->get();
        $employee   = Employee::findOrFail($review->employee_id);
        return view('pages.hr.performance.review_edit', compact('review','categories','employee'));
    }

    public function updateReview(Request $req, $hrId)
    {
        $req->validate(['notes'=>'nullable|string|max:1000','scores'=>'required|array','scores.*'=>'required|numeric|min:0|max:10']);
        DB::transaction(function () use ($req, $hrId) {
            $review = PerformanceReview::findOrFail($hrId);
            $review->update(['notes'=>$req->notes]);
            foreach ($req->scores as $categoryId => $score) {
                $category = PerformanceCategory::find($categoryId);
                if (!$category) continue;
                PerformanceScore::updateOrCreate(['review_id'=>$hrId,'category_id'=>$categoryId],['score'=>$score,'weighted_score'=>$score * $category->weight]);
            }
            $review->recalculate();
            AuditLog::log('updated','hr',"Performance review #{$hrId} updated");
        });
        return redirect()->route('hr.performance.reviews')->with('flash_success','Review updated.');
    }

    public function destroyReview($hrId)
    {
        PerformanceReview::findOrFail($hrId)->delete();
        AuditLog::log('deleted','hr',"Performance review #{$hrId} deleted");
        return back()->with('flash_success','Review deleted.');
    }

    public function employeeHistory($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        if (!Qs::userIsHRManager() && !Qs::userIsTeamSA()) {
            $linked = Employee::where('user_id',auth()->id())->first();
            if (!$linked || $linked->id !== (int)$employeeId) return redirect()->route('dashboard')->with('flash_danger','Access denied.');
        }
        $reviews = PerformanceReview::with(['scores.category','reviewer'])->where('employee_id',$employeeId)->orderByDesc('period')->get();
        return view('pages.hr.performance.employee_history', compact('employee','reviews'));
    }

    public function myPerformance()
    {
        $employee = Employee::where('user_id',auth()->id())->first();
        if (!$employee) return redirect()->route('dashboard')->with('flash_danger','No employee record linked to your account.');
        return $this->employeeHistory($employee->id);
    }
}
