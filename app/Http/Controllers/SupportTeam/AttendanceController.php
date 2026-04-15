<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\MyClass;
use App\Models\Section;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Services\RulesEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    protected $my_class, $student;

    public function __construct(MyClassRepo $my_class, StudentRepo $student)
    {
        $this->middleware('teamSAT');
        $this->my_class = $my_class;
        $this->student  = $student;
    }

    /**
     * Attendance index.
     * - Admin/Super Admin: show full class selector.
     * - Teacher: auto-load their homeroom section(s) — no selector shown.
     */
    public function index()
    {
        $uid = Auth::id();

        if (Qs::userIsTeamSA()) {
            // Admin sees all classes
            $d['my_classes'] = $this->my_class->all();
            $d['is_admin']   = true;
            $d['sections']   = collect();
        } else {
            // Teacher: find sections where they are the homeroom teacher
            $homeroomSections = Section::where('teacher_id', $uid)
                ->with('my_class')
                ->get();

            $d['my_classes']       = collect();
            $d['is_admin']         = false;
            $d['homeroom_sections'] = $homeroomSections;
        }

        return view('pages.support_team.attendance.index', $d);
    }

    /**
     * Open an attendance session.
     * Teachers can only open sessions for their own homeroom section.
     * Admins can open for any class/section.
     */
    public function create(Request $req)
    {
        $uid = Auth::id();

        $this->validate($req, [
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id'  => 'required|exists:sections,id',
            'date'        => 'required|date',
        ]);

        // Homeroom enforcement for teachers
        if (!Qs::userIsTeamSA()) {
            $isHomeroom = Section::where('id', $req->section_id)
                ->where('teacher_id', $uid)
                ->exists();

            if (!$isHomeroom) {
                return back()->with('pop_error',
                    'You are not assigned to this class. Only the homeroom teacher can mark attendance for this section.'
                );
            }
        }

        // Duplicate / future date / other rule checks
        $validation = RulesEngine::validateAttendanceSession(
            $req->my_class_id,
            $req->section_id,
            $req->date,
            $uid
        );

        if (!$validation['valid']) {
            return back()->with('pop_error', $validation['message']);
        }

        $year    = Qs::getCurrentSession();
        $session = AttendanceSession::firstOrCreate(
            ['my_class_id' => $req->my_class_id, 'section_id' => $req->section_id, 'date' => $req->date],
            ['teacher_id' => $uid, 'year' => $year]
        );

        return redirect()->route('attendance.manage', $session->id);
    }

    /**
     * Show the attendance marking form for a session.
     * Teachers can only access sessions for their homeroom section.
     */
    public function manage($session_id)
    {
        $uid     = Auth::id();
        $session = AttendanceSession::with(['my_class', 'section'])->findOrFail($session_id);

        // Homeroom enforcement for teachers
        if (!Qs::userIsTeamSA()) {
            $isHomeroom = Section::where('id', $session->section_id)
                ->where('teacher_id', $uid)
                ->exists();

            if (!$isHomeroom) {
                return redirect()->route('attendance.index')
                    ->with('flash_danger', 'You are not assigned to this class. Only the homeroom teacher can mark attendance for this section.');
            }
        }

        $students = $this->student->getRecord([
            'my_class_id' => $session->my_class_id,
            'section_id'  => $session->section_id,
        ])->get()->sortBy('user.name');

        if ($students->isEmpty()) {
            return redirect()->route('attendance.index')
                ->with('flash_danger', 'No students found in this class/section.');
        }

        $existing = AttendanceRecord::where('session_id', $session_id)->pluck('status', 'student_id');

        return view('pages.support_team.attendance.manage', [
            'session'  => $session,
            'students' => $students,
            'existing' => $existing,
        ]);
    }

    /**
     * Save attendance records.
     * Validates: homeroom restriction, non-empty submission.
     */
    public function store(Request $req, $session_id)
    {
        $uid     = Auth::id();
        $session = AttendanceSession::with('my_class')->findOrFail($session_id);

        // Homeroom enforcement for teachers
        if (!Qs::userIsTeamSA()) {
            $isHomeroom = Section::where('id', $session->section_id)
                ->where('teacher_id', $uid)
                ->exists();

            if (!$isHomeroom) {
                return redirect()->route('attendance.index')
                    ->with('flash_danger', 'You are not assigned to this class.');
            }
        }

        $students = $this->student->getRecord([
            'my_class_id' => $session->my_class_id,
            'section_id'  => $session->section_id,
        ])->get();

        if ($students->isEmpty()) {
            return back()->with('flash_danger', 'No students found. Attendance not saved.');
        }

        foreach ($students as $st) {
            $status = $req->input('status_' . $st->user_id, 'absent');
            AttendanceRecord::updateOrCreate(
                ['session_id' => $session_id, 'student_id' => $st->user_id],
                ['status' => $status]
            );
        }

        AuditLog::log('created', 'attendance',
            "Attendance saved for session #{$session_id} (" . ($session->my_class->name ?? '') . " - {$session->date})"
        );

        return redirect()->route('attendance.index')
            ->with('flash_success', 'Attendance saved successfully.');
    }

    /**
     * View attendance report for a student.
     */
    public function report($student_id)
    {
        $year = Qs::getCurrentSession();
        $sr   = $this->student->getRecord(['user_id' => $student_id])->first();
        if (!$sr) return Qs::goWithDanger();

        // Teachers can only view reports for students in their homeroom
        if (!Qs::userIsTeamSA()) {
            $isHomeroom = Section::where('id', $sr->section_id)
                ->where('teacher_id', Auth::id())
                ->exists();

            if (!$isHomeroom) {
                return redirect()->route('attendance.index')
                    ->with('flash_danger', 'You are not assigned to this student\'s class.');
            }
        }

        $sessions = AttendanceSession::where('year', $year)->pluck('id');
        $records  = AttendanceRecord::whereIn('session_id', $sessions)
            ->where('student_id', $student_id)
            ->with('session')
            ->orderByDesc('created_at')
            ->get();

        $total   = $records->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();
        $pct     = $total > 0 ? round(($present / $total) * 100, 1) : 100;

        return view('pages.support_team.attendance.report', [
            'sr'      => $sr,
            'records' => $records,
            'total'   => $total,
            'present' => $present,
            'absent'  => $total - $present,
            'pct'     => $pct,
            'year'    => $year,
            'blocked' => RulesEngine::isResultBlocked($student_id, $year),
        ]);
    }

    /**
     * List all sessions.
     * Teachers only see sessions for their homeroom sections.
     */
    public function sessions()
    {
        $uid = Auth::id();

        if (Qs::userIsTeamSA()) {
            $sessions = AttendanceSession::with(['my_class', 'section', 'teacher'])
                ->orderByDesc('date')->paginate(30);
        } else {
            $mySectionIds = Section::where('teacher_id', $uid)->pluck('id');
            $sessions = AttendanceSession::whereIn('section_id', $mySectionIds)
                ->with(['my_class', 'section', 'teacher'])
                ->orderByDesc('date')->paginate(30);
        }

        return view('pages.support_team.attendance.sessions', ['sessions' => $sessions]);
    }
}
