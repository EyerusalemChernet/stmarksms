@extends('layouts.master')
@section('page_title', 'Create Employee')
@section('content')

@php
    // Support prefill from recruitment "Convert to Employee"
    $prefill = session('prefill', []);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-person-plus mr-2"></i>Create New Employee
        @if(!empty($prefill['_from_application']))
            <span class="badge badge-success ml-2">
                <i class="bi bi-person-check mr-1"></i>Pre-filled from Application #{{ $prefill['_from_application'] }}
            </span>
        @endif
    </h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to Employees
    </a>
</div>

<form action="{{ route('hr.employees.store') }}" method="POST">
    @csrf

    <div class="row">

        {{-- ── Column 1: Personal Information ─────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-person mr-1"></i>Personal Information</h6>
                </div>
                <div class="card-body">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control"
                                   value="{{ old('first_name', $prefill['first_name'] ?? '') }}" required autofocus>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control"
                                   value="{{ old('last_name', $prefill['last_name'] ?? '') }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">— Select —</option>
                                <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                   value="{{ old('date_of_birth') }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone', $prefill['phone'] ?? '') }}" placeholder="09XXXXXXXX">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $prefill['email'] ?? '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Address</label>
                        <input type="text" name="address" class="form-control"
                               value="{{ old('address', $prefill['address'] ?? '') }}">
                    </div>

                </div>
            </div>

            {{-- Identity & Compliance --}}
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-card-text mr-1"></i>Identity & Compliance</h6>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">National ID</label>
                            <input type="text" name="national_id" class="form-control"
                                   value="{{ old('national_id') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">TIN Number</label>
                            <input type="text" name="tin_number" class="form-control"
                                   value="{{ old('tin_number') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Pension No.</label>
                            <input type="text" name="pension_number" class="form-control"
                                   value="{{ old('pension_number') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- HR Notes --}}
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-sticky mr-1"></i>HR Notes
                        <small class="text-muted">(internal only)</small>
                    </h6>
                </div>
                <div class="card-body">
                    <textarea name="hr_notes" class="form-control" rows="3"
                              placeholder="Internal HR notes — not visible to the employee">{{ old('hr_notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Column 2: Employment Details ────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-briefcase mr-1"></i>Employment Details</h6>
                </div>
                <div class="card-body">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Department</label>
                            <select name="department_id" id="dept-select" class="form-control">
                                <option value="">— None —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}"
                                        {{ old('department_id') == $d->id ? 'selected' : '' }}>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Position</label>
                            <select name="position_id" id="pos-select" class="form-control">
                                <option value="">— None —</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}"
                                        {{ old('position_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                        @if($p->department) ({{ $p->department->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Employment Type</label>
                            <select name="employment_type" class="form-control">
                                @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $v=>$l)
                                    <option value="{{ $v }}"
                                        {{ old('employment_type', 'full_time') === $v ? 'selected' : '' }}>
                                        {{ $l }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Hire Date</label>
                            <input type="date" name="hire_date" class="form-control"
                                   value="{{ old('hire_date', now()->toDateString()) }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Currency</label>
                            <select name="currency" class="form-control">
                                @foreach(['ETB','USD','EUR'] as $cur)
                                    <option value="{{ $cur }}"
                                        {{ old('currency', 'ETB') === $cur ? 'selected' : '' }}>
                                        {{ $cur }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-8">
                            <label class="font-weight-bold">Monthly Salary</label>
                            <input type="number" name="salary" step="0.01" min="0"
                                   class="form-control" value="{{ old('salary', 0) }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control"
                                   value="{{ old('bank_name') }}"
                                   placeholder="e.g. Commercial Bank of Ethiopia">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Bank Account No.</label>
                            <input type="text" name="bank_account_no" class="form-control"
                                   value="{{ old('bank_account_no') }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Reporting Manager</label>
                            <select name="reporting_manager_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($managers as $m)
                                    <option value="{{ $m->id }}"
                                        {{ old('reporting_manager_id') == $m->id ? 'selected' : '' }}>
                                        {{ $m->full_name }} ({{ $m->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Shift</label>
                            <select name="shift_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($shifts as $sh)
                                    <option value="{{ $sh->id }}"
                                        {{ old('shift_id') == $sh->id ? 'selected' : '' }}>
                                        {{ $sh->name }} ({{ $sh->start_time }}–{{ $sh->end_time }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>{{-- /row --}}

    <div class="d-flex justify-content-between align-items-center mb-4">
        <button type="submit" class="btn btn-success btn-lg px-5">
            <i class="bi bi-person-check mr-1"></i>Create Employee
        </button>
        <a href="{{ route('hr.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

@endsection

@section('scripts')
<script>
// Dynamic position filtering by department
$('#dept-select').on('change', function() {
    var deptId = $(this).val();
    var posSelect = $('#pos-select');
    var currentVal = posSelect.val();

    posSelect.html('<option value="">— Loading... —</option>').prop('disabled', true);

    if (!deptId) {
        // No department selected — load all positions
        $.get('{{ route("hr.positions.by_department", 0) }}'.replace('/0', '/' + 0))
         .always(function() {
            // Reload all positions
            posSelect.html('<option value="">— None —</option>');
            @foreach($positions as $p)
            posSelect.append('<option value="{{ $p->id }}">{{ $p->name }}</option>');
            @endforeach
            posSelect.prop('disabled', false);
         });
        return;
    }

    $.get('/hr/positions/by-department/' + deptId)
     .done(function(data) {
        posSelect.html('<option value="">— None —</option>');
        $.each(data, function(i, pos) {
            posSelect.append('<option value="' + pos.id + '">' + pos.name + '</option>');
        });
        posSelect.val(currentVal).prop('disabled', false);
     })
     .fail(function() {
        posSelect.html('<option value="">— None —</option>').prop('disabled', false);
     });
});
</script>
@endsection
