@extends('layouts.master')
@section('page_title', 'Positions')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-briefcase mr-2"></i>Positions</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to HR
    </a>
</div>

<div class="row">

    {{-- Add Position --}}
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Add Position</h6></div>
            <div class="card-body">
                <form id="pos-add-form">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Position Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="pos-name" class="form-control"
                               placeholder="e.g. Senior Teacher" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Department</label>
                        <select name="department_id" id="pos-dept" class="form-control">
                            <option value="">— All Departments (cross-dept) —</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Leave blank for positions that span all departments.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea name="description" id="pos-desc" class="form-control" rows="2"
                                  placeholder="Optional"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle mr-1"></i>Add Position
                    </button>
                </form>
            </div>
        </div>

        {{-- Edit Position --}}
        <div class="card d-none" id="pos-edit-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Edit Position</h6>
                <button type="button" class="btn btn-xs btn-outline-secondary" id="pos-edit-cancel">
                    <i class="bi bi-x"></i> Cancel
                </button>
            </div>
            <div class="card-body">
                <form id="pos-edit-form">
                    @csrf @method('PUT')
                    <input type="hidden" id="edit-pos-id">
                    <div class="form-group">
                        <label class="font-weight-bold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit-pos-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Department</label>
                        <select id="edit-pos-dept" class="form-control">
                            <option value="">— All Departments —</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea id="edit-pos-desc" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle mr-1"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Position List --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <h6 class="card-title mb-0">
                    All Positions
                    <span class="badge badge-secondary ml-1">{{ $positions->count() }}</span>
                    @if($search)<span class="badge badge-warning ml-1">Filtered: "{{ $search }}"</span>@endif
                </h6>
                <div class="d-flex" style="gap:6px;">
                    <a href="{{ route('hr.positions', array_merge(request()->query(), ['export'=>'pdf'])) }}"
                       class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
                    <a href="{{ route('hr.positions', array_merge(request()->query(), ['export'=>'csv'])) }}"
                       class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
                </div>
            </div>
            {{-- Search --}}
            <div class="card-body py-2 border-bottom">
                <form action="{{ route('hr.positions') }}" method="GET" class="form-inline mb-0" style="gap:6px;">
                    <input type="text" name="search" value="{{ $search }}"
                           class="form-control form-control-sm" style="min-width:180px;"
                           placeholder="Search positions…">
                    <select name="department_id" class="form-control form-control-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ $deptFilter == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
                    @if($search || $deptFilter)
                    <a href="{{ route('hr.positions') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
                    @endif
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th class="text-center">Employees</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($positions as $pos)
                        <tr>
                            <td class="font-weight-bold">{{ $pos->name }}</td>
                            <td>
                                @if($pos->department)
                                    <span class="badge badge-info">{{ $pos->department->name }}</span>
                                @else
                                    <span class="text-muted small">All Departments</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-primary">{{ $pos->employee_count }}</span>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-xs btn-primary pos-edit-btn"
                                        data-id="{{ $pos->id }}"
                                        data-name="{{ $pos->name }}"
                                        data-dept="{{ $pos->department_id }}"
                                        data-desc="{{ $pos->description }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('hr.positions.destroy', $pos->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete {{ $pos->name }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No positions yet. Add one on the left.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ── Add Position ──────────────────────────────────────────────────────────────
$('#pos-add-form').on('submit', function(e) {
    e.preventDefault();
    var btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true).text('Saving...');

    $.post('{{ route("hr.positions.store") }}', {
        _token:        '{{ csrf_token() }}',
        name:          $('#pos-name').val(),
        department_id: $('#pos-dept').val(),
        description:   $('#pos-desc').val()
    })
    .done(function(r) {
        if (r.ok) {
            $('#pos-name').val('');
            $('#pos-dept').val('');
            $('#pos-desc').val('');
            location.reload();
        } else {
            alert(r.msg || 'Error saving position.');
        }
    })
    .fail(function(xhr) {
        var err = xhr.responseJSON;
        if (err && err.errors) {
            alert(Object.values(err.errors).flat().join('\n'));
        } else {
            alert('An error occurred.');
        }
    })
    .always(function() {
        btn.prop('disabled', false).html('<i class="bi bi-plus-circle mr-1"></i>Add Position');
    });
});

// ── Edit Position ─────────────────────────────────────────────────────────────
$(document).on('click', '.pos-edit-btn', function() {
    $('#edit-pos-id').val($(this).data('id'));
    $('#edit-pos-name').val($(this).data('name'));
    $('#edit-pos-dept').val($(this).data('dept') || '');
    $('#edit-pos-desc').val($(this).data('desc') || '');
    $('#pos-edit-card').removeClass('d-none');
    $('html, body').animate({ scrollTop: $('#pos-edit-card').offset().top - 80 }, 300);
});

$('#pos-edit-cancel').on('click', function() {
    $('#pos-edit-card').addClass('d-none');
});

$('#pos-edit-form').on('submit', function(e) {
    e.preventDefault();
    var id  = $('#edit-pos-id').val();
    var btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true).text('Saving...');

    $.ajax({
        url:    '/hr/positions/' + id,
        method: 'PUT',
        data: {
            _token:        '{{ csrf_token() }}',
            name:          $('#edit-pos-name').val(),
            department_id: $('#edit-pos-dept').val(),
            description:   $('#edit-pos-desc').val()
        }
    })
    .done(function(r) {
        if (r.ok) { location.reload(); }
        else { alert(r.msg || 'Error updating position.'); }
    })
    .fail(function(xhr) {
        var err = xhr.responseJSON;
        if (err && err.errors) {
            alert(Object.values(err.errors).flat().join('\n'));
        } else {
            alert('An error occurred.');
        }
    })
    .always(function() {
        btn.prop('disabled', false).html('<i class="bi bi-check-circle mr-1"></i>Save Changes');
    });
});
</script>
@endsection
