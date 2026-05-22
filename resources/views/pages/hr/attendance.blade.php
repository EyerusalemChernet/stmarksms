@extends('layouts.master')
@section('page_title', 'Staff Attendance')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-clipboard-check mr-2"></i>Staff Attendance</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to HR
    </a>
</div>

{{-- Holiday alert for today --}}
@if(isset($todayHoliday) && $todayHoliday)
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-calendar-event mr-2" style="font-size:1.2rem;"></i>
    <div>
        <strong>Today is a public holiday:</strong> {{ $todayHoliday }}.
        Attendance marked today will still be recorded, but this day is excluded from payroll working-day calculations.
    </div>
</div>
@endif

{{-- Holidays in selected month --}}
@if(isset($monthHolidays) && $monthHolidays->count() > 0)
<div class="alert alert-info py-2 mb-3 small">
    <i class="bi bi-info-circle mr-1"></i>
    <strong>{{ $monthHolidays->count() }} public holiday(s) in {{ $month }}:</strong>
    @foreach($monthHolidays as $h)
        <span class="badge badge-light border ml-1">{{ $h->date->format('d M') }} — {{ $h->name }}</span>
    @endforeach
    — these days are excluded from payroll working-day calculations.
</div>
@endif

{{-- Month summary tab --}}
<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
        <h6 class="card-title mb-0"><i class="bi bi-bar-chart mr-1"></i>Monthly Summary</h6>
        <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
            <form action="{{ route('hr.attendance') }}" method="GET" class="form-inline mb-0" style="gap:6px;">
                <input type="text" name="search" value="{{ $search }}"
                       class="form-control form-control-sm" style="width:160px;"
                       placeholder="Search employee…">
                <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
                <button type="submit" class="btn btn-sm btn-outline-primary">View</button>
                @if($search)
                <a href="{{ route('hr.attendance', ['month'=>$month]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
                @endif
            </form>
            <a href="{{ route('hr.attendance', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('hr.attendance', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </div>
    </div>
    @if($monthlySummary->count())
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Employee</th>
                    <th class="text-center text-success">Present</th>
                    <th class="text-center text-warning">Late</th>
                    <th class="text-center text-danger">Absent</th>
                    <th class="text-center text-info">Leave</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Hours</th>
                    <th class="text-center">Overtime</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                @php $s = $monthlySummary->get($emp->id); @endphp
                @if($s)
                <tr>
                    <td>
                        <div class="d-flex align-items-center" style="gap:6px;">
                            <img src="{{ $emp->photo_url }}" width="24" height="24"
                                 class="rounded-circle" style="object-fit:cover;">
                            <a href="{{ route('hr.show', $emp->id) }}">{{ $emp->full_name }}</a>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge badge-success">{{ $s['present'] }}</span></td>
                    <td class="text-center"><span class="badge badge-warning">{{ $s['late'] }}</span></td>
                    <td class="text-center"><span class="badge badge-danger">{{ $s['absent'] }}</span></td>
                    <td class="text-center"><span class="badge badge-info">{{ $s['leave'] }}</span></td>
                    <td class="text-center">
                        <span class="{{ $s['attendance_rate'] >= 75 ? 'text-success' : 'text-danger' }} font-weight-bold">
                            {{ $s['attendance_rate'] }}%
                        </span>
                    </td>
                    <td class="text-center">{{ $s['actual_hours'] }}h</td>
                    <td class="text-center">
                        @if($s['overtime_hours'] > 0)
                            <span class="text-primary">+{{ $s['overtime_hours'] }}h</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('hr.attendance.report', $emp->id) }}?month={{ $month }}"
                           class="btn btn-xs btn-outline-primary">Report</a>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body text-muted text-center py-3">
        No attendance records for {{ $month }} yet.
    </div>
    @endif
</div>

{{-- CSV Bulk Import --}}
<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            <i class="bi bi-upload mr-1"></i>Bulk Import via CSV
        </h6>
        <a href="{{ route('hr.attendance.template') }}" class="btn btn-xs btn-outline-secondary">
            <i class="bi bi-download mr-1"></i>Download Template
        </a>
    </div>
    <div class="card-body">

        @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-warning py-2 small">
            <strong>Import warnings:</strong>
            <ul class="mb-0 mt-1">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('hr.attendance.import') }}" method="POST" enctype="multipart/form-data"
              class="form-inline flex-wrap" style="gap:10px;">
            @csrf
            <div>
                <label class="font-weight-bold small d-block mb-1">CSV File <span class="text-danger">*</span></label>
                <input type="file" name="csv_file" accept=".csv,.txt"
                       class="form-control form-control-sm" style="width:280px;" required>
            </div>
            <div style="padding-top:22px;">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-upload mr-1"></i>Import
                </button>
            </div>
        </form>

        <div class="mt-2 small text-muted">
            <i class="bi bi-info-circle mr-1"></i>
            CSV columns: <code>employee_code, date (YYYY-MM-DD), status (present/absent/late/leave), sign_in_time (HH:MM), sign_off_time (HH:MM), leave_type, remark</code>.
            Download the template above for a ready-to-fill example.
            Existing records for the same employee + date will be <strong>overwritten</strong>.
        </div>
    </div>
</div>

{{-- Daily marking form --}}
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-pencil-square mr-1"></i>Mark Today's Attendance</h6></div>
    <div class="card-body">
        <form action="{{ route('hr.attendance.save') }}" method="POST">
            @csrf
            <div class="form-group row align-items-center mb-3">
                <label class="col-sm-2 col-form-label font-weight-bold">Date</label>
                <div class="col-sm-3">
                    <input type="date" name="date" class="form-control"
                           value="{{ $today }}" max="{{ $today }}" required>
                </div>
                <div class="col-sm-7 text-right">
                    <button type="button" class="btn btn-sm btn-outline-success mr-1" id="mark-all-present">
                        <i class="bi bi-check-all mr-1"></i>All Present
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="mark-all-absent">
                        <i class="bi bi-x-circle mr-1"></i>All Absent
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Employee</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Leave Type</th>
                            <th>Sign In</th>
                            <th>Sign Off</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                        @php
                            $rec     = $todayRecords->get($emp->id);
                            $current = $rec->status ?? 'present';
                            $shift   = $emp->currentShift?->shift;
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center" style="gap:6px;">
                                    <img src="{{ $emp->photo_url }}" width="26" height="26"
                                         class="rounded-circle" style="object-fit:cover;">
                                    <div>
                                        <a href="{{ route('hr.show', $emp->id) }}">{{ $emp->full_name }}</a>
                                        @if($emp->employmentDetails?->department)
                                            <br><small class="text-muted">{{ $emp->employmentDetails->department->name }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($shift)
                                    <span class="badge badge-dark">{{ $shift->name }}</span>
                                    <br><small class="text-muted">{{ $shift->start_time }}–{{ $shift->end_time }}</small>
                                @else
                                    <span class="text-muted small">No shift</span>
                                @endif
                            </td>
                            <td>
                                <select name="attendance[{{ $emp->id }}][status]"
                                        class="form-control form-control-sm status-select" style="width:110px;">
                                    <option value="present" {{ $current === 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="absent"  {{ $current === 'absent'  ? 'selected' : '' }}>Absent</option>
                                    <option value="late"    {{ $current === 'late'    ? 'selected' : '' }}>Late</option>
                                    <option value="leave"   {{ $current === 'leave'   ? 'selected' : '' }}>Leave</option>
                                </select>
                            </td>
                            <td>
                                <select name="attendance[{{ $emp->id }}][leave_type]"
                                        class="form-control form-control-sm leave-type-select" style="width:120px;">
                                    <option value="">—</option>
                                    @foreach(['annual'=>'Annual','sick'=>'Sick','maternity'=>'Maternity','paternity'=>'Paternity','unpaid'=>'Unpaid','other'=>'Other'] as $v=>$l)
                                        <option value="{{ $v }}" {{ ($rec->leave_type ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="time" name="attendance[{{ $emp->id }}][sign_in_time]"
                                       value="{{ $rec->sign_in_time ?? '' }}"
                                       class="form-control form-control-sm" style="width:100px;">
                            </td>
                            <td>
                                <input type="time" name="attendance[{{ $emp->id }}][sign_off_time]"
                                       value="{{ $rec->sign_off_time ?? '' }}"
                                       class="form-control form-control-sm" style="width:100px;">
                            </td>
                            <td>
                                <input type="text" name="remark[{{ $emp->id }}]"
                                       value="{{ $rec->remark ?? '' }}"
                                       class="form-control form-control-sm" placeholder="Optional"
                                       style="width:120px;">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-success mt-2">
                <i class="bi bi-check-circle mr-1"></i>Save Attendance
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$('#mark-all-present').on('click', function(){
    $('.status-select').val('present');
    $('.leave-type-select').val('');
});
$('#mark-all-absent').on('click', function(){
    $('.status-select').val('absent');
    $('.leave-type-select').val('');
});

// Show/hide leave type based on status
$(document).on('change', '.status-select', function(){
    var row = $(this).closest('tr');
    var leaveSelect = row.find('.leave-type-select');
    if ($(this).val() === 'leave') {
        leaveSelect.prop('disabled', false);
    } else {
        leaveSelect.val('').prop('disabled', false);
    }
});
</script>
@endsection
