@extends('layouts.master')
@section('page_title', 'New Leave Request')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-calendar-plus mr-2"></i>New Leave Request</h5>
    <a href="{{ route('hr.leave.requests') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('hr.leave.requests.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label class="font-weight-bold">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control" required id="employee-select">
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }} ({{ $emp->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type" class="form-control" required>
                                @foreach(['annual'=>'Annual Leave','sick'=>'Sick Leave','maternity'=>'Maternity Leave','paternity'=>'Paternity Leave','unpaid'=>'Unpaid Leave','other'=>'Other'] as $v=>$l)
                                    <option value="{{ $v }}" {{ old('leave_type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Days <span class="text-muted small" id="days-count"></span></label>
                            <input type="text" class="form-control bg-light" id="days-display"
                                   placeholder="Select dates first" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ old('start_date') }}" required id="start-date">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ old('end_date') }}" required id="end-date">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Reason</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="Optional — reason for leave">{{ old('reason') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send mr-1"></i>Submit Request
                        </button>
                        <a href="{{ route('hr.leave.requests') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Count working days between two dates (client-side preview)
function countWorkingDays(start, end) {
    var count = 0;
    var cur = new Date(start);
    var endDate = new Date(end);
    while (cur <= endDate) {
        var day = cur.getDay();
        if (day !== 0 && day !== 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count;
}

function updateDays() {
    var s = $('#start-date').val();
    var e = $('#end-date').val();
    if (s && e && s <= e) {
        var days = countWorkingDays(s, e);
        $('#days-display').val(days + ' working day(s)');
    } else {
        $('#days-display').val('');
    }
}

$('#start-date, #end-date').on('change', updateDays);
</script>
@endsection
