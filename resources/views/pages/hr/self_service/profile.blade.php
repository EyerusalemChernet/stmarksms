@extends('layouts.master')
@section('page_title', 'My Profile')
@section('content')

@php $ed = $employee->employmentDetails; @endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-person-badge mr-2"></i>My Profile</h5>
    <div style="gap:6px;" class="d-flex">
        <a href="{{ route('my.payslips') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-cash-stack mr-1"></i>My Payslips
        </a>
        <a href="{{ route('my.performance.self') }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-star mr-1"></i>My Performance
        </a>
        <a href="{{ route('my.leave.index') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-calendar-heart mr-1"></i>My Leave
        </a>
    </div>
</div>

<div class="row">
    {{-- Identity card --}}
    <div class="col-md-4">
        <div class="card text-center p-3 mb-3">
            <img src="{{ $employee->photo_url }}" width="90" height="90"
                 class="rounded-circle mx-auto mb-2" style="object-fit:cover;border:3px solid #e0e0e0;">
            <h5 class="mb-0">{{ $employee->full_name }}</h5>
            <p class="text-muted small mb-1">{{ $employee->employee_code }}</p>
            @if($ed?->department)
                <span class="badge badge-info">{{ $ed->department->name }}</span>
            @endif
            @if($ed?->position)
                <span class="badge badge-primary ml-1">{{ $ed->position->name }}</span>
            @endif
            <hr>
            <div class="text-left small">
                <p class="mb-1"><i class="bi bi-envelope mr-1 text-muted"></i>{{ $employee->email ?? '—' }}</p>
                <p class="mb-1"><i class="bi bi-phone mr-1 text-muted"></i>{{ $employee->phone ?? '—' }}</p>
                <p class="mb-1"><i class="bi bi-geo-alt mr-1 text-muted"></i>{{ $employee->address ?? '—' }}</p>
                @if($ed?->hire_date)
                    <p class="mb-1"><i class="bi bi-calendar-check mr-1 text-muted"></i>
                        Hired: {{ $ed->hire_date->format('d M Y') }}</p>
                @endif
                @if($ed?->employment_type)
                    <p class="mb-1"><i class="bi bi-briefcase mr-1 text-muted"></i>
                        {{ $ed->employmentTypeLabel() }}</p>
                @endif
                @if($employee->currentShift?->shift)
                    <p class="mb-1"><i class="bi bi-clock mr-1 text-muted"></i>
                        Shift: {{ $employee->currentShift->shift->name }}
                        ({{ $employee->currentShift->shift->start_time }}–{{ $employee->currentShift->shift->end_time }})
                    </p>
                @endif
            </div>
        </div>

        {{-- Leave balance summary --}}
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-calendar-heart mr-1"></i>Leave Balances</h6>
                <a href="{{ route('my.leave.index') }}" class="btn btn-xs btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach(['annual'=>'Annual','sick'=>'Sick','maternity'=>'Maternity','paternity'=>'Paternity'] as $type => $label)
                    @php $bal = $leaveBalances->get($type); @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="text-right">
                            @if($bal)
                                <span class="font-weight-bold {{ $bal->available > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $bal->available }}
                                </span>
                                <small class="text-muted">/ {{ $bal->entitled }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </table>
                @if($pendingLeave > 0)
                <div class="px-3 py-2 border-top">
                    <small class="text-warning">
                        <i class="bi bi-hourglass-split mr-1"></i>
                        {{ $pendingLeave }} pending leave request(s)
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        {{-- Employment details --}}
        @if($ed)
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-briefcase mr-1"></i>Employment Details</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td class="font-weight-bold" style="width:35%">Department</td><td>{{ $ed->department?->name ?? '—' }}</td></tr>
                    <tr><td class="font-weight-bold">Position</td><td>{{ $ed->position?->name ?? '—' }}</td></tr>
                    <tr><td class="font-weight-bold">Employment Type</td><td>{{ $ed->employmentTypeLabel() }}</td></tr>
                    <tr><td class="font-weight-bold">Hire Date</td><td>{{ $ed->hire_date?->format('d M Y') ?? '—' }}</td></tr>
                    @if($ed->contract_end_date)
                    <tr>
                        <td class="font-weight-bold">Contract End</td>
                        <td class="{{ $ed->isContractExpired() ? 'text-danger' : '' }}">
                            {{ $ed->contract_end_date->format('d M Y') }}
                            @if($ed->isContractExpired()) <span class="badge badge-danger">Expired</span> @endif
                        </td>
                    </tr>
                    @endif
                    @if($ed->bank_account_no)
                    <tr><td class="font-weight-bold">Bank Account</td>
                        <td>{{ $ed->bank_name ? $ed->bank_name.' — ' : '' }}{{ $ed->bank_account_no }}</td></tr>
                    @endif
                    @if($employee->tin_number)
                    <tr><td class="font-weight-bold">TIN Number</td><td>{{ $employee->tin_number }}</td></tr>
                    @endif
                    @if($employee->pension_number)
                    <tr><td class="font-weight-bold">Pension No.</td><td>{{ $employee->pension_number }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
        @endif

        {{-- Qualifications --}}
        @if($employee->qualifications->count())
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-mortarboard mr-1"></i>Qualifications</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Degree</th><th>Field</th><th>Institution</th><th>Year</th></tr>
                    </thead>
                    <tbody>
                        @foreach($employee->qualifications as $q)
                        <tr>
                            <td>{{ $q->degree }}</td>
                            <td>{{ $q->field_of_study ?? '—' }}</td>
                            <td>{{ $q->institution ?? '—' }}</td>
                            <td>{{ $q->graduation_year ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Emergency contacts --}}
        @if($employee->emergencyContacts->count())
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-telephone-plus mr-1"></i>Emergency Contacts</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Name</th><th>Relationship</th><th>Phone</th></tr>
                    </thead>
                    <tbody>
                        @foreach($employee->emergencyContacts as $ec)
                        <tr>
                            <td>{{ $ec->name }} @if($ec->is_primary)<span class="badge badge-success ml-1">Primary</span>@endif</td>
                            <td>{{ $ec->relationship ?? '—' }}</td>
                            <td>{{ $ec->phone }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Recent attendance --}}
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-calendar3 mr-1"></i>Recent Attendance</h6></div>
            <div class="card-body p-0">
                @if($recentAttendance->count())
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Date</th><th>Status</th><th>Sign In</th><th>Sign Off</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentAttendance as $a)
                        <tr>
                            <td>{{ $a->date }}</td>
                            <td>
                                @php $cls = ['present'=>'success','late'=>'warning','absent'=>'danger','leave'=>'info'][$a->status] ?? 'secondary'; @endphp
                                <span class="badge badge-{{ $cls }}">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td>{{ $a->sign_in_time ?? '—' }}</td>
                            <td>{{ $a->sign_off_time ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted p-3 mb-0">No attendance records yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
