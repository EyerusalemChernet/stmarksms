<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BookRequest;
use App\Models\ExamRecord;
use App\Models\MyClass;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Repositories\MyClassRepo;
use App\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class ReportController extends Controller
{
    protected $my_class, $user;

    public function __construct(MyClassRepo $my_class, UserRepo $user)
    {
        // Academic reports: admin, super_admin, teacher
        $this->middleware('teamSAT')->only(['index', 'students', 'attendance', 'academic', 'library']);
        // Finance report: admin, super_admin, hr_manager only
        $this->middleware('hr_manager')->only(['finance']);
        $this->my_class = $my_class;
        $this->user     = $user;
    }

    /** Reports index / overview */
    public function index()
    {
        $year    = Qs::getCurrentSession();
        $classes = MyClass::orderBy('name')->get();
        return view('pages.reports.index', compact('year', 'classes'));
    }

    // ─── STUDENT REPORTS ────────────────────────────────────────────────────────

    public function students(Request $req)
    {
        $year     = $req->get('year', Qs::getCurrentSession());
        $class_id = $req->get('class_id');

        $classQuery = MyClass::withCount(['student_record as active_count' => function ($q) {
            $q->where('grad', 0);
        }])->orderBy('name');

        if ($class_id) $classQuery->where('id', $class_id);
        $classes = $classQuery->get();

        $srQuery = StudentRecord::where('grad', 0)->with('user');
        if ($class_id) $srQuery->where('my_class_id', $class_id);
        $allStudents = $srQuery->get();

        $male   = $allStudents->filter(fn($s) => $s->user && $s->user->gender === 'Male')->count();
        $female = $allStudents->filter(fn($s) => $s->user && $s->user->gender === 'Female')->count();

        $promotions = Promotion::where('from_session', $year)
            ->when($class_id, fn($q) => $q->where('from_class_id', $class_id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')->get()->pluck('total', 'status');

        $allClasses = MyClass::orderBy('name')->get();

        $d = compact('classes', 'allStudents', 'male', 'female', 'promotions', 'year', 'class_id', 'allClasses');

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.reports.exports.students_pdf', $d);
            return $pdf->download("student_report_{$year}.pdf");
        }

        if ($req->get('export') === 'csv') {
            return $this->exportStudentsCsv($classes, $year);
        }

        $d['total'] = $allStudents->count();
        return view('pages.reports.students', $d);
    }

    protected function exportStudentsCsv($classes, $year)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=students_{$year}.csv"];
        $callback = function () use ($classes) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Class', 'Active Students']);
            foreach ($classes as $c) {
                fputcsv($handle, [$c->name, $c->active_count]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ─── ATTENDANCE REPORTS ──────────────────────────────────────────────────────

    public function attendance(Request $req)
    {
        $year     = $req->get('year', Qs::getCurrentSession());
        $class_id = $req->get('class_id');

        $sessions = AttendanceSession::where('year', $year)
            ->when($class_id, fn($q) => $q->where('my_class_id', $class_id))
            ->pluck('id');

        $classQuery = MyClass::orderBy('name');
        if ($class_id) $classQuery->where('id', $class_id);

        $classes = $classQuery->get()->map(function ($cls) use ($sessions) {
            $studentIds = StudentRecord::where('my_class_id', $cls->id)->where('grad', 0)->pluck('user_id');
            $total      = AttendanceRecord::whereIn('session_id', $sessions)->whereIn('student_id', $studentIds)->count();
            $present    = AttendanceRecord::whereIn('session_id', $sessions)->whereIn('student_id', $studentIds)->whereIn('status', ['present', 'late'])->count();
            $cls->att_total   = $total;
            $cls->att_present = $present;
            $cls->att_pct     = $total > 0 ? round(($present / $total) * 100, 1) : 0;
            return $cls;
        });

        $allClasses = MyClass::orderBy('name')->get();

        $d = compact('classes', 'year', 'class_id', 'allClasses');
        $d['total_sessions'] = $sessions->count();

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.reports.exports.attendance_pdf', $d);
            return $pdf->download("attendance_report_{$year}.pdf");
        }

        if ($req->get('export') === 'csv') {
            return $this->exportAttendanceCsv($classes, $year);
        }

        return view('pages.reports.attendance', $d);
    }

    protected function exportAttendanceCsv($classes, $year)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=attendance_{$year}.csv"];
        $callback = function () use ($classes) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Class', 'Total Records', 'Present', 'Attendance %']);
            foreach ($classes as $c) {
                fputcsv($handle, [$c->name, $c->att_total, $c->att_present, $c->att_pct . '%']);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ─── ACADEMIC REPORTS ────────────────────────────────────────────────────────

    public function academic(Request $req)
    {
        $year     = $req->get('year', Qs::getCurrentSession());
        $class_id = $req->get('class_id');

        $classQuery = MyClass::orderBy('name');
        if ($class_id) $classQuery->where('id', $class_id);

        $classes = $classQuery->get()->map(function ($cls) use ($year) {
            $avg = ExamRecord::where('my_class_id', $cls->id)->where('year', $year)->avg('ave');
            $cls->avg_score     = $avg ? round($avg, 1) : 0;
            $cls->student_count = StudentRecord::where('my_class_id', $cls->id)->where('grad', 0)->count();
            return $cls;
        });

        $topQuery = ExamRecord::where('year', $year);
        if ($class_id) $topQuery->where('my_class_id', $class_id);

        $topStudents = $topQuery->selectRaw('student_id, AVG(ave) as overall_avg')
            ->groupBy('student_id')
            ->orderByDesc('overall_avg')
            ->take(10)
            ->with('student.student_record.my_class')
            ->get();

        $allClasses = MyClass::orderBy('name')->get();

        $d = compact('classes', 'topStudents', 'year', 'class_id', 'allClasses');

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.reports.exports.academic_pdf', $d);
            return $pdf->download("academic_report_{$year}.pdf");
        }

        if ($req->get('export') === 'csv') {
            return $this->exportAcademicCsv($classes, $topStudents, $year);
        }

        return view('pages.reports.academic', $d);
    }

    protected function exportAcademicCsv($classes, $topStudents, $year)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=academic_{$year}.csv"];
        $callback = function () use ($classes, $topStudents) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Class', 'Students', 'Average Score']);
            foreach ($classes as $c) {
                fputcsv($handle, [$c->name, $c->student_count, $c->avg_score]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Rank', 'Student', 'Average']);
            foreach ($topStudents as $i => $exr) {
                fputcsv($handle, [$i + 1, $exr->student->name ?? '-', round($exr->overall_avg, 1)]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ─── FINANCE REPORTS ─────────────────────────────────────────────────────────

    public function finance(Request $req)
    {
        $year     = $req->get('year', Qs::getCurrentSession());
        $class_id = $req->get('class_id');

        $totalCollected = PaymentRecord::where('paid', 1)
            ->whereHas('payment', fn($q) => $q->where('year', $year))
            ->sum('amt_paid');

        $totalOutstanding = PaymentRecord::where('paid', 0)
            ->whereHas('payment', fn($q) => $q->where('year', $year))
            ->sum(DB::raw('COALESCE(balance, 0)'));

        $totalStudentsPaid   = PaymentRecord::where('paid', 1)->whereHas('payment', fn($q) => $q->where('year', $year))->distinct('student_id')->count('student_id');
        $totalStudentsUnpaid = PaymentRecord::where('paid', 0)->whereHas('payment', fn($q) => $q->where('year', $year))->distinct('student_id')->count('student_id');

        $classQuery = MyClass::orderBy('name');
        if ($class_id) $classQuery->where('id', $class_id);

        $classes = $classQuery->get()->map(function ($cls) use ($year) {
            $paid   = PaymentRecord::where('paid', 1)->whereHas('payment', fn($q) => $q->where('year', $year)->where('my_class_id', $cls->id))->sum('amt_paid');
            $unpaid = PaymentRecord::where('paid', 0)->whereHas('payment', fn($q) => $q->where('year', $year)->where('my_class_id', $cls->id))->count();
            $cls->paid_amount  = $paid;
            $cls->unpaid_count = $unpaid;
            return $cls;
        });

        $allClasses = MyClass::orderBy('name')->get();

        $d = compact('totalCollected', 'totalOutstanding', 'totalStudentsPaid', 'totalStudentsUnpaid', 'classes', 'year', 'class_id', 'allClasses');
        $d['total_collected']   = $totalCollected;
        $d['total_outstanding'] = $totalOutstanding;
        $d['students_paid']     = $totalStudentsPaid;
        $d['students_unpaid']   = $totalStudentsUnpaid;

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.reports.exports.finance_pdf', $d);
            return $pdf->download("finance_report_{$year}.pdf");
        }

        if ($req->get('export') === 'csv') {
            return $this->exportFinanceCsv($classes, $year);
        }

        return view('pages.reports.finance', $d);
    }

    protected function exportFinanceCsv($classes, $year)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=finance_{$year}.csv"];
        $callback = function () use ($classes) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Class', 'Amount Collected', 'Unpaid Records']);
            foreach ($classes as $c) {
                fputcsv($handle, [$c->name, $c->paid_amount, $c->unpaid_count]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ─── LIBRARY REPORTS ─────────────────────────────────────────────────────────

    public function library(Request $req)
    {
        $mostBorrowed = BookRequest::selectRaw('book_id, count(*) as borrow_count')
            ->groupBy('book_id')
            ->orderByDesc('borrow_count')
            ->take(10)
            ->with('book')
            ->get();

        $overdue = BookRequest::where('status', 'approved')
            ->where('issued_at', '<', now()->subDays(14))
            ->with(['book', 'user'])
            ->get();

        $history = BookRequest::whereIn('status', ['returned', 'approved'])
            ->with(['book', 'user'])
            ->orderByDesc('updated_at')
            ->take(20)
            ->get();

        $d = compact('mostBorrowed', 'overdue', 'history');

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.reports.exports.library_pdf', $d);
            return $pdf->download('library_report.pdf');
        }

        if ($req->get('export') === 'csv') {
            return $this->exportLibraryCsv($mostBorrowed);
        }

        return view('pages.reports.library', $d);
    }

    protected function exportLibraryCsv($mostBorrowed)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=library_report.csv'];
        $callback = function () use ($mostBorrowed) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Book', 'Times Borrowed']);
            foreach ($mostBorrowed as $br) {
                fputcsv($handle, [$br->book->name ?? '-', $br->borrow_count]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }
}
