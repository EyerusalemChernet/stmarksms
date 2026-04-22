@extends('layouts.master')
@section('page_title', 'Smart Performance Insights')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-graph-up-arrow mr-2 text-primary"></i>Smart Performance Insights</h5>
    <div>
        <span class="badge badge-secondary" style="font-size:12px;">
            <i class="bi bi-calendar3 mr-1"></i>{{ $summary['current_exam'] }}
        </span>
        <a href="{{ route('marks.index') }}" class="btn btn-sm btn-secondary ml-2">
            <i class="bi bi-arrow-left mr-1"></i>Back to Marks
        </a>
    </div>
</div>

{{-- ── Summary Cards ──────────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card danger d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $summary['at_risk_count'] }}</div>
                <div class="stat-label">At-Risk Students</div>
                <small style="opacity:.75;">Below 50% or significant drop</small>
            </div>
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card warning d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $summary['classes_at_risk'] }}</div>
                <div class="stat-label">Classes Need Support</div>
                <small style="opacity:.75;">Average below 45%</small>
            </div>
            <div class="stat-icon"><i class="bi bi-building-exclamation"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card info d-flex align-items-center justify-content-between">
            <div>
                <div class="stat-value">{{ $summary['school_average'] }}%</div>
                <div class="stat-label">School Average</div>
                <small style="opacity:.75;">Across all classes</small>
            </div>
            <div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="stat-card success d-flex align-items-center justify-content-between">
            <div>
                @if($summary['top_performer'])
                    <div class="stat-value" style="font-size:18px;">{{ $summary['top_performer']['average'] }}%</div>
                    <div class="stat-label">{{ $summary['top_performer']['student_name'] }}</div>
                    <small style="opacity:.75;">{{ $summary['top_performer']['class'] }}</small>
                @else
                    <div class="stat-value">—</div>
                    <div class="stat-label">Top Performer</div>
                @endif
            </div>
            <div class="stat-icon"><i class="bi bi-trophy-fill"></i></div>
        </div>
    </div>
</div>

{{-- ── At-Risk Students ────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center" style="background:#ef4444;color:#fff;">
        <i class="bi bi-exclamation-triangle-fill mr-2"></i>
        <h6 class="mb-0">Students Requiring Attention</h6>
        <span class="badge badge-light text-danger ml-2">{{ count($atRiskStudents) }}</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Current Avg</th>
                    <th>Previous Avg</th>
                    <th>Change</th>
                    <th>Risk</th>
                </tr>
            </thead>
            <tbody>
                @forelse($atRiskStudents as $s)
                <tr>
                    <td>{{ $s['student_name'] }}</td>
                    <td>{{ $s['class'] }}</td>
                    <td>
                        <span class="{{ $s['current_avg'] < 50 ? 'text-danger font-weight-bold' : '' }}">
                            {{ $s['current_avg'] }}%
                        </span>
                    </td>
                    <td>{{ $s['previous_avg'] > 0 ? $s['previous_avg'].'%' : '—' }}</td>
                    <td>
                        @if($s['drop_percent'] > 0)
                            <span class="text-danger">↓ {{ $s['drop_percent'] }}%</span>
                        @elseif($s['previous_avg'] == 0)
                            <span class="text-muted">—</span>
                        @else
                            <span class="text-success">↑</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $s['risk_color'] }}">{{ ucfirst($s['risk_level']) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle text-success mr-1"></i>No at-risk students identified
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Class Overview ──────────────────────────────────────────────────────── --}}
<h6 class="font-weight-bold mb-3"><i class="bi bi-grid-3x3-gap mr-1"></i>Class Performance Overview</h6>
@if(count($classOverview))
<div class="row mb-4">
    @foreach($classOverview as $cls)
    @php
        $headerBg = $cls['health'] === 'good' ? '#10b981' : ($cls['health'] === 'warning' ? '#f59e0b' : '#ef4444');
    @endphp
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header text-white" style="background:{{ $headerBg }};">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $cls['class_name'] }}</h6>
                    <span class="badge badge-light" style="color:{{ $headerBg }};">{{ $cls['average'] }}%</span>
                </div>
            </div>
            <div class="card-body py-2 px-3" style="font-size:13px;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Students</span>
                    <strong>{{ $cls['student_count'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Below 50%</span>
                    <strong class="{{ $cls['students_below_50'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $cls['students_below_50'] }}
                    </strong>
                </div>
                <div class="progress mb-2" style="height:6px;">
                    <div class="progress-bar" style="width:{{ $cls['average'] }}%;background:{{ $headerBg }};"></div>
                </div>
                <div class="d-flex justify-content-between" style="font-size:11px;">
                    <span class="text-success">↑ {{ $cls['best_subject'] }} ({{ $cls['best_subject_avg'] }}%)</span>
                    <span class="text-danger">↓ {{ $cls['worst_subject'] }} ({{ $cls['worst_subject_avg'] }}%)</span>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="alert alert-info">No class data available for the current exam.</div>
@endif

{{-- ── Bottom Row: Subject Alerts | Top Performers | Most Improved ─────────── --}}
<div class="row">

    {{-- Subject Alerts --}}
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header" style="background:#f59e0b;color:#fff;">
                <h6 class="mb-0"><i class="bi bi-flag-fill mr-1"></i>Subjects Needing Attention</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Subject</th><th>Avg</th><th>Trend</th></tr>
                    </thead>
                    <tbody>
                        @forelse($subjectAlerts as $a)
                        <tr>
                            <td>{{ $a['subject_name'] }}</td>
                            <td>
                                <span class="{{ $a['alert_level'] === 'critical' ? 'text-danger font-weight-bold' : 'text-warning' }}">
                                    {{ $a['current_avg'] }}%
                                </span>
                            </td>
                            <td>
                                <span class="{{ $a['trend'] === 'declining' ? 'text-danger' : 'text-muted' }}">
                                    {{ $a['trend_icon'] }} {{ ucfirst($a['trend']) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                <i class="bi bi-check-circle text-success mr-1"></i>All subjects adequate
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top Performers --}}
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header" style="background:#10b981;color:#fff;">
                <h6 class="mb-0"><i class="bi bi-trophy-fill mr-1"></i>Top Performers</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($topPerformers as $i => $p)
                        <tr>
                            <td width="28">
                                @if($i === 0) 🥇
                                @elseif($i === 1) 🥈
                                @elseif($i === 2) 🥉
                                @else <span class="text-muted">#{{ $i+1 }}</span>
                                @endif
                            </td>
                            <td>{{ $p['student_name'] }}</td>
                            <td class="text-muted small">{{ $p['class'] }}</td>
                            <td class="text-right"><strong>{{ $p['average'] }}%</strong></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Most Improved --}}
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header" style="background:#4f46e5;color:#fff;">
                <h6 class="mb-0"><i class="bi bi-graph-up-arrow mr-1"></i>Most Improved</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($mostImproved as $s)
                        <tr>
                            <td>{{ $s['student_name'] }}</td>
                            <td class="text-muted small">{{ $s['class'] }}</td>
                            <td class="small text-muted">{{ $s['previous_avg'] }}% → {{ $s['current_avg'] }}%</td>
                            <td class="text-right text-success font-weight-bold">↑ {{ $s['improvement'] }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No comparison data yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
