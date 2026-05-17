@extends('layouts.master')
@section('page_title', 'Training Enrollments')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people mr-2"></i>Training Enrollments</h5>
    <a href="{{ route('hr.training.programs') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-mortarboard mr-1"></i>Programs
    </a>
</div>

{{-- Status tabs --}}
<ul class="nav nav-tabs mb-3">
    @foreach(['all'=>['dark','All'],'enrolled'=>['secondary','Enrolled'],'in_progress'=>['info','In Progress'],'completed'=>['success','Completed'],'failed'=>['danger','Failed'],'cancelled'=>['dark','Cancelled']] as $s=>[$cls,$lbl])
    <li class="nav-item">
        <a class="nav-link {{ $status === $s ? 'active' : '' }}"
           href="{{ route('hr.training.enrollments', array_merge(request()->query(), ['status'=>$s])) }}">
            {{ $lbl }}
            <span class="badge badge-{{ $cls }} ml-1">{{ $statusCounts[$s] ?? ($s==='all' ? array_sum($statusCounts) : 0) }}</span>
        </a>
    </li>
    @endforeach
</ul>

{{-- Search + filter + export --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.training.enrollments') }}" method="GET" class="form-inline mb-0 flex-grow-1" style="gap:6px;">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="min-width:180px;"
                   placeholder="Search employee name or code…">
            <select name="program_id" class="form-control form-control-sm">
                <option value="">— All Programs —</option>
                @foreach($programs as $p)
                    <option value="{{ $p->id }}" {{ $programId == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
            @if($search || $programId)
            <a href="{{ route('hr.training.enrollments', ['status'=>$status]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
            @endif
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.training.enrollments', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('hr.training.enrollments', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Employee</th>
                    <th>Program</th>
                    <th>Category</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th class="text-center">Score</th>
                    <th>Certificate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $e)
                <tr>
                    <td>
                        <div class="d-flex align-items-center" style="gap:6px;">
                            <img src="{{ $e->employee->photo_url }}" width="26" height="26"
                                 class="rounded-circle" style="object-fit:cover;">
                            <div>
                                <a href="{{ route('hr.training.employee', $e->employee_id) }}">
                                    {{ $e->employee->full_name }}
                                </a>
                                <br><small class="text-muted">{{ $e->employee->employee_code }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="font-weight-bold">{{ $e->program->title }}</td>
                    <td><span class="badge badge-{{ $e->program->categoryBadgeClass() }}">{{ $e->program->categoryLabel() }}</span></td>
                    <td class="text-muted small">{{ $e->start_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-muted small">{{ $e->end_date?->format('d M Y') ?? '—' }}</td>
                    <td><span class="badge badge-{{ $e->statusBadgeClass() }}">{{ $e->statusLabel() }}</span></td>
                    <td class="text-center">
                        @if($e->score !== null)
                            <span class="{{ $e->passed ? 'text-success' : 'text-danger' }} font-weight-bold">
                                {{ $e->score }}%
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">
                        @if($e->certificate_number)
                            <span class="text-success"><i class="bi bi-patch-check mr-1"></i>{{ $e->certificate_number }}</span>
                            @if($e->certificate_expiry)
                                <br><small class="{{ $e->isExpired() ? 'text-danger' : 'text-muted' }}">
                                    Exp: {{ $e->certificate_expiry->format('d M Y') }}
                                </small>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-xs btn-primary update-btn"
                                data-id="{{ $e->id }}"
                                data-status="{{ $e->status }}"
                                data-start="{{ $e->start_date?->format('Y-m-d') }}"
                                data-end="{{ $e->end_date?->format('Y-m-d') }}"
                                data-completion="{{ $e->completion_date?->format('Y-m-d') }}"
                                data-score="{{ $e->score }}"
                                data-passed="{{ $e->passed ? '1' : '0' }}"
                                data-cert="{{ $e->certificate_number }}"
                                data-expiry="{{ $e->certificate_expiry?->format('Y-m-d') }}"
                                data-notes="{{ $e->notes }}"
                                title="Update">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('hr.training.enrollments.destroy', $e->id) }}"
                              method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Remove this training record?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No enrollment records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $enrollments->links() }}</div>
    </div>
</div>

{{-- Update enrollment modal --}}
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="update-form" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Update Training Record</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Status</label>
                            <select name="status" id="u-status" class="form-control form-control-sm">
                                <option value="enrolled">Enrolled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Score (%)</label>
                            <input type="number" name="score" id="u-score" class="form-control form-control-sm"
                                   min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">Start Date</label>
                            <input type="date" name="start_date" id="u-start" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">End Date</label>
                            <input type="date" name="end_date" id="u-end" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">Completion Date</label>
                            <input type="date" name="completion_date" id="u-completion" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Certificate No.</label>
                            <input type="text" name="certificate_number" id="u-cert" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Certificate Expiry</label>
                            <input type="date" name="certificate_expiry" id="u-expiry" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Notes</label>
                        <textarea name="notes" id="u-notes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="passed" value="1" id="u-passed" class="form-check-input">
                        <label class="form-check-label small" for="u-passed">Passed</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).on('click', '.update-btn', function() {
    var id = $(this).data('id');
    $('#update-form').attr('action', '/hr/training/enrollments/' + id);
    $('#u-status').val($(this).data('status'));
    $('#u-score').val($(this).data('score') || '');
    $('#u-start').val($(this).data('start') || '');
    $('#u-end').val($(this).data('end') || '');
    $('#u-completion').val($(this).data('completion') || '');
    $('#u-cert').val($(this).data('cert') || '');
    $('#u-expiry').val($(this).data('expiry') || '');
    $('#u-notes').val($(this).data('notes') || '');
    $('#u-passed').prop('checked', $(this).data('passed') == '1');
    $('#updateModal').modal('show');
});
</script>
@endsection
