@extends('layouts.master')
@section('page_title', 'Departments')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-building mr-2"></i>Departments</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to HR
    </a>
</div>

<div class="row">

    {{-- Add Department --}}
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Add Department</h6></div>
            <div class="card-body">
                <form id="dept-add-form">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="dept-name" class="form-control"
                               placeholder="e.g. Academic Affairs" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea name="description" id="dept-desc" class="form-control" rows="2"
                                  placeholder="Optional description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle mr-1"></i>Add Department
                    </button>
                </form>
            </div>
        </div>

        {{-- Edit Department (shown when edit button clicked) --}}
        <div class="card d-none" id="dept-edit-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Edit Department</h6>
                <button type="button" class="btn btn-xs btn-outline-secondary" id="dept-edit-cancel">
                    <i class="bi bi-x"></i> Cancel
                </button>
            </div>
            <div class="card-body">
                <form id="dept-edit-form">
                    @csrf @method('PUT')
                    <input type="hidden" id="edit-dept-id">
                    <div class="form-group">
                        <label class="font-weight-bold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit-dept-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea id="edit-dept-desc" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle mr-1"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Department List --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <h6 class="card-title mb-0">
                    All Departments
                    <span class="badge badge-secondary ml-1">{{ $departments->count() }}</span>
                    @if($search)<span class="badge badge-warning ml-1">Filtered: "{{ $search }}"</span>@endif
                </h6>
                <div class="d-flex" style="gap:6px;">
                    <a href="{{ route('hr.departments', array_merge(request()->query(), ['export'=>'pdf'])) }}"
                       class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
                    <a href="{{ route('hr.departments', array_merge(request()->query(), ['export'=>'csv'])) }}"
                       class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
                </div>
            </div>
            {{-- Search --}}
            <div class="card-body py-2 border-bottom">
                <form action="{{ route('hr.departments') }}" method="GET" class="form-inline mb-0" style="gap:6px;">
                    <input type="text" name="search" value="{{ $search }}"
                           class="form-control form-control-sm" style="min-width:200px;"
                           placeholder="Search departments…">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
                    @if($search)
                    <a href="{{ route('hr.departments') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
                    @endif
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="dept-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Positions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                        <tr id="dept-row-{{ $dept->id }}">
                            <td class="font-weight-bold">{{ $dept->name }}</td>
                            <td class="text-muted small">{{ $dept->description ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge badge-info">{{ $dept->employee_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ $dept->positions->count() }}</span>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-xs btn-primary dept-edit-btn"
                                        data-id="{{ $dept->id }}"
                                        data-name="{{ $dept->name }}"
                                        data-desc="{{ $dept->description }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('hr.departments.destroy', $dept->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete {{ $dept->name }}? Employees will be unassigned.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No departments yet. Add one on the left.
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
// ── Add Department ────────────────────────────────────────────────────────────
$('#dept-add-form').on('submit', function(e) {
    e.preventDefault();
    var btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true).text('Saving...');

    $.post('{{ route("hr.departments.store") }}', {
        _token:      '{{ csrf_token() }}',
        name:        $('#dept-name').val(),
        description: $('#dept-desc').val()
    })
    .done(function(r) {
        if (r.ok) {
            $('#dept-name').val('');
            $('#dept-desc').val('');
            location.reload();
        } else {
            alert(r.msg || 'Error saving department.');
        }
    })
    .fail(function(xhr) {
        var err = xhr.responseJSON;
        if (err && err.errors) {
            alert(Object.values(err.errors).flat().join('\n'));
        } else {
            alert('An error occurred. Please try again.');
        }
    })
    .always(function() {
        btn.prop('disabled', false).html('<i class="bi bi-plus-circle mr-1"></i>Add Department');
    });
});

// ── Edit Department ───────────────────────────────────────────────────────────
$(document).on('click', '.dept-edit-btn', function() {
    var id   = $(this).data('id');
    var name = $(this).data('name');
    var desc = $(this).data('desc') || '';

    $('#edit-dept-id').val(id);
    $('#edit-dept-name').val(name);
    $('#edit-dept-desc').val(desc);
    $('#dept-edit-card').removeClass('d-none');
    $('html, body').animate({ scrollTop: $('#dept-edit-card').offset().top - 80 }, 300);
});

$('#dept-edit-cancel').on('click', function() {
    $('#dept-edit-card').addClass('d-none');
});

$('#dept-edit-form').on('submit', function(e) {
    e.preventDefault();
    var id  = $('#edit-dept-id').val();
    var btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true).text('Saving...');

    $.ajax({
        url:    '/hr/departments/' + id,
        method: 'PUT',
        data: {
            _token:      '{{ csrf_token() }}',
            name:        $('#edit-dept-name').val(),
            description: $('#edit-dept-desc').val()
        }
    })
    .done(function(r) {
        if (r.ok) {
            location.reload();
        } else {
            alert(r.msg || 'Error updating department.');
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
        btn.prop('disabled', false).html('<i class="bi bi-check-circle mr-1"></i>Save Changes');
    });
});
</script>
@endsection
