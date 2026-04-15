@php
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BookRequest;
use App\Models\PaymentRecord;
use App\Services\RulesEngine;
use App\Helpers\Qs;

$year = Qs::getCurrentSession();
$uid  = Auth::id();
$userType = Qs::getUserType();
@endphp

{{-- ── PARENT: fee reminder + attendance warning ──────────────────────────── --}}
@if(Qs::userIsParent())
    @php
        $myChildren = \App\Models\StudentRecord::where('my_parent_id', $uid)->with('user')->get();
    @endphp
    @foreach($myChildren as $child)
        @php
            $sessions = AttendanceSession::where('year', $year)->pluck('id');
            $total    = AttendanceRecord::whereIn('session_id', $sessions)->where('student_id', $child->user_id)->count();
            $present  = AttendanceRecord::whereIn('session_id', $sessions)->where('student_id', $child->user_id)->whereIn('status', ['present','late'])->count();
            $pct      = $total > 0 ? round(($present/$total)*100,1) : 100;
            $unpaid   = PaymentRecord::where('student_id', $child->user_id)->where('paid', 0)->count();
        @endphp
        @if($pct < 75)
        <div class="alert alert-warning alert-dismissible border-0 mb-2 py-2">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <i class="icon-warning2 mr-2"></i>
            <strong>{{ $child->user->name }}</strong> has only <strong>{{ $pct }}%</strong> attendance this session. Minimum required is 75%.
        </div>
        @endif
        @if($unpaid > 0)
        <div class="alert alert-danger alert-dismissible border-0 mb-2 py-2">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <i class="icon-coin-dollar mr-2"></i>
            <strong>{{ $child->user->name }}</strong> has <strong>{{ $unpaid }}</strong> outstanding fee payment(s).
            <a href="{{ route('parent.child', $child->user_id) }}" class="alert-link ml-1">View Details</a>
        </div>
        @endif
    @endforeach
@endif

{{-- ── LIBRARIAN / ADMIN: overdue books alert ─────────────────────────────── --}}
@if(Qs::userIsTeamSA())
    @php
        $overdueCount = BookRequest::where('status', 'approved')
            ->where('issued_at', '<', now()->subDays(14))->count();
    @endphp
    @if($overdueCount > 0)
    <div class="alert alert-warning alert-dismissible border-0 mb-2 py-2">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        <i class="icon-book mr-2"></i>
        <strong>{{ $overdueCount }}</strong> overdue library book(s) — issued more than 14 days ago.
        <a href="{{ route('reports.library') }}" class="alert-link ml-1">View Report</a>
    </div>
    @endif
@endif
