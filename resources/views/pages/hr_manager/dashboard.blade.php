@extends('layouts.master')
@section('page_title', 'HR Manager Dashboard')
@section('content')

{{-- Stat Cards --}}
<div class="row">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card primary d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $total_staff ?? 0 }}</div><div class="stat-label" data-i18n="total_staff">Total Staff</div></div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card success d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $staff_present_today ?? 0 }}</div><div class="stat-label" data-i18n="present_today">Present Today</div></div>
            <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card danger d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $staff_absent_today ?? 0 }}</div><div class="stat-label" data-i18n="absent_today">Absent Today</div></div>
            <div class="stat-icon"><i class="bi bi-person-x-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card info d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $total_teachers ?? 0 }}</div><div class="stat-label" data-i18n="total_teachers">Teachers</div></div>
            <div class="stat-icon"><i class="bi bi-person-workspace"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card slate d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $unread_messages ?? 0 }}</div><div class="stat-label" data-i18n="unread_messages">Unread Messages</div></div>
            <div class="stat-icon"><i class="bi bi-envelope-fill"></i></div>
        </div>
    </div>
    @php $pendingLeave = $pending_leave_requests ?? 0; @endphp
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card warning d-flex align-items-center justify-content-between">
            <div><div class="stat-value">{{ $pendingLeave }}</div><div class="stat-label">Pending Leave</div></div>
            <div class="stat-icon"><i class="bi bi-calendar-x"></i></div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Quick Actions --}}
    <div class="col-md-12 mb-3">
        <div class="card h-100">
            <div class="card-header"><h6 class="card-title"><i class="bi bi-lightning-charge mr-2 text-warning"></i><span data-i18n="quick_actions">Quick Actions</span></h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4 mb-3"><a href="{{ route('hr.attendance') }}" class="quick-link-card"><i class="bi bi-clipboard-check"></i><small data-i18n="staff_att">Staff Att.</small></a></div>
                    <div class="col-4 mb-3"><a href="{{ route('hr.payroll') }}" class="quick-link-card"><i class="bi bi-cash-stack"></i><small>Payroll</small></a></div>
                    <div class="col-4 mb-3"><a href="{{ route('hr.index') }}" class="quick-link-card"><i class="bi bi-people"></i><small data-i18n="staff_list">Staff List</small></a></div>
                    <div class="col-4 mb-3"><a href="{{ route('hr.leave.requests') }}" class="quick-link-card"><i class="bi bi-calendar-x"></i><small>Leave Requests</small></a></div>
                    <div class="col-4 mb-3"><a href="{{ route('hr.departments') }}" class="quick-link-card"><i class="bi bi-building"></i><small data-i18n="departments">Departments</small></a></div>
                    <div class="col-4 mb-3"><a href="{{ route('hr.recruitment.applications') }}" class="quick-link-card"><i class="bi bi-person-plus"></i><small>Applications</small></a></div>
                    <div class="col-4 mb-3"><a href="{{ route('inbox') }}" class="quick-link-card"><i class="bi bi-envelope"></i><small data-i18n="inbox">Inbox @if(($unread_messages??0)>0)<span class="badge badge-danger" style="font-size:9px;">{{$unread_messages}}</span>@endif</small></a></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Announcements --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title"><i class="bi bi-megaphone mr-2 text-primary"></i><span data-i18n="announcements">Announcements</span></h6>
        <a href="{{ route('announcements') }}" class="btn btn-xs btn-light" data-i18n="view_all">View All</a>
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
        <div class="p-4 text-center text-muted"><p class="mb-0 small" data-i18n="no_announcements">No announcements.</p></div>
        @endforelse
    </div>
</div>
@endsection
