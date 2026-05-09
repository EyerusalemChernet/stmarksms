@extends('layouts.master')
@section('page_title', 'HR Overview')
@section('content')

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><i class="bi bi-speedometer2 mr-2"></i>HR Overview</h5>
        <small class="text-muted">{{ now()->format('l, d F Y') }}</small>
    </div>
    <div style="gap:6px;" class="d-flex flex-wrap">
        <a href="{{ route('hr.staff') }}"            class="btn btn-sm btn-outline-primary"><i class="bi bi-people mr-1"></i>Staff List</a>
        <a href="{{ route('hr.employees.create') }}" class="btn btn-sm btn-success"><i class="bi bi-person-plus mr-1"></i>Add Employee</a>
        <a href="{{ route('hr.attendance') }}"       class="btn btn-sm btn-outline-success"><i class="bi bi-clipboard-check mr-1"></i>Attendance</a>
        <a href="{{ route('hr.payroll') }}"          class="btn btn-sm btn-outline-warning"><i class="bi bi-cash-stack mr-1"></i>Payroll</a>
    </div>
</div>

{{-- ── Row 1: Headcount stat cards ─────────────────────────────────────────── --}}
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.staff', ['status'=>'active']) }}" class="text-decoration-none">
            <div class="card stat-card stat-success text-white text-center p-3 h-100">
                <h2 class="mb-0 font-weight-bold">{{ $totalActive }}</h2>
                <small>Active Staff</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.staff', ['status'=>'on_leave']) }}" class="text-decoration-none">
            <div class="card stat-card stat-warning text-white text-center p-3 h-100">
                <h2 class="mb-0 font-weight-bold">{{ $totalOnLeave }}</h2>
                <small>On Leave</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.staff', ['status'=>'suspended']) }}" class="text-decoration-none">
            <div class="card stat-card stat-danger text-white text-center p-3 h-100">
                <h2 class="mb-0 font-weight-bold">{{ $totalSuspended }}</h2>
                <small>Suspended</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.staff', ['status'=>'terminated']) }}" class="text-decoration-none">
            <div class="card stat-card stat-secondary text-white text-center p-3 h-100">
                <h2 class="mb-0 font-weight-bold">{{ $totalTerminated }}</h2>
                <small>Terminated</small>
            </div>
        </a>
    </div>
</div>

{{-- ── Row 2: Today's attendance + Payroll + Leave + Recruitment ───────────── --}}
<div class="row mb-3">

    {{-- Today's Attendance --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-calendar-check mr-1 text-success"></i>Today's Attendance</h6>
                <a href="{{ route('hr.attendance') }}" class="btn btn-xs btn-light">Mark</a>
            </div>
            <div class="card-body">
                {{-- Rate ring --}}
                <div class="text-center mb-3">
                    <div style="position:relative;display:inline-block;width:100px;height:100px;">
                        <svg viewBox="0 0 36 36" style="width:100px;height:100px;transform:rotate(-90deg);">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e9ecef" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.9" fill="none"
                                    stroke="{{ $attRate >= 75 ? '#28a745' : ($attRate >= 50 ? '#ffc107' : '#dc3545') }}"
                                    stroke-width="3"
                                    stroke-dasharray="{{ $attRate }} {{ 100 - $attRate }}"
                                    stroke-linecap="round"/>
                        </svg>
                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                            <div class="font-weight-bold" style="font-size:18px;line-height:1;">{{ $attRate }}%</div>
                            <div style="font-size:9px;color:#888;">Rate</div>
                        </div>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-3">
                        <div class="font-weight-bold text-success" style="font-size:18px;">{{ $todayPresent }}</div>
                        <small class="text-muted">Present</small>
                    </div>
                    <div class="col-3">
                        <div class="font-weight-bold text-warning" style="font-size:18px;">{{ $todayLate }}</div>
                        <small class="text-muted">Late</small>
                    </div>
                    <div class="col-3">
                        <div class="font-weight-bold text-danger" style="font-size:18px;">{{ $todayAbsent }}</div>
                        <small class="text-muted">Absent</small>
                    </div>
                    <div class="col-3">
                        <div class="font-weight-bold text-info" style="font-size:18px;">{{ $todayOnLeave }}</div>
                        <small class="text-muted">Leave</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payroll this month --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-cash-stack mr-1 text-warning"></i>Payroll — {{ $month }}</h6>
                <a href="{{ route('hr.payroll') }}" class="btn btn-xs btn-light">Manage</a>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="font-weight-bold text-secondary" style="font-size:22px;">{{ $payrollDraft }}</div>
                        <small class="text-muted">Draft</small>
                    </div>
                    <div class="col-4">
                        <div class="font-weight-bold text-info" style="font-size:22px;">{{ $payrollApproved }}</div>
                        <small class="text-muted">Approved</small>
                    </div>
                    <div class="col-4">
                        <div class="font-weight-bold text-success" style="font-size:22px;">{{ $payrollPaid }}</div>
                        <small class="text-muted">Paid</small>
                    </div>
                </div>
                @if($totalNetPay > 0)
                <div class="text-center border-top pt-2">
                    <small class="text-muted">Total Net Pay (Paid)</small>
                    <div class="font-weight-bold text-success" style="font-size:16px;">
                        ETB {{ number_format($totalNetPay, 2) }}
                    </div>
                </div>
                @else
                <div class="text-center text-muted small border-top pt-2">
                    No paid payrolls for {{ $month }} yet.
                    <br>
                    <a href="{{ route('hr.payroll') }}" class="btn btn-xs btn-outline-success mt-1">
                        <i class="bi bi-plus-circle mr-1"></i>Generate Payroll
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Leave & Recruitment --}}
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="bi bi-calendar-x mr-1 text-danger"></i>Leave & Recruitment</h6>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <a href="{{ route('hr.leave.requests', ['status'=>'pending']) }}" class="text-decoration-none">
                            <div class="font-weight-bold text-warning" style="font-size:28px;">{{ $pendingLeave }}</div>
                            <small class="text-muted">Pending Leave</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <div class="font-weight-bold text-info" style="font-size:28px;">{{ $approvedLeave }}</div>
                        <small class="text-muted">On Leave Today</small>
                    </div>
                </div>
                <hr class="my-2">
                <div class="row text-center">
                    <div class="col-6">
                        <a href="{{ route('hr.recruitment.postings') }}" class="text-decoration-none">
                            <div class="font-weight-bold text-primary" style="font-size:22px;">{{ $openPostings }}</div>
                            <small class="text-muted">Open Postings</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('hr.recruitment.applications', ['status'=>'applied']) }}" class="text-decoration-none">
                            <div class="font-weight-bold text-success" style="font-size:22px;">{{ $newApplications }}</div>
                            <small class="text-muted">New Apps (7d)</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 3: Attendance trend + Department breakdown ──────────────────────── --}}
<div class="row mb-3">

    {{-- 6-month attendance trend --}}
    <div class="col-md-7 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="bi bi-graph-up mr-1 text-primary"></i>Attendance Rate — Last 6 Months</h6>
            </div>
            <div class="card-body">
                @foreach($attendanceTrend as $t)
                <div class="d-flex align-items-center mb-2">
                    <div style="width:70px;font-size:11px;color:#888;flex-shrink:0;">{{ $t['month'] }}</div>
                    <div class="flex-grow-1 mx-2">
                        <div class="progress" style="height:14px;border-radius:7px;">
                            <div class="progress-bar {{ $t['rate'] >= 75 ? 'bg-success' : ($t['rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                 style="width:{{ $t['rate'] }}%;border-radius:7px;transition:width .6s ease;">
                            </div>
                        </div>
                    </div>
                    <div style="width:45px;text-align:right;font-size:12px;font-weight:bold;
                                color:{{ $t['rate'] >= 75 ? '#28a745' : ($t['rate'] >= 50 ? '#ffc107' : '#dc3545') }}">
                        {{ $t['rate'] }}%
                    </div>
                    <div style="width:60px;text-align:right;font-size:10px;color:#aaa;">
                        {{ $t['present'] }}/{{ $t['total'] }}
                    </div>
                </div>
                @endforeach
                @if($attendanceTrend->every(fn($t) => $t['total'] === 0))
                <p class="text-muted text-center small py-3">No attendance data recorded yet.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Department breakdown --}}
    <div class="col-md-5 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-building mr-1 text-info"></i>Staff by Department</h6>
                <a href="{{ route('hr.departments') }}" class="btn btn-xs btn-light">Manage</a>
            </div>
            <div class="card-body p-0">
                @php $maxCount = $deptBreakdown->max('active_count') ?: 1; @endphp
                @forelse($deptBreakdown as $dept)
                <div class="px-3 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-size:12px;font-weight:600;">{{ $dept->name }}</span>
                        <span class="badge badge-info">{{ $dept->active_count }}</span>
                    </div>
                    <div class="progress" style="height:6px;border-radius:3px;">
                        <div class="progress-bar bg-info"
                             style="width:{{ round(($dept->active_count / $maxCount) * 100) }}%;border-radius:3px;">
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-muted small">No departments yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── Row 4: Pending leave requests + Recent hires ────────────────────────── --}}
<div class="row">

    {{-- Pending leave requests --}}
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-hourglass-split mr-1 text-warning"></i>Pending Leave Requests
                    @if($pendingLeave > 0)
                        <span class="badge badge-warning ml-1">{{ $pendingLeave }}</span>
                    @endif
                </h6>
                <a href="{{ route('hr.leave.requests', ['status'=>'pending']) }}" class="btn btn-xs btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentLeave as $req)
                <div class="d-flex align-items-center px-3 py-2 border-bottom">
                    <img src="{{ $req->employee->photo_url }}" width="32" height="32"
                         class="rounded-circle mr-2" style="object-fit:cover;flex-shrink:0;">
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="font-weight-bold" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $req->employee->full_name }}
                        </div>
                        <div style="font-size:11px;color:#888;">
                            {{ $req->leaveTypeLabel() }} ·
                            {{ $req->start_date->format('d M') }} – {{ $req->end_date->format('d M') }}
                            ({{ $req->days_requested }}d)
                        </div>
                    </div>
                    <div class="ml-2 d-flex" style="gap:4px;flex-shrink:0;">
                        <form action="{{ route('hr.leave.requests.approve', $req->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-success" title="Approve"
                                    onclick="return confirm('Approve this leave request?')">
                                <i class="bi bi-check"></i>
                            </button>
                        </form>
                        <form action="{{ route('hr.leave.requests.reject', $req->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-danger" title="Reject"
                                    onclick="return confirm('Reject this leave request?')">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                        <a href="{{ route('hr.leave.requests.show', $req->id) }}" class="btn btn-xs btn-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-muted small">
                    <i class="bi bi-check-circle text-success" style="font-size:1.5rem;"></i>
                    <p class="mt-1 mb-0">No pending leave requests.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent hires --}}
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-person-plus mr-1 text-success"></i>Recent Hires (30 days)</h6>
                <a href="{{ route('hr.staff') }}" class="btn btn-xs btn-light">All Staff</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentHires as $emp)
                <div class="d-flex align-items-center px-3 py-2 border-bottom">
                    <img src="{{ $emp->photo_url }}" width="36" height="36"
                         class="rounded-circle mr-2" style="object-fit:cover;flex-shrink:0;">
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="font-weight-bold" style="font-size:12px;">{{ $emp->full_name }}</div>
                        <div style="font-size:11px;color:#888;">
                            {{ $emp->employmentDetails?->department?->name ?? '—' }}
                            @if($emp->employmentDetails?->hire_date)
                                · Hired {{ $emp->employmentDetails->hire_date->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('hr.show', $emp->id) }}" class="btn btn-xs btn-outline-primary ml-2">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
                @empty
                <div class="p-4 text-center text-muted small">No new hires in the last 30 days.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── Quick actions row ────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0"><i class="bi bi-lightning-charge mr-1 text-warning"></i>Quick Actions</h6>
    </div>
    <div class="card-body">
        <div class="row text-center">
            @foreach([
                ['hr.staff',                    'bi-people',           'Staff List',       'primary'],
                ['hr.employees.create',         'bi-person-plus',      'Add Employee',     'success'],
                ['hr.attendance',               'bi-clipboard-check',  'Attendance',       'info'],
                ['hr.payroll',                  'bi-cash-stack',       'Payroll',          'warning'],
                ['hr.leave.requests',           'bi-calendar-x',       'Leave Requests',   'danger'],
                ['hr.recruitment.postings',     'bi-briefcase',        'Job Postings',     'secondary'],
                ['hr.performance.reviews',      'bi-star',             'Performance',      'primary'],
                ['hr.departments',              'bi-building',         'Departments',      'info'],
            ] as [$route, $icon, $label, $color])
            <div class="col-6 col-md-3 col-lg-3 mb-3">
                <a href="{{ route($route) }}"
                   class="d-block p-3 rounded border text-center text-decoration-none"
                   style="transition:background .15s;"
                   onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background=''">
                    <i class="bi {{ $icon }} text-{{ $color }}" style="font-size:1.6rem;"></i>
                    <div class="mt-1" style="font-size:11px;font-weight:600;color:#555;">{{ $label }}</div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
