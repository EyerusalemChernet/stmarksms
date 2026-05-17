@extends('layouts.master')
@section('page_title', 'Departments')
@section('content')

<style>
.dept-accordion .card { border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; margin-bottom:10px; }
.dept-accordion .dept-toggle { cursor:pointer; user-select:none; background:#f8f9fa; }
.dept-accordion .dept-toggle:hover { background:#f0f4f8; }
.dept-accordion .dept-toggle .chevron { transition:transform .2s; color:#888; }
.dept-accordion .card.show-dept .dept-toggle .chevron { transform:rotate(90deg); }
.dept-accordion .dept-body { display:none; border-top:1px solid #eee; }
.dept-accordion .card.show-dept .dept-body { display:block; }
.dept-teacher-table td { vertical-align:middle; }
.dept-teacher-remove { color:#dc3545; padding:0 .35rem; line-height:1; }
.dept-teacher-remove:hover { color:#a71d2a; }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header header-elements-inline">
                <h6 class="card-title mb-0"><i class="bi bi-building mr-1"></i> Add Department</h6>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('departments.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Sciences" required value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="bi bi-plus-circle mr-1"></i> Add Department
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8 dept-accordion">
        @forelse($departments as $dept)
            <div class="card {{ $loop->first ? 'show-dept' : '' }}" id="dept-card-{{ $dept->id }}">
                <div class="card-header dept-toggle d-flex justify-content-between align-items-center py-3" data-dept-id="{{ $dept->id }}">
                    <div class="d-flex align-items-center flex-grow-1" style="gap:10px;">
                        <i class="bi bi-chevron-right chevron"></i>
                        <h6 class="mb-0 font-weight-semibold">
                            <i class="bi bi-folder2-open mr-1 text-primary"></i>{{ $dept->name }}
                        </h6>
                        <span class="badge badge-secondary">{{ $dept->staff->count() }} teacher(s)</span>
                    </div>
                    @if($dept->description)
                        <small class="text-muted ml-3 d-none d-md-inline">{{ \Illuminate\Support\Str::limit($dept->description, 60) }}</small>
                    @endif
                </div>
                <div class="card-body dept-body">
                    @if($dept->description)
                        <p class="text-muted small mb-3 d-md-none">{{ $dept->description }}</p>
                    @endif

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover dept-teacher-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Teacher</th>
                                    <th class="text-right" style="width:80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dept->staff as $staff)
                                    @if($staff->user)
                                        <tr>
                                            <td>
                                                <i class="bi bi-person mr-1 text-muted"></i>{{ $staff->user->name }}
                                                @if($staff->user->email)
                                                    <br><small class="text-muted">{{ $staff->user->email }}</small>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <form method="post" action="{{ route('departments.teachers.remove', [$dept->id, $staff->user->id]) }}" class="d-inline form-remove-teacher mb-0" data-teacher-name="{{ $staff->user->name }}" data-dept-name="{{ $dept->name }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-link dept-teacher-remove" title="Remove from department">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted small">No teachers assigned yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($unassignedTeachers->isNotEmpty())
                        <form method="post" action="{{ route('departments.teachers.add', $dept->id) }}" class="form-inline flex-wrap" style="gap:8px;">
                            @csrf
                            <select name="user_id" class="form-control select-search" required data-placeholder="Select teacher to add" style="min-width:220px;">
                                <option value=""></option>
                                @foreach($unassignedTeachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg mr-1"></i> Add teacher
                            </button>
                        </form>
                    @else
                        <p class="text-muted small mb-0">All teachers are assigned to departments. Create new teachers under Users, or remove one from another department first.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-muted">No departments yet. Add one using the form on the left.</div>
            </div>
        @endforelse
    </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
    $(document).on('click', '.dept-toggle', function () {
        var $card = $(this).closest('.card');
        $card.toggleClass('show-dept');
        $('.dept-accordion .card').not($card).removeClass('show-dept');
    });

    $(document).on('submit', '.form-remove-teacher', function (e) {
        var $form = $(this);
        var teacher = $form.data('teacher-name');
        var dept = $form.data('dept-name');
        var msg = 'Remove ' + teacher + ' from ' + dept + '?';
        if (typeof swal === 'function') {
            e.preventDefault();
            swal({
                title: 'Remove teacher?',
                text: msg,
                icon: 'warning',
                buttons: ['Cancel', 'Remove'],
                dangerMode: true
            }).then(function (ok) {
                if (ok) { $form.off('submit').submit(); }
            });
        } else if (!window.confirm(msg)) {
            e.preventDefault();
        }
    });
})();
</script>
@endsection
