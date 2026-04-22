@extends('layouts.master')
@section('page_title', 'Early Warning System')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-shield-exclamation mr-2 text-warning"></i>Dropout Early Warning System
    </h5>
    <div class="d-flex align-items-center" style="gap:8px;">
        {{-- Class filter --}}
        <form method="GET" action="{{ route('attendance.risk') }}" class="d-flex" style="gap:6px;">
            <select name="class_id" class="form-control form-control-sm" onchange="this.form.submit()"
                    style="min-width:160px;">
                <option value="">All Classes</option>
                @foreach($classes as $cls)
                    <option value="{{ $cls->id }}" {{ $classId == $cls->id ? 'selected' : '' }}>
                        {{ $cls->name }}
                    </option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>Back
        </a>
    </div>
</div>

{{-- ── Ministry Alert ──────────────────────────────────────────────────────── --}}
@if($summary['average_attendance'] < 75)
<div class="alert alert-warning alert-dismissible mb-3">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="bi bi-exclamation-triangle-fill mr-2"></i>
    <strong>Ministry of Education Alert:</strong>
    School-wide attendance ({{ $summary['average_attendance'] }}%) is below the required
    <strong>75% minimum</strong>. Immediate action is required.
</div>
@endif

{{-- ── Summary Cards ──────────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card danger d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $summary['critical_count'] }}</div>
                <div class="stat-label">Critical Risk</div>
                <small style="opacity:.75;">Immediate intervention</small>
            </div>
            <div class="stat-icon"><i class="bi bi-person-x-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card warning d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $summary['warning_count'] }}</div>
                <div class="stat-label">Warning Level</div>
                <small style="opacity:.75;">Monitor closely</small>
            </div>
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card {{ $summary['attendance_health'] === 'good' ? 'success' : ($summary['attendance_health'] === 'warning' ? 'warning' : 'danger') }} d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $summary['average_attendance'] }}%</div>
                <div class="stat-label">Avg Attendance</div>
                <small style="opacity:.75;">Min required: 75%</small>
            </div>
            <div class="stat-icon"><i class="bi bi-clipboard-check-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card primary d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $summary['total_students'] }}</div>
                <div class="stat-label">Total Students</div>
                <small style="opacity:.75;">{{ $summary['total_at_risk'] }} flagged</small>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
</div>

{{-- ── Student Risk Table ──────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background:#1e1b4b;color:#fff;">
        <h6 class="mb-0">
            <i class="bi bi-shield-exclamation mr-2"></i>Student Risk Assessments
            <span class="badge badge-light text-dark ml-2">{{ count($students) }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Attendance</th>
                    <th>Trend</th>
                    <th>Academic Avg</th>
                    <th>Consec. Absences</th>
                    <th>Risk Score</th>
                    <th>Risk Factors</th>
                    <th>Recommended Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                <tr>
                    <td>
                        <a href="{{ route('attendance.report', $s['student_user_id']) }}"
                           class="text-dark font-weight-bold">
                            {{ $s['student_name'] }}
                        </a>
                    </td>
                    <td>{{ $s['class'] }}</td>
                    <td>
                        @php
                            $attCls = $s['attendance_percent'] >= 75 ? 'success'
                                    : ($s['attendance_percent'] >= 65 ? 'warning' : 'danger');
                        @endphp
                        <span class="badge badge-{{ $attCls }}">
                            {{ $s['attendance_percent'] }}%
                        </span>
                        <small class="text-muted d-block" style="font-size:10px;">
                            {{ $s['attendance_present'] }}/{{ $s['attendance_total'] }} days
                        </small>
                    </td>
                    <td>
                        @if($s['attendance_trend'] === 'declining')
                            <span class="text-danger">↓ Declining</span>
                        @elseif($s['attendance_trend'] === 'improving')
                            <span class="text-success">↑ Improving</span>
                        @else
                            <span class="text-muted">→ Stable</span>
                        @endif
                    </td>
                    <td>
                        @if($s['academic_avg'] > 0)
                            <span class="{{ $s['academic_avg'] < 50 ? 'text-danger font-weight-bold' : '' }}">
                                {{ $s['academic_avg'] }}%
                            </span>
                            @if($s['academic_trend'] === 'declining')
                                <span class="text-danger small">↓</span>
                            @elseif($s['academic_trend'] === 'improving')
                                <span class="text-success small">↑</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($s['consecutive_absences'] >= 5)
                            <span class="text-danger font-weight-bold">
                                {{ $s['consecutive_absences'] }} days
                            </span>
                        @elseif($s['consecutive_absences'] > 0)
                            {{ $s['consecutive_absences'] }} days
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="min-width:110px;">
                        <div class="d-flex align-items-center" style="gap:6px;">
                            <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar bg-{{ $s['risk_color'] }}"
                                     style="width:{{ $s['risk_score'] }}%"></div>
                            </div>
                            <span class="badge badge-{{ $s['risk_color'] }}" style="font-size:11px;">
                                {{ $s['risk_score'] }}
                            </span>
                        </div>
                    </td>
                    <td style="min-width:180px;">
                        @foreach($s['risk_factors'] as $f)
                            <div class="badge badge-secondary mb-1 d-block text-left"
                                 style="white-space:normal;font-size:10px;">
                                {{ $f }}
                            </div>
                        @endforeach
                    </td>
                    <td style="min-width:180px;">
                        @foreach($s['recommendations'] as $r)
                            <div class="badge badge-info mb-1 d-block text-left"
                                 style="white-space:normal;font-size:10px;color:#1e293b;">
                                {{ $r }}
                            </div>
                        @endforeach
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle-fill text-success mr-2" style="font-size:20px;"></i><br>
                        No at-risk students identified. All students meet attendance requirements.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Risk Scoring Methodology ────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-info-circle mr-1 text-primary"></i>Risk Scoring Methodology
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Risk Factor</th><th>Weight</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Attendance below 65% (critical)</td><td><span class="badge badge-danger">30 pts</span></td></tr>
                        <tr><td>Attendance 65–74% (warning)</td><td><span class="badge badge-warning">15 pts</span></td></tr>
                        <tr><td>Attendance declining &gt;10 pp</td><td><span class="badge badge-warning">20 pts</span></td></tr>
                        <tr><td>Academic average below 50%</td><td><span class="badge badge-danger">25 pts</span></td></tr>
                        <tr><td>Grades declining &gt;15 pts</td><td><span class="badge badge-warning">15 pts</span></td></tr>
                        <tr><td>5+ consecutive absences</td><td><span class="badge badge-secondary">10 pts</span></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <div>
                    <p class="mb-2">
                        <span class="badge badge-danger mr-1">Critical (50–100)</span>
                        Immediate intervention required
                    </p>
                    <p class="mb-2">
                        <span class="badge badge-warning mr-1">Warning (25–49)</span>
                        Monitor closely, contact parents
                    </p>
                    <p class="mb-0">
                        <span class="badge badge-success mr-1">Low (0–24)</span>
                        No immediate action needed
                    </p>
                    <hr>
                    <small class="text-muted">
                        <i class="bi bi-info-circle mr-1"></i>
                        Based on Ethiopian Ministry of Education guidelines.
                        Minimum attendance requirement: <strong>75%</strong>.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
