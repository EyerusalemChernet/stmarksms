@extends('layouts.master')
@section('page_title', 'Training Programs')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-mortarboard mr-2"></i>Training Programs</h5>
    <div style="gap:6px;" class="d-flex">
        <a href="{{ route('hr.training.enrollments') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-people mr-1"></i>All Enrollments
        </a>
        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addProgramModal">
            <i class="bi bi-plus-circle mr-1"></i>New Program
        </button>
    </div>
</div>

{{-- Search + filter + export --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.training.programs') }}" method="GET" class="form-inline mb-0 flex-grow-1" style="gap:6px;">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="min-width:200px;"
                   placeholder="Search title or provider…">
            <select name="category" class="form-control form-control-sm">
                <option value="all" {{ $category === 'all' ? 'selected' : '' }}>All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_',' ',$cat)) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
            @if($search || $category !== 'all')
            <a href="{{ route('hr.training.programs') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
            @endif
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.training.programs', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('hr.training.programs', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="row mb-3">
    @php
        $totalPrograms   = $programs->count();
        $mandatory       = $programs->where('is_mandatory', true)->count();
        $totalEnrollments = $programs->sum('enrollments_count');
        $totalCompleted  = $programs->sum('completed_count');
    @endphp
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-2">
            <h4 class="text-primary mb-0">{{ $totalPrograms }}</h4><small class="text-muted">Programs</small>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-2">
            <h4 class="text-danger mb-0">{{ $mandatory }}</h4><small class="text-muted">Mandatory</small>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-2">
            <h4 class="text-info mb-0">{{ $totalEnrollments }}</h4><small class="text-muted">Total Enrollments</small>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-2">
            <h4 class="text-success mb-0">{{ $totalCompleted }}</h4><small class="text-muted">Completed</small>
        </div>
    </div>
</div>

{{-- Programs table --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Provider</th>
                    <th class="text-center">Hours</th>
                    <th class="text-center">Enrolled</th>
                    <th class="text-center">Completed</th>
                    <th class="text-center">Mandatory</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $p)
                <tr class="{{ !$p->is_active ? 'text-muted' : '' }}">
                    <td>
                        <div class="font-weight-bold">{{ $p->title }}</div>
                        @if($p->description)
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($p->description, 80) }}</small>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $p->categoryBadgeClass() }}">{{ $p->categoryLabel() }}</span></td>
                    <td class="text-muted small">{{ $p->provider ?? '—' }}</td>
                    <td class="text-center">{{ $p->duration_hours ? $p->duration_hours.'h' : '—' }}</td>
                    <td class="text-center"><span class="badge badge-info">{{ $p->enrollments_count }}</span></td>
                    <td class="text-center"><span class="badge badge-success">{{ $p->completed_count }}</span></td>
                    <td class="text-center">
                        @if($p->is_mandatory)
                            <span class="badge badge-danger">Yes</span>
                        @else
                            <span class="text-muted small">No</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('hr.training.enrollments', ['program_id'=>$p->id]) }}"
                           class="btn btn-xs btn-info" title="View Enrollments">
                            <i class="bi bi-people"></i>
                        </a>
                        <button type="button" class="btn btn-xs btn-success enroll-btn"
                                data-id="{{ $p->id }}"
                                data-title="{{ $p->title }}"
                                title="Enroll Employee">
                            <i class="bi bi-person-plus"></i>
                        </button>
                        <a href="{{ route('hr.training.programs.edit', $p->id) }}"
                           class="btn btn-xs btn-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('hr.training.programs.destroy', $p->id) }}"
                              method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Delete this program? All enrollment records will also be deleted.')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No training programs yet. Add one above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Program Modal --}}
<div class="modal fade" id="addProgramModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('hr.training.programs.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-mortarboard mr-1"></i>New Training Program</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="font-weight-bold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required
                                   placeholder="e.g. Active Learning Strategies">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-control" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ ucwords(str_replace('_',' ',$cat)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Provider / Trainer</label>
                            <input type="text" name="provider" class="form-control"
                                   placeholder="e.g. Ministry of Education">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Duration (hours)</label>
                            <input type="number" name="duration_hours" class="form-control" min="1">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Cost (ETB)</label>
                            <input type="number" name="cost" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="What this training covers"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="mandatory">
                        <label class="form-check-label" for="mandatory">Mandatory for all staff</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle mr-1"></i>Create Program
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- Enroll Employee Modal --}}
<div class="modal fade" id="enrollModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('hr.training.enroll') }}" method="POST">
                @csrf
                <input type="hidden" name="training_program_id" id="enroll-program-id">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus mr-1"></i>Enroll Employee
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Program: <strong id="enroll-program-title"></strong>
                    </p>
                    <div class="form-group">
                        <label class="font-weight-bold">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">— Select Employee —</option>
                            @foreach(\App\Models\Employee::where('status','active')->orderBy('first_name')->get() as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->full_name }} ({{ $emp->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle mr-1"></i>Enroll
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Enroll Employee button — populate modal with program id and title
$(document).on('click', '.enroll-btn', function() {
    var id    = $(this).data('id');
    var title = $(this).data('title');
    $('#enroll-program-id').val(id);
    $('#enroll-program-title').text(title);
    $('#enrollModal').modal('show');
});
</script>
@endsection
