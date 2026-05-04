@extends('layouts.master')
@section('page_title', 'Manage Users')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">Manage Users</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#new-user" class="nav-link active" data-toggle="tab">Create New User</a></li>
                <li class="nav-item"><a href="#bulk-user" class="nav-link" data-toggle="tab"><i class="bi bi-people-fill mr-1"></i>Bulk Import</a></li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Manage Users</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach($user_types as $ut)
                            <a href="#ut-{{ Qs::hash($ut->id) }}" class="dropdown-item" data-toggle="tab">{{ $ut->name }}s</a>
                        @endforeach
                    </div>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="new-user">
                    <form method="post" enctype="multipart/form-data" class="wizard-form steps-validation ajax-store" action="{{ route('users.store') }}" data-fouc>
                        @csrf
                    <h6>Personal Data</h6>
                        <fieldset>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="user_type"> Select User: <span class="text-danger">*</span></label>
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
                                        <input value="{{ old('name') }}" required type="text" name="name" placeholder="Full Name" class="form-control">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Address: <span class="text-danger">*</span></label>
                                        <input value="{{ old('address') }}" class="form-control" placeholder="Address" name="address" type="text" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Email address: </label>
                                        <input value="{{ old('email') }}" type="email" name="email" class="form-control" placeholder="your@email.com">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Username: </label>
                                        <input value="{{ old('username') }}" type="text" name="username" class="form-control" placeholder="Username">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Phone <small class="text-muted">(09XXXXXXXX)</small>:</label>
                                        <input value="{{ old('phone') }}" type="text" name="phone"
                                               class="form-control" placeholder="e.g. 0911434321"
                                               pattern="09[0-9]{8}" title="10 digits starting with 09">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Alternative Phone <small class="text-muted">(Optional)</small>:</label>
                                        <input value="{{ old('phone2') }}" type="text" name="phone2"
                                               class="form-control" placeholder="e.g. 0922434321"
                                               pattern="09[0-9]{8}" title="10 digits starting with 09">
                                    </div>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date of Employment:</label>
                                        <input autocomplete="off" name="emp_date" value="{{ old('emp_date') }}" type="text" class="form-control date-pick" placeholder="Select Date...">

                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="password">Password: </label>
                                        <input id="password" type="password" name="password"
                                               class="form-control"
                                               minlength="8"
                                               pattern="^(?=.*[A-Z])(?=.*\d).{8,}$"
                                               title="Min 8 characters, at least 1 uppercase letter and 1 number"
                                               autocomplete="new-password">
                                        <small class="text-muted">Min 8 chars · 1 uppercase · 1 number</small>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="gender">Gender: <span class="text-danger">*</span></label>
                                        <select class="select form-control" id="gender" name="gender" required data-fouc data-placeholder="Choose..">
                                            <option value=""></option>
                                            <option {{ (old('gender') == 'Male') ? 'selected' : '' }} value="Male">Male</option>
                                            <option {{ (old('gender') == 'Female') ? 'selected' : '' }} value="Female">Female</option>
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
                                        <label for="bg_id">Blood Group: </label>
                                        <select class="select form-control" id="bg_id" name="bg_id" data-fouc data-placeholder="Choose..">
                                            <option value=""></option>
                                            @foreach($blood_groups as $bg)
                                                <option {{ (old('bg_id') == $bg->id ? 'selected' : '') }} value="{{ $bg->id }}">{{ $bg->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>

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



                    </form>
                </div>

                {{-- ── BULK IMPORT TAB ─────────────────────────────────── --}}
                <div class="tab-pane fade" id="bulk-user">
                    <div class="pt-3">

                        <div class="alert alert-info border-0 mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill mr-3 mt-1" style="font-size:18px;"></i>
                                <div>
                                    <strong>Bulk User Import via CSV</strong><br>
                                    Upload a CSV to create multiple users at once. Supported types: <code>teacher</code>, <code>parent</code>, <code>hr_manager</code>, <code>admin</code>.
                                    Default password is the user type name unless specified.
                                    <a href="{{ route('users.bulk.template') }}" class="ml-2 font-weight-bold">
                                        <i class="bi bi-download mr-1"></i>Download CSV Template
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Column reference --}}
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered" style="font-size:12px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Column</th><th>user_type</th><th>name</th><th>email</th>
                                        <th>username</th><th>phone</th><th>gender</th>
                                        <th>address</th><th>emp_date</th><th>password</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-weight-bold">Example</td>
                                        <td>teacher</td><td>Abebe Kebede</td><td>abebe@email.com</td>
                                        <td>abebe.kebede</td><td>0911234567</td><td>Male</td>
                                        <td>Addis Ababa</td><td>{{ date('Y-m-d') }}</td><td>Teacher@123</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <form id="bulk-user-form" method="post" enctype="multipart/form-data" action="{{ route('users.bulk.import') }}">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">Select CSV File <span class="text-danger">*</span></label>
                                        <input type="file" name="csv_file" id="bulk-user-csv" accept=".csv,text/csv" class="form-control" required>
                                        <small class="text-muted">Max 5MB. UTF-8 encoded CSV only.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <button type="button" id="bulk-user-preview-btn" class="btn btn-info btn-block">
                                            <i class="bi bi-eye mr-1"></i>Preview CSV
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <button type="submit" id="bulk-user-submit-btn" class="btn btn-success btn-block" disabled>
                                            <i class="bi bi-cloud-upload mr-1"></i>Import Users
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="bulk-user-preview-area" style="display:none;" class="mb-3">
                                <h6 class="font-weight-semibold mb-2">
                                    <i class="bi bi-table mr-1"></i>Preview
                                    <span id="bulk-user-row-count" class="badge badge-primary ml-1"></span>
                                </h6>
                                <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                                    <table class="table table-sm table-bordered table-hover">
                                        <thead class="thead-dark" id="bulk-user-preview-head"></thead>
                                        <tbody id="bulk-user-preview-body"></tbody>
                                    </table>
                                </div>
                                <div id="bulk-user-validation-errors" class="mt-2"></div>
                            </div>
                        </form>

                        <div id="bulk-user-result" class="mt-3" style="display:none;"></div>
                    </div>
                </div>
                {{-- ── END BULK IMPORT TAB ──────────────────────────────── --}}

                @foreach($user_types as $ut)
                    <div class="tab-pane fade" id="ut-{{Qs::hash($ut->id)}}">                         <table class="table datatable-button-html5-columns">
                            <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($users->where('user_type', $ut->title) as $u)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><img class="rounded-circle" style="height: 40px; width: 40px;" src="{{ $u->photo }}" alt="photo"></td>
                                    <td>{{ $u->name }}</td>
                                    <td>{{ $u->username }}</td>
                                    <td>{{ $u->phone }}</td>
                                    <td>{{ $u->email }}</td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-menu9"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-left">
                                                    {{--View Profile--}}
                                                    <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Profile</a>
                                                    {{--Edit--}}
                                                    <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                @if(Qs::userIsSuperAdmin())

                                                        <a href="{{ route('users.reset_pass', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-lock"></i> Reset password</a>
                                                        {{--Delete--}}
                                                        <a id="{{ Qs::hash($u->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                        <form method="post" id="item-delete-{{ Qs::hash($u->id) }}" action="{{ route('users.destroy', Qs::hash($u->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                @endif

                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

    {{--Student List Ends--}}

@endsection

@section('scripts')
<script>
$(function () {
    // Hide the "Previous" button on the first step of the wizard
    // jQuery Steps adds .actions ul li:first-child for the Prev button
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
        } else {
            var missing = [];
            if (!hasLength) missing.push('8+ chars');
            if (!hasUpper)  missing.push('1 uppercase');
            if (!hasNumber) missing.push('1 number');
            $hint.removeClass('text-muted text-success').addClass('text-danger')
                 .text('Needs: ' + missing.join(', '));
        }
    });

    // ── Bulk User Import ──────────────────────────────────────────────────────
    var validUserTypes = ['teacher','parent','hr_manager','admin','super_admin','accountant'];

    $('#bulk-user-csv').on('change', function () {
        $('#bulk-user-preview-area').hide();
        $('#bulk-user-submit-btn').prop('disabled', true);
        $('#bulk-user-preview-head, #bulk-user-preview-body, #bulk-user-validation-errors').empty();
    });

    $('#bulk-user-preview-btn').on('click', function () {
        var file = $('#bulk-user-csv')[0].files[0];
        if (!file) { flash({ msg: 'Please select a CSV file first.', type: 'warning' }); return; }
        var reader = new FileReader();
        reader.onload = function (e) {
            var lines = e.target.result.split(/\r?\n/).filter(function (l) { return l.trim(); });
            if (lines.length < 2) { flash({ msg: 'CSV must have a header row and at least one data row.', type: 'warning' }); return; }
            var headers = lines[0].split(',').map(function (h) { return h.trim(); });
            var $head = $('<tr>');
            headers.forEach(function (h) { $head.append($('<th>').text(h)); });
            $head.append('<th>Status</th>');
            $('#bulk-user-preview-head').html($head);
            var $body = $('#bulk-user-preview-body').empty();
            var errors = [], validRows = 0;
            for (var i = 1; i < Math.min(lines.length, 51); i++) {
                var cols = lines[i].split(',').map(function (c) { return c.trim(); });
                var rowErrors = [];
                var typeIdx = headers.indexOf('user_type'), nameIdx = headers.indexOf('name'), genderIdx = headers.indexOf('gender');
                if (typeIdx >= 0 && !validUserTypes.includes((cols[typeIdx] || '').toLowerCase())) rowErrors.push('Invalid user_type');
                if (nameIdx >= 0 && (!cols[nameIdx] || cols[nameIdx].length < 2)) rowErrors.push('Name too short');
                if (genderIdx >= 0 && cols[genderIdx] && !['Male', 'Female'].includes(cols[genderIdx])) rowErrors.push('Gender must be Male/Female');
                var statusCell = rowErrors.length
                    ? '<td><span class="badge badge-danger">' + rowErrors.join(', ') + '</span></td>'
                    : '<td><span class="badge badge-success">OK</span></td>';
                if (rowErrors.length) errors.push('Row ' + i + ': ' + rowErrors.join(', '));
                else validRows++;
                var $tr = $('<tr>');
                cols.forEach(function (c) { $tr.append($('<td>').text(c)); });
                $tr.append(statusCell);
                $body.append($tr);
            }
            if (lines.length > 51) $body.append('<tr><td colspan="' + (headers.length + 1) + '" class="text-center text-muted">... and ' + (lines.length - 51) + ' more rows</td></tr>');
            $('#bulk-user-row-count').text((lines.length - 1) + ' rows');
            $('#bulk-user-preview-area').show();
            if (errors.length) {
                $('#bulk-user-validation-errors').html('<div class="alert alert-warning border-0"><strong>' + errors.length + ' row(s) have issues:</strong><ul class="mb-0 mt-1">' + errors.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul></div>');
            }
            $('#bulk-user-submit-btn').prop('disabled', validRows === 0);
        };
        reader.readAsText(file);
    });

    $('#bulk-user-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#bulk-user-submit-btn').prop('disabled', true).html('<i class="bi bi-hourglass-split mr-1"></i>Importing...');
        var fd = new FormData(this);
        $.ajax({ url: $(this).attr('action'), type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
        .done(function (r) {
            var cls = r.ok ? 'success' : 'danger';
            var html = '<div class="alert alert-' + cls + ' border-0"><strong>' + (r.ok ? 'Import Complete' : 'Import Failed') + '</strong><br>' + r.msg + '</div>';
            if (r.errors && r.errors.length) html += '<ul class="list-group mt-2">' + r.errors.map(function (e) { return '<li class="list-group-item list-group-item-danger py-1 small">' + e + '</li>'; }).join('') + '</ul>';
            $('#bulk-user-result').html(html).show();
            $btn.prop('disabled', false).html('<i class="bi bi-cloud-upload mr-1"></i>Import Users');
            if (r.ok) setTimeout(function () { location.reload(); }, 2000);
        })
        .fail(function (xhr) {
            $('#bulk-user-result').html('<div class="alert alert-danger border-0">Server error: ' + xhr.status + ' ' + xhr.statusText + '</div>').show();
            $btn.prop('disabled', false).html('<i class="bi bi-cloud-upload mr-1"></i>Import Users');
        });
    });
});
</script>
@endsection
