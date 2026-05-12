@extends('layouts.master')
@section('page_title', 'Employee — ' . $employee->full_name)
@section('content')

@php $ed = $employee->employmentDetails; @endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-person-badge mr-2"></i>{{ $employee->full_name }}
        <span class="badge badge-light border text-monospace ml-1">{{ $employee->employee_code }}</span>
    </h5>
    <div>
        <a href="{{ route('hr.profile.edit', $employee->id) }}" class="btn btn-sm btn-primary mr-1">
            <i class="bi bi-pencil mr-1"></i>Edit Profile
        </a>
        <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>Back
        </a>
    </div>
</div>

{{-- Status banner for non-active employees --}}
@if($employee->status !== 'active')
<div class="alert alert-{{ $employee->statusBadgeClass() }} border-0 mb-3 py-2">
    <i class="bi bi-exclamation-circle mr-1"></i>
    <strong>{{ ucwords(str_replace('_',' ',$employee->status)) }}</strong>
    @if($employee->status === 'terminated')
        — Terminated on {{ $employee->termination_date?->format('d M Y') }}: {{ $employee->termination_reason }}
    @endif
</div>
@endif

<div class="row">

    {{-- ── LEFT: Identity card ─────────────────────────────────────────────── --}}
    <div class="col-md-4">

        <div class="card text-center p-3 mb-3">
            <img src="{{ $employee->photo_url }}" width="100" height="100"
                 class="rounded-circle mx-auto mb-2" style="object-fit:cover;border:3px solid #e0e0e0;">
            <h5 class="mb-0">{{ $employee->full_name }}</h5>
            @if($employee->user)
                <p class="text-muted mb-1 small">{{ ucwords(str_replace('_',' ',$employee->user->user_type)) }}</p>
            @endif
            @if($ed && $ed->department)
                <span class="badge badge-info">{{ $ed->department->name }}</span>
            @endif
            @if($ed && $ed->position)
                <span class="badge badge-primary ml-1">{{ $ed->position->name }}</span>
            @endif
            <hr>
            <div class="text-left small">
                <p class="mb-1"><i class="bi bi-envelope mr-1 text-muted"></i> {{ $employee->email ?? '—' }}</p>
                <p class="mb-1"><i class="bi bi-phone mr-1 text-muted"></i> {{ $employee->phone ?? '—' }}</p>
                <p class="mb-1"><i class="bi bi-geo-alt mr-1 text-muted"></i> {{ $employee->address ?? '—' }}</p>
                @if($ed && $ed->hire_date)
                    <p class="mb-1"><i class="bi bi-calendar-check mr-1 text-muted"></i>
                        Hired: {{ $ed->hire_date->format('d M Y') }}</p>
                @endif
                @if($ed && $ed->bank_account_no)
                    <p class="mb-1"><i class="bi bi-bank mr-1 text-muted"></i>
                        {{ $ed->bank_name ? $ed->bank_name.' — ' : '' }}{{ $ed->bank_account_no }}</p>
                @endif
                @if($ed && $ed->is_remote)
                    <span class="badge badge-warning">Remote</span>
                @endif
                @if($ed && $ed->contract_end_date)
                    <p class="mb-1 {{ $ed->isContractExpired() ? 'text-danger' : '' }}">
                        <i class="bi bi-hourglass-split mr-1"></i>
                        Contract ends: {{ $ed->contract_end_date->format('d M Y') }}
                        @if($ed->isContractExpired()) <span class="badge badge-danger">Expired</span> @endif
                    </p>
                @endif
            </div>

            {{-- Quick status change --}}
            @if($employee->status !== 'terminated')
            <form action="{{ route('hr.status.change', $employee->id) }}" method="POST" class="mt-2">
                @csrf
                <div class="input-group input-group-sm">
                    <select name="status" class="form-control form-control-sm">
                        @foreach(['active'=>'Active','on_leave'=>'On Leave','suspended'=>'Suspended'] as $v=>$l)
                            <option value="{{ $v }}" {{ $employee->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Set</button>
                    </div>
                </div>
            </form>
            @endif
        </div>

        {{-- Attendance summary --}}
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Attendance (Last 30 Days)</h6>
                <a href="{{ route('hr.attendance.report', $employee->id) }}"
                   class="btn btn-xs btn-outline-primary">Full Report</a>
            </div>
            <div class="card-body text-center">
                <div class="row">
                    <div class="col-4"><h4 class="text-success mb-0">{{ $presentCount }}</h4><small>Present</small></div>
                    <div class="col-4"><h4 class="text-danger mb-0">{{ $totalCount - $presentCount }}</h4><small>Absent</small></div>
                    <div class="col-4"><h4 class="text-primary mb-0">{{ $attPct }}%</h4><small>Rate</small></div>
                </div>
                <div class="progress mt-2" style="height:6px;">
                    <div class="progress-bar {{ $attPct >= 75 ? 'bg-success' : 'bg-danger' }}"
                         style="width:{{ $attPct }}%"></div>
                </div>
            </div>
        </div>

        {{-- Emergency contacts --}}
        @if($employee->emergencyContacts->count())
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-telephone-plus mr-1"></i>Emergency Contacts</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($employee->emergencyContacts as $ec)
                        <tr>
                            <td>
                                {{ $ec->name }}
                                @if($ec->is_primary) <span class="badge badge-success ml-1">Primary</span> @endif
                                <br><small class="text-muted">{{ $ec->relationship ?? '' }}</small>
                            </td>
                            <td class="text-right"><small>{{ $ec->phone }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Recent payrolls --}}
        @if($payrolls->count())
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-cash-stack mr-1"></i>Recent Payrolls</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Month</th><th>Net Pay</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($payrolls as $pr)
                        <tr>
                            <td>{{ $pr->month }}</td>
                            <td class="text-success font-weight-bold">
                                {{ $pr->currency }} {{ number_format($pr->net_pay, 2) }}
                            </td>
                            <td>
                                @php $cls = ['pending'=>'warning','approved'=>'info','paid'=>'success'][$pr->status] ?? 'secondary'; @endphp
                                <span class="badge badge-{{ $cls }}">{{ ucfirst($pr->status) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>{{-- /col-md-4 --}}

    {{-- ── RIGHT: Details ──────────────────────────────────────────────────── --}}
    <div class="col-md-8">

        {{-- Salary --}}
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-cash-stack mr-1"></i>Salary</h6>
                @if($employee->currentSalary)
                    <span class="text-success font-weight-bold">
                        Current: {{ $employee->currentSalary->currency }}
                        {{ number_format($employee->currentSalary->amount, 2) }}
                    </span>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('hr.assign_salary', $employee->id) }}" method="POST" class="form-inline">
                    @csrf
                    <div class="form-group mr-2">
                        <label class="mr-1 small font-weight-bold">Currency</label>
                        <select name="currency" class="form-control form-control-sm">
                            @foreach(['ETB','USD','EUR'] as $cur)
                                <option value="{{ $cur }}"
                                    {{ ($employee->currentSalary->currency ?? 'ETB') === $cur ? 'selected' : '' }}>
                                    {{ $cur }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <label class="mr-1 small font-weight-bold">Amount</label>
                        <input type="number" name="amount" step="0.01" min="0"
                               value="{{ $employee->currentSalary->amount ?? '' }}"
                               class="form-control form-control-sm" style="width:130px;" required>
                    </div>
                    <div class="form-group mr-2">
                        <label class="mr-1 small font-weight-bold">Effective</label>
                        <input type="date" name="start_date" value="{{ now()->toDateString() }}"
                               class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success">Update</button>
                </form>

                @if($employee->salaries->count() > 1)
                <div class="mt-3">
                    <p class="small font-weight-bold text-muted mb-1">Salary History</p>
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr><th>Currency</th><th>Amount</th><th>From</th><th>To</th></tr>
                        </thead>
                        <tbody>
                            @foreach($employee->salaries as $sal)
                            <tr class="{{ is_null($sal->end_date) ? 'table-success' : '' }}">
                                <td>{{ $sal->currency }}</td>
                                <td>{{ number_format($sal->amount, 2) }}</td>
                                <td>{{ $sal->start_date->format('d M Y') }}</td>
                                <td>{!! $sal->end_date ? $sal->end_date->format('d M Y') : '<span class="badge badge-success">Current</span>' !!}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Shift --}}
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-clock mr-1"></i>Shift</h6>
                @if($employee->currentShift && $employee->currentShift->shift)
                    <span class="badge badge-dark">
                        {{ $employee->currentShift->shift->name }}
                        ({{ $employee->currentShift->shift->start_time }} – {{ $employee->currentShift->shift->end_time }})
                    </span>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('hr.assign_shift', $employee->id) }}" method="POST" class="form-inline">
                    @csrf
                    <div class="form-group mr-2">
                        <label class="mr-1 small font-weight-bold">Shift</label>
                        <select name="shift_id" class="form-control form-control-sm" required>
                            <option value="">— Select —</option>
                            @foreach($shifts as $sh)
                                <option value="{{ $sh->id }}"
                                    {{ ($employee->currentShift->shift_id ?? null) == $sh->id ? 'selected' : '' }}>
                                    {{ $sh->name }} ({{ $sh->start_time }}–{{ $sh->end_time }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <label class="mr-1 small font-weight-bold">Effective</label>
                        <input type="date" name="start_date" value="{{ now()->toDateString() }}"
                               class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark">Assign</button>
                </form>
            </div>
        </div>

        {{-- Qualifications --}}
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-mortarboard mr-1"></i>Qualifications</h6></div>
            <div class="card-body">
                @if($employee->qualifications->count())
                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr><th>Degree</th><th>Field</th><th>Institution</th><th>Year</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($employee->qualifications as $q)
                        <tr>
                            <td>{{ $q->degree }}</td>
                            <td>{{ $q->field_of_study ?? '—' }}</td>
                            <td>{{ $q->institution ?? '—' }}</td>
                            <td>{{ $q->graduation_year ?? '—' }}</td>
                            <td>
                                <form action="{{ route('hr.qualification.delete', $employee->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="qualification_id" value="{{ $q->id }}">
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Remove this qualification?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                <form action="{{ route('hr.qualification.add', $employee->id) }}" method="POST" class="form-inline">
                    @csrf
                    <div class="form-group mr-2">
                        <input type="text" name="degree" class="form-control form-control-sm"
                               placeholder="Degree (e.g. BSc)" required style="width:130px;">
                    </div>
                    <div class="form-group mr-2">
                        <input type="text" name="field_of_study" class="form-control form-control-sm"
                               placeholder="Field of Study" style="width:140px;">
                    </div>
                    <div class="form-group mr-2">
                        <input type="text" name="institution" class="form-control form-control-sm"
                               placeholder="Institution" style="width:140px;">
                    </div>
                    <div class="form-group mr-2">
                        <input type="number" name="graduation_year" class="form-control form-control-sm"
                               placeholder="Year" min="1950" max="{{ date('Y') }}" style="width:80px;">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus mr-1"></i>Add
                    </button>
                </form>
            </div>
        </div>

        {{-- Subjects (if teacher) --}}
        @if($subjects->count())
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-book mr-1"></i>Assigned Subjects</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Subject</th><th>Class</th></tr></thead>
                    <tbody>
                        @foreach($subjects as $sub)
                        <tr>
                            <td>{{ $sub->name }}</td>
                            <td>{{ $sub->my_class->name ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Recent attendance --}}
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-calendar3 mr-1"></i>Recent Attendance</h6>
                <a href="{{ route('hr.attendance.report', $employee->id) }}"
                   class="btn btn-xs btn-outline-primary">Full Report</a>
            </div>
            <div class="card-body p-0">
                @if($attendance->count())
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Date</th><th>Status</th><th>Sign In</th><th>Sign Off</th><th>Remark</th></tr>
                    </thead>
                    <tbody>
                        @foreach($attendance as $a)
                        <tr>
                            <td>{{ $a->date }}</td>
                            <td>
                                @php $cls = ['present'=>'success','late'=>'warning','absent'=>'danger','leave'=>'info'][$a->status] ?? 'secondary'; @endphp
                                <span class="badge badge-{{ $cls }}">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td>{{ $a->sign_in_time  ?? '—' }}</td>
                            <td>{{ $a->sign_off_time ?? '—' }}</td>
                            <td>{{ $a->remark ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p class="text-muted p-3 mb-0">No attendance records yet.</p>
                @endif
            </div>
        </div>

    </div>{{-- /col-md-8 --}}
</div>
@endsection
