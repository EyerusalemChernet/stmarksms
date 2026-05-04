@extends('layouts.master')
@section('page_title', 'Edit — ' . $employee->full_name)
@section('content')

@php $ed = $employee->employmentDetails; @endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-pencil-square mr-2"></i>Edit Employee Profile
        <span class="badge badge-light border text-monospace ml-1">{{ $employee->employee_code }}</span>
    </h5>
    <a href="{{ route('hr.show', $employee->id) }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to Profile
    </a>
</div>

<form action="{{ route('hr.profile.update', $employee->id) }}" method="POST">
    @csrf @method('PUT')

    <div class="row">

        {{-- ── Column 1 ─────────────────────────────────────────────────────── --}}
        <div class="col-md-6">

            {{-- Personal Information --}}
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-person mr-1"></i>Personal Information</h6>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control"
                                   value="{{ old('first_name', $employee->first_name) }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control"
                                   value="{{ old('last_name', $employee->last_name) }}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $employee->email) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone', $employee->phone) }}" placeholder="09XXXXXXXX">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Phone 2</label>
                            <input type="text" name="phone2" class="form-control"
                                   value="{{ old('phone2', $employee->phone2) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                   value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">— Select —</option>
                                <option value="male"   {{ old('gender', $employee->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Address</label>
                            <input type="text" name="address" class="form-control"
                                   value="{{ old('address', $employee->address) }}">
                        </div>
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
                                   value="{{ old('national_id', $employee->national_id) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">TIN Number</label>
                            <input type="text" name="tin_number" class="form-control"
                                   value="{{ old('tin_number', $employee->tin_number) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Pension No.</label>
                            <input type="text" name="pension_number" class="form-control"
                                   value="{{ old('pension_number', $employee->pension_number) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Emergency Contacts --}}
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-telephone-plus mr-1"></i>Emergency Contacts</h6>
                </div>
                <div class="card-body" id="emergency-contacts">
                    @php
                        $contacts = old('emergency', $employee->emergencyContacts->map(fn($c) => [
                            'name'         => $c->name,
                            'phone'        => $c->phone,
                            'relationship' => $c->relationship,
                            'is_primary'   => $c->is_primary,
                        ])->toArray());
                        if (empty($contacts)) $contacts = [['name'=>'','phone'=>'','relationship'=>'','is_primary'=>false]];
                    @endphp

                    @foreach($contacts as $i => $contact)
                    <div class="emergency-row border rounded p-2 mb-2">
                        <div class="form-row">
                            <div class="form-group col-md-5 mb-1">
                                <input type="text" name="emergency[{{ $i }}][name]"
                                       class="form-control form-control-sm"
                                       placeholder="Contact Name"
                                       value="{{ $contact['name'] ?? '' }}">
                            </div>
                            <div class="form-group col-md-4 mb-1">
                                <input type="text" name="emergency[{{ $i }}][phone]"
                                       class="form-control form-control-sm"
                                       placeholder="Phone"
                                       value="{{ $contact['phone'] ?? '' }}">
                            </div>
                            <div class="form-group col-md-3 mb-1">
                                <input type="text" name="emergency[{{ $i }}][relationship]"
                                       class="form-control form-control-sm"
                                       placeholder="Relation"
                                       value="{{ $contact['relationship'] ?? '' }}">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox"
                                       name="emergency[{{ $i }}][is_primary]" value="1"
                                       {{ !empty($contact['is_primary']) ? 'checked' : '' }}>
                                <label class="form-check-label small">Primary</label>
                            </div>
                            @if($i > 0)
                            <button type="button" class="btn btn-xs btn-outline-danger remove-contact">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="add-contact">
                        <i class="bi bi-plus mr-1"></i>Add Contact
                    </button>
                </div>
            </div>

        </div>{{-- /col-md-6 --}}

        {{-- ── Column 2 ─────────────────────────────────────────────────────── --}}
        <div class="col-md-6">

            {{-- Employment Details --}}
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0"><i class="bi bi-briefcase mr-1"></i>Employment Details</h6>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Department</label>
                            <select name="department_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}"
                                        {{ old('department_id', $ed->department_id ?? null) == $d->id ? 'selected' : '' }}>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Position</label>
                            <select name="position_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}"
                                        {{ old('position_id', $ed->position_id ?? null) == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Reporting Manager</label>
                            <select name="reporting_manager_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($managers as $m)
                                    <option value="{{ $m->id }}"
                                        {{ old('reporting_manager_id', $ed->reporting_manager_id ?? null) == $m->id ? 'selected' : '' }}>
                                        {{ $m->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Employment Type</label>
                            <select name="employment_type" class="form-control">
                                @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $v=>$l)
                                    <option value="{{ $v }}"
                                        {{ old('employment_type', $ed->employment_type ?? 'full_time') === $v ? 'selected' : '' }}>
                                        {{ $l }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Hire Date</label>
                            <input type="date" name="hire_date" class="form-control"
                                   value="{{ old('hire_date', $ed->hire_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Contract End Date</label>
                            <input type="date" name="contract_end_date" class="form-control"
                                   value="{{ old('contract_end_date', $ed->contract_end_date?->format('Y-m-d')) }}">
                            <small class="text-muted">Leave blank for permanent.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Currency</label>
                            <select name="currency" class="form-control">
                                @foreach(['ETB','USD','EUR'] as $cur)
                                    <option value="{{ $cur }}"
                                        {{ old('currency', $ed->currency ?? 'ETB') === $cur ? 'selected' : '' }}>
                                        {{ $cur }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Salary</label>
                            <input type="number" name="salary" step="0.01" min="0" class="form-control"
                                   value="{{ old('salary', $ed->salary ?? 0) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Remote?</label>
                            <select name="is_remote" class="form-control">
                                <option value="0" {{ !old('is_remote', $ed->is_remote ?? false) ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_remote', $ed->is_remote ?? false) ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control"
                                   value="{{ old('bank_name', $ed->bank_name) }}"
                                   placeholder="e.g. Commercial Bank of Ethiopia">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Bank Account No.</label>
                            <input type="text" name="bank_account_no" class="form-control"
                                   value="{{ old('bank_account_no', $ed->bank_account_no) }}">
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
                              placeholder="Internal HR notes — not visible to the employee">{{ old('hr_notes', $employee->hr_notes) }}</textarea>
                </div>
            </div>

        </div>{{-- /col-md-6 --}}
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-check-circle mr-1"></i>Save Changes
        </button>
        <a href="{{ route('hr.show', $employee->id) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

{{-- Danger Zone --}}
@if($employee->status !== 'terminated')
<div class="card border-danger mt-2">
    <div class="card-header bg-danger text-white">
        <h6 class="card-title mb-0"><i class="bi bi-exclamation-triangle mr-1"></i>Danger Zone — Terminate Employee</h6>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">This action is logged and cannot be undone without HR intervention.</p>
        <form action="{{ route('hr.terminate', $employee->id) }}" method="POST"
              onsubmit="return confirm('Terminate {{ $employee->full_name }}? This is logged.')">
            @csrf
            <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">Termination Date</label>
                    <input type="date" name="termination_date" class="form-control"
                           value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group col-md-7">
                    <label class="font-weight-bold">Reason</label>
                    <input type="text" name="termination_reason" class="form-control"
                           placeholder="e.g. Resignation, Contract ended, Misconduct" required>
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="bi bi-person-x mr-1"></i>Terminate
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@else
<div class="card border-success mt-2">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <span class="badge badge-dark mr-2">Terminated</span>
            <span class="text-muted">
                on {{ $employee->termination_date?->format('d M Y') }}
                — {{ $employee->termination_reason }}
            </span>
        </div>
        <form action="{{ route('hr.reactivate', $employee->id) }}" method="POST"
              onsubmit="return confirm('Reactivate {{ $employee->full_name }}?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-person-check mr-1"></i>Reactivate
            </button>
        </form>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
// Dynamic emergency contact rows
var contactCount = {{ count($contacts) }};

$('#add-contact').on('click', function(){
    var i = contactCount++;
    var html = `
    <div class="emergency-row border rounded p-2 mb-2">
        <div class="form-row">
            <div class="form-group col-md-5 mb-1">
                <input type="text" name="emergency[${i}][name]" class="form-control form-control-sm" placeholder="Contact Name">
            </div>
            <div class="form-group col-md-4 mb-1">
                <input type="text" name="emergency[${i}][phone]" class="form-control form-control-sm" placeholder="Phone">
            </div>
            <div class="form-group col-md-3 mb-1">
                <input type="text" name="emergency[${i}][relationship]" class="form-control form-control-sm" placeholder="Relation">
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="emergency[${i}][is_primary]" value="1">
                <label class="form-check-label small">Primary</label>
            </div>
            <button type="button" class="btn btn-xs btn-outline-danger remove-contact">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
    </div>`;
    $('#emergency-contacts').find('#add-contact').before(html);
});

$(document).on('click', '.remove-contact', function(){
    $(this).closest('.emergency-row').remove();
});
</script>
@endsection
