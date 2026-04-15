@extends('layouts.master')
@section('page_title', 'HR Manager Dashboard')
@section('content')

{{-- Stat Cards --}}
<div class="row">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card primary d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $total_staff ?? 0 }}</div><div class="stat-label">Total Staff</div></div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card success d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $staff_present_today ?? 0 }}</div><div class="stat-label">Present Today</div></div>
            <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card danger d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $staff_absent_today ?? 0 }}</div><div class="stat-label">Absent Today</div></div>
            <div class="stat-icon"><i class="bi bi-person-x-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card info d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $total_teachers ?? 0 }}</div><div class="stat-label">Teachers</div></div>
            <div class="stat-icon"><i class="bi bi-person-workspace"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card teal d-flex align-items-center justify-content-between">
            <div><div class="stat-value">ETB {{ number_format($total_collected ?? 0) }}</div><div class="stat-label">Fees Collected</div></div>
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card warning d-flex align-items-center justify-content-between">
            <div><div class="stat-value">ETB {{ number_format($total_outstanding ?? 0) }}</div><div class="stat-label">Outstanding</div></div>
            <div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card pink d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $students_unpaid ?? 0 }}</div><div class="stat-label">Students Unpaid</div></div>
            <div class="stat-icon"><i class="bi bi-person-exclamation"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card slate d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $unread_messages ?? 0 }}</div><div class="stat-label">Unread Messages</div></div>
            <div class="stat-icon"><i class="bi bi-envelope-fill"></i></div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Recent Payments --}}
    <div class="col-md-7 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="bi bi-receipt mr-2 text-success"></i>Recent Payments</h6>
                <a href="{{ route('payments.manage') }}" class="btn btn-xs btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Fee</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($recent_payments ?? [] as $r)
                        <tr>
                            <td>{{ $r->pr->payment->title ?? '—' }}</td>
                            <td class="text-success">ETB {{ number_format($r->amt_paid) }}</td>
                            <td><span class="badge badge-secondary">{{ ucfirst($r->payment_method ?? 'cash') }}</span></td>
                            <td class="small text-muted">{{ $r->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-5 mb-3">
        <div class="card h-100">
            <div class="card-header"><h6 class="card-title"><i class="bi bi-lightning-charge mr-2 text-warning"></i>Quick Actions</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4 mb-3">
                        <a href="{{ route('hr.attendance') }}" class="quick-link-card">
                            <i class="bi bi-clipboard-check"></i><small>Staff Att.</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('payments.manage') }}" class="quick-link-card">
                            <i class="bi bi-cash-coin"></i><small>Payments</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('hr.index') }}" class="quick-link-card">
                            <i class="bi bi-people"></i><small>Staff List</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('reports.finance') }}" class="quick-link-card">
                            <i class="bi bi-bar-chart-line"></i><small>Finance Rpt</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('hr.departments') }}" class="quick-link-card">
                            <i class="bi bi-building"></i><small>Departments</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('inbox') }}" class="quick-link-card">
                            <i class="bi bi-envelope"></i>
                            <small>Inbox @if(($unread_messages??0)>0)<span class="badge badge-danger" style="font-size:9px;">{{$unread_messages}}</span>@endif</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Announcements --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title"><i class="bi bi-megaphone mr-2 text-primary"></i>Announcements</h6>
        <a href="{{ route('announcements') }}" class="btn btn-xs btn-light">View All</a>
    </div>
    <div class="card-body p-0">
        @forelse($announcements ?? [] as $a)
        <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <strong style="font-size:13px;">{{ $a->title }}</strong>
                <small class="text-muted ml-2" style="white-space:nowrap;">{{ $a->created_at->diffForHumans() }}</small>
            </div>
            <p class="mb-0 text-muted" style="font-size:12px;margin-top:3px;">{{ \Illuminate\Support\Str::limit($a->body, 120) }}</p>
        </div>
        @empty
        <div class="p-4 text-center text-muted"><p class="mb-0 small">No announcements.</p></div>
        @endforelse
    </div>
</div>
@endsection
