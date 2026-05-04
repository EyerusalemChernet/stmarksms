@extends('layouts.master')
@section('page_title', 'Apply for Leave')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-calendar-plus mr-2"></i>Apply for Leave</h5>
    <a href="{{ route('my.leave.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>My Leave
    </a>
</div>

<div class="row">
    {{-- Form --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('my.leave.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label class="font-weight-bold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-control" required id="leave-type-select">
                            <option value="">— Select Leave Type —</option>
                            @foreach(['annual'=>'Annual Leave','sick'=>'Sick Leave','maternity'=>'Maternity Leave','paternity'=>'Paternity Leave','unpaid'=>'Unpaid Leave','other'=>'Other'] as $v=>$l)
                                <option value="{{ $v }}" {{ old('leave_type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Balance hint shown when leave type is selected --}}
                    <div id="balance-hint" class="alert alert-info py-2 small d-none"></div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start-date"
                                   class="form-control" value="{{ old('start_date') }}"
                                   min="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end-date"
                                   class="form-control" value="{{ old('end_date') }}"
                                   min="{{ now()->toDateString() }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Working Days</label>
                        <input type="text" id="days-display" class="form-control bg-light"
                               placeholder="Select dates to calculate" readonly>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Reason</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="Optional — briefly describe the reason">{{ old('reason') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send mr-1"></i>Submit Request
                        </button>
                        <a href="{{ route('my.leave.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Balance sidebar --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bar-chart-steps mr-1"></i>Your Leave Balances — {{ now()->year }}
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Type</th>
                            <th class="text-center">Available</th>
                            <th class="text-center">Used</th>
                            <th class="text-center">Entitled</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary as $type => $bal)
                        @if($bal['entitled'] > 0 || in_array($type, ['annual','sick']))
                        <tr data-type="{{ $type }}" data-available="{{ $bal['available'] }}">
                            <td>{{ $bal['label'] }}</td>
                            <td class="text-center">
                                <span class="font-weight-bold {{ $bal['available'] > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $bal['available'] }}
                                </span>
                            </td>
                            <td class="text-center text-muted">{{ $bal['used'] }}</td>
                            <td class="text-center text-muted">{{ $bal['entitled'] }}</td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body p-3">
                <p class="small text-muted mb-1">
                    <i class="bi bi-info-circle mr-1"></i>
                    Leave requests are reviewed by HR. You will be notified once approved or rejected.
                </p>
                <p class="small text-muted mb-0">
                    Weekends are not counted as leave days.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Balance data from server
var balances = @json(collect($summary)->mapWithKeys(fn($b, $t) => [$t => $b['available']]));

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
        checkBalance(days);
    } else {
        $('#days-display').val('');
        $('#balance-hint').addClass('d-none');
    }
}

function checkBalance(days) {
    var type = $('#leave-type-select').val();
    if (!type || type === 'unpaid') {
        $('#balance-hint').addClass('d-none');
        return;
    }
    var available = balances[type] !== undefined ? balances[type] : null;
    if (available === null) {
        $('#balance-hint').addClass('d-none');
        return;
    }
    if (days > available) {
        $('#balance-hint')
            .removeClass('d-none alert-info alert-success')
            .addClass('alert-warning')
            .html('<i class="bi bi-exclamation-triangle mr-1"></i>You are requesting <strong>' + days + '</strong> day(s) but only have <strong>' + available + '</strong> available.');
    } else {
        $('#balance-hint')
            .removeClass('d-none alert-warning')
            .addClass('alert-info')
            .html('<i class="bi bi-check-circle mr-1"></i>You have <strong>' + available + '</strong> day(s) available for this leave type.');
    }
}

$('#start-date, #end-date').on('change', updateDays);
$('#leave-type-select').on('change', function() {
    var days = parseInt($('#days-display').val()) || 0;
    if (days > 0) checkBalance(days);
    else $('#balance-hint').addClass('d-none');
});
</script>
@endsection
