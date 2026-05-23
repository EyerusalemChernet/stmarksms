<?php

namespace App\Http\Controllers\MyParent;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BookRequest;
use App\Models\ExamRecord;
use App\Models\Message;
use App\Models\FeePayment;
use App\Models\StudentFeeInvoice;
use App\Repositories\StudentRepo;
use App\Services\DiscountService;
use App\Services\PenaltyService;
use App\Services\RulesEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyController extends Controller
{
    protected $student;

    public function __construct(StudentRepo $student)
    {
        $this->student = $student;
    }

    /** Parent dashboard — shows all children with summary cards */
    public function dashboard()
    {
        $parentId = Auth::id();
        $year     = Qs::getCurrentSession();

        $children = $this->student->getRecord(['my_parent_id' => $parentId])
            ->with(['my_class', 'section', 'user'])
            ->get();

        // Announcements visible to parents
        $announcements = Announcement::where('active', true)
            ->where(fn($q) => $q->where('audience', 'all')->orWhere('audience', 'parents'))
            ->with('author')->orderByDesc('created_at')->take(5)->get();

        // Unread messages
        $unread = Message::where('receiver_id', $parentId)->where('read', false)->count();

        $familyInfo = DiscountService::getFamilyInfoForParent($parentId);

        // Per-child summary data
        $childData = $children->map(function ($sr) use ($year) {
            $sid = $sr->user_id;

            // Attendance
            $sessions = AttendanceSession::where('year', $year)->pluck('id');
            $total    = AttendanceRecord::whereIn('session_id', $sessions)->where('student_id', $sid)->count();
            $present  = AttendanceRecord::whereIn('session_id', $sessions)->where('student_id', $sid)->whereIn('status', ['present', 'late'])->count();
            $attPct   = $total > 0 ? round(($present / $total) * 100, 1) : 100;

            // Latest exam result
            $latestExr = ExamRecord::where('student_id', $sid)->where('year', $year)->orderByDesc('id')->first();

            // Outstanding school fee invoices
            $feeInvoices = StudentFeeInvoice::where('student_id', $sid)
                ->with('fee_structure.category')
                ->orderByDesc('created_at')
                ->get();
            foreach ($feeInvoices as $inv) {
                if (in_array($inv->status, ['unpaid', 'partial'], true)) {
                    PenaltyService::syncPenaltyForInvoice($inv);
                }
            }
            $unpaidCount   = $feeInvoices->whereIn('status', ['unpaid', 'partial'])->count();
            $feeBalance    = $feeInvoices->sum('balance');
            $feeDiscount   = $feeInvoices->sum('discount');
            $discountType  = DiscountService::getDiscountTypeForStudent($sr);

            // Active library borrows
            $borrowed = BookRequest::where('user_id', $sid)->whereIn('status', ['pending', 'approved'])->with('book')->get();

            // Recent 5 days attendance
            $recentAtt = AttendanceRecord::join('attendance_sessions', 'attendance_records.session_id', '=', 'attendance_sessions.id')
                ->where('attendance_sessions.year', $year)
                ->where('attendance_records.student_id', $sid)
                ->orderByDesc('attendance_sessions.date')
                ->select('attendance_records.status', 'attendance_sessions.date')
                ->take(5)
                ->get();

            return [
                'sr'          => $sr,
                'att_pct'     => $attPct,
                'att_total'   => $total,
                'att_present' => $present,
                'recent_att'  => $recentAtt,
                'latest_exr'  => $latestExr,
                'unpaid'         => $unpaidCount,
                'fee_balance'    => $feeBalance,
                'fee_discount'   => $feeDiscount,
                'discount_type'  => $discountType,
                'fee_invoices'   => $feeInvoices,
                'borrowed'       => $borrowed,
                'blocked'        => RulesEngine::isResultBlocked($sid, $year),
            ];
        });

        return view('pages.parent.dashboard', compact('childData', 'announcements', 'unread', 'year', 'familyInfo'));
    }

    /** Full child detail view for a parent */
    public function childDetail($student_id)
    {
        $parentId = Auth::id();
        $year     = Qs::getCurrentSession();

        // Security: ensure this child belongs to this parent
        $sr = $this->student->getRecord(['user_id' => $student_id, 'my_parent_id' => $parentId])
            ->with(['my_class', 'section', 'user'])->first();

        if (!$sr) {
            return redirect()->route('parent.dashboard')->with('flash_danger', 'Child record not found.');
        }

        // Attendance history
        $sessions = AttendanceSession::where('year', $year)->pluck('id');
        $attRecords = AttendanceRecord::whereIn('session_id', $sessions)
            ->where('student_id', $student_id)
            ->with('session.my_class')
            ->orderByDesc('created_at')->get();
        $total   = $attRecords->count();
        $present = $attRecords->whereIn('status', ['present', 'late'])->count();
        $attPct  = $total > 0 ? round(($present / $total) * 100, 1) : 100;

        // Exam results
        $examRecords = ExamRecord::where('student_id', $student_id)
            ->where('year', $year)->with('exam', 'my_class')->get();

        // School fee invoices (new finance module)
        $feeInvoices = StudentFeeInvoice::where('student_id', $student_id)
            ->with(['fee_structure.category', 'fee_structure.my_class', 'payments'])
            ->orderByDesc('created_at')
            ->get();
        foreach ($feeInvoices as $inv) {
            if (in_array($inv->status, ['unpaid', 'partial'], true)) {
                PenaltyService::syncPenaltyForInvoice($inv);
            }
        }
        $familyInfo    = DiscountService::getFamilyInfoForParent($parentId);
        $discountType  = DiscountService::getDiscountTypeForStudent($sr);

        // Library
        $borrowed = BookRequest::where('user_id', $student_id)
            ->whereIn('status', ['pending', 'approved'])->with('book')->get();
        $borrowHistory = BookRequest::where('user_id', $student_id)
            ->where('status', 'returned')->with('book')->orderByDesc('returned_at')->take(10)->get();

        // Messages from teachers
        $messages = Message::where('receiver_id', $parentId)
            ->whereHas('sender', fn($q) => $q->where('user_type', 'teacher'))
            ->with('sender')->orderByDesc('created_at')->take(10)->get();

        // Timetable
        $timetable = \App\Models\TimeTableRecord::where('my_class_id', $sr->my_class_id)
            ->where('year', $year)->with('my_class')->first();

        $blocked = RulesEngine::isResultBlocked($student_id, $year);

        return view('pages.parent.child_detail', compact(
            'sr', 'attRecords', 'attPct', 'total', 'present',
            'examRecords', 'feeInvoices', 'familyInfo', 'discountType',
            'borrowed', 'borrowHistory', 'messages',
            'timetable', 'blocked', 'year'
        ));
    }

    /** Parent fee payment interface */
    public function feePayment($invoice_id)
    {
        $parentId = Auth::id();
        $id = Qs::decodeHash($invoice_id);
        
        $invoice = StudentFeeInvoice::with(['student', 'fee_structure.category', 'fee_structure.my_class', 'payments'])
            ->findOrFail($id);
        
        // Security: ensure this invoice belongs to parent's child
        $sr = $this->student->getRecord(['user_id' => $invoice->student_id, 'my_parent_id' => $parentId])->first();
        if (!$sr) {
            return redirect()->route('parent.dashboard')->with('flash_danger', 'Access denied.');
        }
        
        // Apply penalties if needed
        if (in_array($invoice->status, ['unpaid', 'partial'])) {
            PenaltyService::syncPenaltyForInvoice($invoice);
            $invoice->refresh();
        }
        
        return view('pages.parent.fee_payment', compact('invoice', 'sr'));
    }
    
    /** Download payment receipt */
    public function downloadReceipt($payment_id)
    {
        $parentId = Auth::id();
        $id = Qs::decodeHash($payment_id);
        
        $payment = FeePayment::with(['student', 'invoice.fee_structure.category', 'invoice.fee_structure.my_class'])
            ->findOrFail($id);
        
        // Security: ensure this payment belongs to parent's child
        $sr = $this->student->getRecord(['user_id' => $payment->student_id, 'my_parent_id' => $parentId])->first();
        if (!$sr) {
            return redirect()->route('parent.dashboard')->with('flash_danger', 'Access denied.');
        }
        
        $settings = \App\Models\Setting::pluck('description', 'type')->toArray();
        
        return view('pages.parent.receipt', compact('payment', 'settings'));
    }

    /** Activity timeline for a child */
    public function timeline($student_id)
    {
        $parentId = Auth::id();
        $year     = Qs::getCurrentSession();

        $sr = $this->student->getRecord(['user_id' => $student_id, 'my_parent_id' => $parentId])
            ->with(['my_class', 'user'])->first();

        if (!$sr) {
            return redirect()->route('parent.dashboard')->with('flash_danger', 'Child record not found.');
        }

        $events = collect();

        // Attendance events
        $sessions = AttendanceSession::where('year', $year)->pluck('id');
        AttendanceRecord::whereIn('session_id', $sessions)
            ->where('student_id', $student_id)
            ->with('session')
            ->get()
            ->each(function ($r) use (&$events) {
                $events->push([
                    'date'  => $r->session->date ?? $r->created_at->toDateString(),
                    'icon'  => $r->status === 'present' ? 'icon-checkmark-circle text-success' : 'icon-cross-circle text-danger',
                    'title' => 'Attendance: ' . ucfirst($r->status),
                    'body'  => 'Class: ' . ($r->session->my_class->name ?? '-'),
                    'ts'    => $r->created_at,
                ]);
            });

        // Exam result events
        ExamRecord::where('student_id', $student_id)->where('year', $year)->with('exam')->get()
            ->each(function ($exr) use (&$events) {
                if ($exr->total) {
                    $events->push([
                        'date'  => $exr->updated_at->toDateString(),
                        'icon'  => 'icon-books text-warning',
                        'title' => 'Exam Result Added',
                        'body'  => ($exr->exam->name ?? 'Exam') . ' — Total: ' . $exr->total . ', Avg: ' . $exr->ave,
                        'ts'    => $exr->updated_at,
                    ]);
                }
            });

        // Library events
        BookRequest::where('user_id', $student_id)->with('book')->get()
            ->each(function ($br) use (&$events) {
                $events->push([
                    'date'  => $br->created_at->toDateString(),
                    'icon'  => 'icon-book text-info',
                    'title' => 'Library: ' . ucfirst($br->status ?? 'Request'),
                    'body'  => 'Book: ' . ($br->book->name ?? '-'),
                    'ts'    => $br->created_at,
                ]);
            });

        FeePayment::where('student_id', $student_id)->with('invoice.fee_structure.category')->get()
            ->each(function ($pay) use (&$events) {
                $events->push([
                    'date'  => ($pay->paid_at ?? $pay->created_at)->toDateString(),
                    'icon'  => 'icon-coin-dollar text-success',
                    'title' => 'Fee Payment',
                    'body'  => (optional($pay->invoice->fee_structure->category)->name ?? 'School fee')
                        . ' — ETB ' . number_format($pay->amount, 2),
                    'ts'    => $pay->paid_at ?? $pay->created_at,
                ]);
            });

        // Announcements
        Announcement::where('active', true)
            ->where(fn($q) => $q->where('audience', 'all')->orWhere('audience', 'parents'))
            ->get()
            ->each(function ($a) use (&$events) {
                $events->push([
                    'date'  => $a->created_at->toDateString(),
                    'icon'  => 'icon-megaphone text-primary',
                    'title' => 'Announcement: ' . $a->title,
                    'body'  => \Illuminate\Support\Str::limit($a->body, 100),
                    'ts'    => $a->created_at,
                ]);
            });

        // Sort by timestamp descending
        $timeline = $events->sortByDesc('ts')->values();

        return view('pages.parent.timeline', compact('sr', 'timeline', 'year'));
    }
}
