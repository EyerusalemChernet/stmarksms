@extends('layouts.master')
@section('page_title', 'Attendance Report — ' . $employee->full_name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-clipboard-data mr-2"></i>Attendance Report
        <span class="badge badge-light border text-monospace ml-1">{{ $employee->employee_code }}</span>
    </h5>
    <div>
        <a href="{{ route('hr.show', $employee->id) }}" class="btn btn-sm btn-secondary mr-1">
            <i class="bi bi-person mr-1"></i>Profile
        </a>
        <a href="{{ route('hr.attendance') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left mr-1"></i>Back
        </a>
    </div>
</div>

{{-- Month selector --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.attendance.report', $employee->id) }}" method="GET" class="form-inline mb-0">
            <label class="mr-2 font-weight-bold">Month:</label>
            <select name="month" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                @foreach($availableMonths as $m)
                    <option value="{{ $m }}" {{ $m === $month ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
                @if($availableMonths->isEmpty())
                    <option value="{{ $month }}">{{ $month }}</option>
                @endif
            </select>
            <button type="submit" class="btn btn-sm btn-primary">View</button>
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.attendance.report', array_merge([$employee->id], ['month' => $month, 'export' => 'pdf'])) }}"
               class="btn btn-sm btn-danger">
                <i class="bi bi-file-pdf mr-1"></i>PDF
            </a>
            <a href="{{ route('hr.attendance.report', array_merge([$employee->id], ['month' => $month, 'export' => 'csv'])) }}"
               class="btn btn-sm btn-success">
                <i class="bi bi-file-spreadsheet mr-1"></i>CSV
            </a>
        </div>
    </div>
</div>

{{-- Summary cards --}}
<div class="row mb-3">
    <div class="col-md-2">
        <div class="card text-center p-3">
            <img src="{{ $employee->photo_url }}" width="55" height="55"
                 class="rounded-circle mx-auto mb-1" style="object-fit:cover;">
            <small class="font-weight-bold">{{ $employee->full_name }}</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card stat-success text-white text-center p-3">
            <h3>{{ $summary['present'] }}</h3><small>Present</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card stat-warning text-white text-center p-3">
            <h3>{{ $summary['late'] }}</h3><small>Late</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card stat-danger text-white text-center p-3">
            <h3>{{ $summary['absent'] }}</h3><small>Absent</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card stat-info text-white text-center p-3">
            <h3>{{ $summary['leave'] }}</h3><small>Leave</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center p-3">
            <h3 class="{{ $summary['attendance_rate'] >= 75 ? 'text-success' : 'text-danger' }}">
                {{ $summary['attendance_rate'] }}%
            </h3>
            <small>Rate</small>
            <div class="progress mt-1" style="height:5px;">
                <div class="progress-bar {{ $summary['attendance_rate'] >= 75 ? 'bg-success' : 'bg-danger' }}"
                     style="width:{{ $summary['attendance_rate'] }}%"></div>
            </div>
        </div>
    </div>
</div>

{{-- Hours summary --}}
@if($summary['total_actual_hours'] > 0)
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card text-center p-2">
            <h5 class="text-primary mb-0">{{ $summary['total_actual_hours'] }}h</h5>
            <small class="text-muted">Total Hours Worked</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-2">
            <h5 class="{{ $summary['total_overtime_hours'] > 0 ? 'text-success' : 'text-muted' }} mb-0">
                +{{ $summary['total_overtime_hours'] }}h
            </h5>
            <small class="text-muted">Overtime</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-2">
            <h5 class="{{ $summary['total_late_minutes'] > 0 ? 'text-warning' : 'text-muted' }} mb-0">
                {{ $summary['total_late_minutes'] }}m
            </h5>
            <small class="text-muted">Total Late Minutes</small>
        </div>
    </div>
</div>
@endif

{{-- Records table --}}
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">Records for {{ $month }}</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Leave Type</th>
                    <th>Sign In</th>
                    <th>Sign Off</th>
                    <th>Hours</th>
                    <th>Overtime</th>
                    <th>Late</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td>{{ $r->date }}</td>
                    <td>
                        <span class="badge badge-{{ $r->statusBadgeClass() }}">
                            {{ ucfirst($r->status) }}
                        </span>
                    </td>
                    <td>
                        @if($r->status === 'leave' && $r->leave_type)
                            <span class="badge badge-light border">{{ $r->leaveTypeLabel() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $r->sign_in_time  ?? '—' }}</td>
                    <td>{{ $r->sign_off_time ?? '—' }}</td>
                    <td>
                        @if($r->actual_hours)
                            {{ $r->actual_hours }}h
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($r->overtime_hours > 0)
                            <span class="text-success">+{{ $r->overtime_hours }}h</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($r->late_minutes > 0)
                            <span class="text-warning">{{ $r->lateLabel() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $r->remark ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">No records for {{ $month }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $records->links() }}</div>
    </div>
</div>
@endsection
