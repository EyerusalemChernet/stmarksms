@extends('layouts.master')
@section('page_title', 'Dashboard')
@section('content')

{{-- ── ADMIN / SUPER ADMIN ─────────────────────────────────────────────────── --}}
@if(Qs::userIsTeamSA())
<div class="row" style="gap:0;">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card primary d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $total_students ?? 0 }}</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card success d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $total_teachers ?? 0 }}</div>
                <div class="stat-label">Total Teachers</div>
            </div>
            <div class="stat-icon"><i class="bi bi-person-workspace"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card info d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $attendance_pct ?? 0 }}%</div>
                <div class="stat-label">Avg Attendance</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clipboard-check-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card teal d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $total_paid ?? 0 }}</div>
                <div class="stat-label">Fees Cleared</div>
            </div>
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card warning d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $total_unpaid ?? 0 }}</div>
                <div class="stat-label">Fees Outstanding</div>
            </div>
            <div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card slate d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $total_sessions ?? 0 }}</div>
                <div class="stat-label">Attendance Sessions</div>
            </div>
            <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card pink d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $total_parents ?? 0 }}</div>
                <div class="stat-label">Total Parents</div>
            </div>
            <div class="stat-icon"><i class="bi bi-person-heart"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card danger d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $unread_messages ?? 0 }}</div>
                <div class="stat-label">Unread Messages</div>
            </div>
            <div class="stat-icon"><i class="bi bi-envelope-fill"></i></div>
        </div>
    </div>
</div>
@endif

{{-- ── TEACHER DASHBOARD ───────────────────────────────────────────────────── --}}
@if(Qs::userIsTeacher())
<div class="row mb-3">
    <div class="col-md-4 mb-3">
        <div class="stat-card primary d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ isset($my_subjects) ? $my_subjects->count() : 0 }}</div>
                <div class="stat-label">My Subjects</div>
            </div>
            <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card success d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ isset($today_sessions) ? $today_sessions->count() : 0 }}</div>
                <div class="stat-label">Today's Sessions</div>
            </div>
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card warning d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $parent_messages ?? 0 }}</div>
                <div class="stat-label">Parent Messages</div>
            </div>
            <div class="stat-icon"><i class="bi bi-chat-left-dots"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="bi bi-journal-text mr-2 text-primary"></i>My Subjects</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Subject</th><th>Class</th></tr></thead>
                    <tbody>
                        @forelse($my_subjects ?? [] as $sub)
                        <tr><td>{{ $sub->name }}</td><td><span class="badge badge-primary">{{ $sub->my_class->name ?? '-' }}</span></td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">No subjects assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header"><h6 class="card-title"><i class="bi bi-journal-check mr-2 text-warning"></i>Upcoming Exams</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Exam</th><th>Term</th><th>Year</th></tr></thead>
                    <tbody>
                        @forelse($upcoming_exams ?? [] as $ex)
                        <tr><td>{{ $ex->name }}</td><td><span class="badge badge-info">Term {{ $ex->term }}</span></td><td>{{ $ex->year }}</td></tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No exams scheduled.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── SHARED: Announcements + Quick Links ────────────────────────────────── --}}
<div class="row">
    <div class="col-md-7 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="bi bi-megaphone mr-2 text-primary"></i>Recent Announcements</h6>
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
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-megaphone" style="font-size:24px;opacity:.3;"></i>
                    <p class="mb-0 mt-2" style="font-size:12px;">No announcements yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-5 mb-3">
        <div class="card h-100">
            <div class="card-header"><h6 class="card-title"><i class="bi bi-lightning-charge mr-2 text-warning"></i>Quick Actions</h6></div>
            <div class="card-body">
                <div class="row" style="gap:0;">
                    @if(Qs::userIsTeamSAT())
                    <div class="col-4 mb-3">
                        <a href="{{ route('attendance.index') }}" class="quick-link-card">
                            <i class="bi bi-clipboard-check"></i><small>Attendance</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('marks.index') }}" class="quick-link-card">
                            <i class="bi bi-journal-check"></i><small>Marks</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('library.index') }}" class="quick-link-card">
                            <i class="bi bi-bookshelf"></i><small>Library</small>
                        </a>
                    </div>
                    @endif
                    <div class="col-4 mb-3">
                        <a href="{{ route('announcements') }}" class="quick-link-card">
                            <i class="bi bi-megaphone"></i><small>Announce</small>
                        </a>
                    </div>
                    <div class="col-4 mb-3">
                        <a href="{{ route('inbox') }}" class="quick-link-card">
                            <i class="bi bi-envelope"></i>
                            <small>Inbox @if(($unread_messages ?? 0) > 0)<span class="badge badge-danger" style="font-size:9px;">{{ $unread_messages }}</span>@endif</small>
                        </a>
                    </div>
                    @if(Qs::userIsTeamSA())
                    <div class="col-4 mb-3">
                        <a href="{{ route('reports.index') }}" class="quick-link-card">
                            <i class="bi bi-bar-chart-line"></i><small>Reports</small>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
