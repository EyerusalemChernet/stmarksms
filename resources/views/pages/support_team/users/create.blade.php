@extends('layouts.master')
@section('page_title', $employee ? 'Create User Account for ' . $employee->full_name : 'Create New User')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title">
            @if($employee)
                <i class="bi bi-person-badge mr-1"></i>
                Create User Account for {{ $employee->full_name }}
                <span class="badge badge-info ml-1" style="font-size:11px;">{{ $employee->employee_code }}</span>
            @else
                <i class="bi bi-person-plus mr-1"></i>
                Create New User Account
            @endif
        </h6>
        <a href="{{ $employee ? route('hr.employees.unlinked') : route('users.index') }}" class="btn btn-sm btn-secondary ml-auto">
            <i class="bi bi-arrow-left mr-1"></i>Back
        </a>
    </div>

    <div class="card-body">
        @if($employee)
        <div class="alert alert-info border-0 mb-4">
            <i class="bi bi-info-circle mr-2"></i>
            <strong>Prefilled from Employee Record:</strong> 
            Form fields have been automatically populated with data from the employee record. Review and adjust as needed.
        </div>
        @endif

        <form method="post" enctype="multipart/form-data" class="wizard-form steps-validation ajax-store" 
              action="{{ route('users.store') }}" data-fouc>
            @csrf

            <h6>Personal Data</h6>
            <fieldset>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="user_type">Select User: <span class="text-danger">*</span></label>
                            <select required data-placeholder="Select User Type" class="form-control select" name="user_type" id="user_type">
                                <option value="" disabled selected>— Select User Type —</option>
                                @foreach($user_types as $ut)
                                    <option value="{{ Qs::hash($ut->id) }}">{{ $ut->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Full Name: <span class="text-danger">*</span></label>
                            <input value="{{ old('name') ?? ($prefill['name'] ?? '') }}" required type="text" name="name" placeholder="Full Name" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address: <span class="text-danger">*</span></label>
                            <input value="{{ old('address') ?? ($prefill['address'] ?? '') }}" class="form-control" placeholder="Address" name="address" type="text" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Email address:</label>
                            <input value="{{ old('email') ?? ($prefill['email'] ?? '') }}" type="email" name="email" class="form-control" placeholder="your@email.com">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Username:</label>
                            <input value="{{ old('username') }}" type="text" name="username" class="form-control" placeholder="Username">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Phone <small class="text-muted">(09XXXXXXXX)</small>:</label>
                            <input value="{{ old('phone') ?? ($prefill['phone'] ?? '') }}" type="text" name="phone"
                                   class="form-control" placeholder="e.g. 0911434321"
                                   pattern="09[0-9]{8}" title="10 digits starting with 09">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Alternative Phone <small class="text-muted">(Optional)</small>:</label>
                            <input value="{{ old('phone2') ?? ($prefill['phone2'] ?? '') }}" type="text" name="phone2"
                                   class="form-control" placeholder="e.g. 0922434321"
                                   pattern="09[0-9]{8}" title="10 digits starting with 09">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date of Employment:</label>
                            <input autocomplete="off" name="emp_date" value="{{ old('emp_date') ?? date('Y-m-d') }}" type="text" class="form-control date-pick" placeholder="Select Date...">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input id="password" type="password" name="password"
                                   class="form-control"
                                   minlength="8"
                                   pattern="^(?=.*[A-Z])(?=.*\d).{8,}$"
                                   title="Min 8 characters, at least 1 uppercase letter and 1 number"
                                   autocomplete="new-password">
                            <small class="text-muted">Min 8 chars · 1 uppercase · 1 number · Leave blank for default (user type)</small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="gender">Gender: <span class="text-danger">*</span></label>
                            <select class="select form-control" id="gender" name="gender" required data-fouc data-placeholder="Choose..">
                                <option value=""></option>
                                <option {{ (old('gender') ?? $prefill['gender'] ?? '') == 'Male' ? 'selected' : '' }} value="Male">Male</option>
                                <option {{ (old('gender') ?? $prefill['gender'] ?? '') == 'Female' ? 'selected' : '' }} value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="nal_id">Nationality: <span class="text-danger">*</span></label>
                            <select data-placeholder="Choose..." required name="nal_id" id="nal_id" class="select-search form-control">
                                <option value=""></option>
                                @foreach($nationals->sortBy(fn($n) => $n->name === 'Ethiopian' ? 0 : 1) as $nal)
                                    <option {{ (old('nal_id') == $nal->id || $nal->name === 'Ethiopian') ? 'selected' : '' }}
                                            value="{{ $nal->id }}">{{ $nal->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{--Region--}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="state_id">Region: <span class="text-danger">*</span></label>
                            <select onchange="getLGA(this.value)" required data-placeholder="Choose.."
                                    class="select-search form-control" name="state_id" id="state_id">
                                <option value=""></option>
                                @foreach($states as $st)
                                    <option {{ (old('state_id') == $st->id ? 'selected' : '') }} value="{{ $st->id }}">{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{--Sub-city / Woreda--}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lga_id">Sub-city / Woreda: <span class="text-danger">*</span></label>
                            <select required data-placeholder="Select Region First"
                                    class="select-search form-control" name="lga_id" id="lga_id">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                    {{--BLOOD GROUP--}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="bg_id">Blood Group:</label>
                            <select class="select form-control" id="bg_id" name="bg_id" data-fouc data-placeholder="Choose..">
                                <option value=""></option>
                                @foreach($blood_groups as $bg)
                                    <option {{ (old('bg_id') == $bg->id ? 'selected' : '') }} value="{{ $bg->id }}">{{ $bg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @include('pages.support_team.users.partials.teacher_department_fields')

                <div class="row">
                    {{--PASSPORT--}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="d-block">Upload Passport Photo:</label>
                            <input value="{{ old('photo') }}" accept="image/*" type="file" name="photo" class="form-input-styled" data-fouc>
                            <span class="form-text text-muted">Accepted Images: jpeg, png. Max file size 2Mb</span>
                        </div>
                    </div>
                </div>

            </fieldset>

            @if($employee)
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            @endif
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(function () {
    var teacherTypeHash = @json($teacher_type_hash ?? '');

    function toggleTeacherDepartment() {
        var isTeacher = $('#user_type').val() === teacherTypeHash;
        $('#teacher-department-section').toggle(isTeacher);
        $('#department_id').prop('required', isTeacher);
        if (!isTeacher) {
            $('#department_id').val('').trigger('change');
        }
    }

    $('#user_type').on('change', toggleTeacherDepartment);
    toggleTeacherDepartment();

    // Hide the "Previous" button on the first step of the wizard
    $(document).on('stepChanged', function (e, currentIndex) {
        if (currentIndex === 0) {
            $('[class*="steps"] .actions a[href*="previous"]').closest('li').hide();
        } else {
            $('[class*="steps"] .actions a[href*="previous"]').closest('li').show();
        }
    });

    // Also hide on initial load
    setTimeout(function () {
        $('[class*="steps"] .actions a[href*="previous"]').closest('li').hide();
    }, 100);

    // Password strength live feedback
    $('#password').on('input', function () {
        var val = $(this).val();
        var $hint = $(this).siblings('small');
        var hasUpper  = /[A-Z]/.test(val);
        var hasNumber = /\d/.test(val);
        var hasLength = val.length >= 8;

        if (hasLength && hasUpper && hasNumber) {
            $hint.removeClass('text-muted text-danger').addClass('text-success')
                 .text('Strong password ✓');
        } else if (val.length === 0) {
            $hint.removeClass('text-muted text-success text-danger').addClass('text-muted')
                 .text('Min 8 chars · 1 uppercase · 1 number · Leave blank for default (user type)');
        } else {
            var missing = [];
            if (!hasLength) missing.push('8+ chars');
            if (!hasUpper)  missing.push('1 uppercase');
            if (!hasNumber) missing.push('1 number');
            $hint.removeClass('text-muted text-success').addClass('text-danger')
                 .text('Needs: ' + missing.join(', '));
        }
    });
});
</script>
@endsection
