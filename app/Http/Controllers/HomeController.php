<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ExamRecord;
use App\Models\Message;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\StaffAttendance;
use App\Models\Subject;
use App\Repositories\UserRepo;
use App\Repositories\StudentRepo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    protected $user, $student;

    public function __construct(UserRepo $user, StudentRepo $student)
    {
        $this->user    = $user;
        $this->student = $student;
    }

    public function index()
    {
        return redirect()->route('dashboard');
    }

    public function privacy_policy()
    {
        $data['app_name']      = config('app.name');
        $data['app_url']       = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.privacy_policy', $data);
    }

    public function terms_of_use()
    {
        $data['app_name']      = config('app.name');
        $data['app_url']       = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.terms_of_use', $data);
    }

    public function dashboard()
    {
        // Parents get their own dedicated dashboard
        if (Qs::userIsParent()) {
            return redirect()->route('parent.dashboard');
        }

        $d    = [];
        $year = Qs::getCurrentSession();
        $uid  = Auth::id();

        // ── Admin / Super Admin dashboard ──────────────────────────────────────
        if (Qs::userIsTeamSA()) {
            $users = $this->user->getAll();
            $d['total_students'] = $users->where('user_type', 'student')->count();
            $d['total_teachers'] = $users->where('user_type', 'teacher')->count();
            $d['total_admins']   = $users->where('user_type', 'admin')->count();
            $d['total_parents']  = $users->where('user_type', 'parent')->count();

            $sessions           = AttendanceSession::where('year', $year)->pluck('id');
            $totalRec           = AttendanceRecord::whereIn('session_id', $sessions)->count();
            $presentRec         = AttendanceRecord::whereIn('session_id', $sessions)->whereIn('status', ['present', 'late'])->count();
            $d['attendance_pct'] = $totalRec > 0 ? round(($presentRec / $totalRec) * 100, 1) : 0;
            $d['total_sessions'] = $sessions->count();

            $d['total_paid']   = PaymentRecord::where('paid', 1)->count();
            $d['total_unpaid'] = PaymentRecord::where('paid', 0)->count();

            $d['announcements']    = $this->getAnnouncements($uid);
            $d['unread_messages']  = Message::where('receiver_id', $uid)->where('read', false)->count();

            return view('pages.admin.dashboard', $d);
        }

        // ── HR Manager dashboard ───────────────────────────────────────────────
        if (Qs::userIsHRManager()) {
            $users = $this->user->getAll();
            $d['total_staff']    = $users->whereIn('user_type', ['admin', 'teacher', 'hr_manager', 'super_admin'])->count();
            $d['total_teachers'] = $users->where('user_type', 'teacher')->count();

            $today = now()->toDateString();
            $d['staff_present_today'] = \App\Models\StaffAttendance::where('date', $today)->whereIn('status', ['present', 'late'])->count();
            $d['staff_absent_today']  = \App\Models\StaffAttendance::where('date', $today)->where('status', 'absent')->count();

            $d['total_collected']  = PaymentRecord::where('paid', 1)->sum('amt_paid');
            $d['total_outstanding'] = PaymentRecord::where('paid', 0)->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(balance, 0)'));
            $d['students_unpaid']  = PaymentRecord::where('paid', 0)->distinct('student_id')->count('student_id');

            $d['recent_payments'] = \App\Models\Receipt::with('pr.payment')
                ->orderByDesc('created_at')->take(8)->get();

            $d['announcements']   = $this->getAnnouncements($uid);
            $d['unread_messages'] = Message::where('receiver_id', $uid)->where('read', false)->count();

            return view('pages.hr_manager.dashboard', $d);
        }

        // ── Teacher dashboard ──────────────────────────────────────────────────
        if (Qs::userIsTeacher()) {
            $d['my_subjects'] = Subject::where('teacher_id', $uid)->with('my_class')->get();

            $myClassIds = $d['my_subjects']->pluck('my_class_id')->unique();
            $d['today_sessions'] = AttendanceSession::whereIn('my_class_id', $myClassIds)
                ->where('date', today()->toDateString())
                ->with('my_class', 'section')->get();

            $d['parent_messages'] = Message::where('receiver_id', $uid)
                ->whereHas('sender', fn($q) => $q->where('user_type', 'parent'))
                ->where('read', false)->count();

            $d['upcoming_exams'] = \App\Models\Exam::where('year', $year)
                ->orderBy('id', 'desc')->take(3)->get();

            $d['announcements']   = $this->getAnnouncements($uid);
            $d['unread_messages'] = Message::where('receiver_id', $uid)->where('read', false)->count();

            return view('pages.teacher.dashboard', $d);
        }

        // Fallback for any other authenticated role
        $d['announcements']   = $this->getAnnouncements($uid);
        $d['unread_messages'] = Message::where('receiver_id', $uid)->where('read', false)->count();
        return view('pages.support_team.dashboard', $d);
    }

    protected function getAnnouncements(int $uid)
    {
        $userType = Qs::getUserType();
        return Announcement::where('active', true)
            ->where(fn($q) => $q->where('audience', 'all')->orWhere('audience', $userType . 's'))
            ->with('author')->orderByDesc('created_at')->take(5)->get();
    }
}
