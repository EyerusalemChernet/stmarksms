@extends('layouts.master')
@section('page_title', 'Job Postings')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-briefcase-fill mr-2"></i>Job Postings</h5>
    <div>
        <a href="{{ route('hr.recruitment.applications') }}" class="btn btn-sm btn-outline-primary mr-1">
            <i class="bi bi-people mr-1"></i>All Applications
        </a>
        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addPostingModal">
            <i class="bi bi-plus-circle mr-1"></i>New Job Posting
        </button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.recruitment.postings') }}" method="GET" class="form-inline mb-0 flex-grow-1" style="gap:6px;">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="min-width:200px;"
                   placeholder="Search title or department…">
            <select name="status_filter" class="form-control form-control-sm">
                <option value="all"    {{ $statusF === 'all'    ? 'selected' : '' }}>All Statuses</option>
                <option value="open"   {{ $statusF === 'open'   ? 'selected' : '' }}>Open</option>
                <option value="on_hold"{{ $statusF === 'on_hold'? 'selected' : '' }}>On Hold</option>
                <option value="closed" {{ $statusF === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
            @if($search || $statusF !== 'all')
            <a href="{{ route('hr.recruitment.postings') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
            @endif
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.recruitment.postings', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('hr.recruitment.postings', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="row mb-3">
    @php
        $open   = $postings->where('status','open')->count();
        $closed = $postings->where('status','closed')->count();
        $hold   = $postings->where('status','on_hold')->count();
    @endphp
    <div class="col-md-4">
        <div class="card stat-card stat-success text-white text-center p-3">
            <h3>{{ $open }}</h3><small>Open Positions</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card stat-warning text-white text-center p-3">
            <h3>{{ $hold }}</h3><small>On Hold</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card stat-secondary text-white text-center p-3">
            <h3>{{ $closed }}</h3><small>Closed</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th class="text-center">Vacancies</th>
                    <th class="text-center">Applications</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($postings as $p)
                <tr>
                    <td class="font-weight-bold">{{ $p->title }}</td>
                    <td>{{ $p->department?->name ?? '—' }}</td>
                    <td><span class="badge badge-light border">{{ ucwords(str_replace('_',' ',$p->employment_type)) }}</span></td>
                    <td class="text-center">{{ $p->vacancies }}</td>
                    <td class="text-center">
                        <a href="{{ route('hr.recruitment.applications', ['posting_id' => $p->id]) }}"
                           class="badge badge-primary">{{ $p->applications_count }}</a>
                    </td>
                    <td>
                        @if($p->deadline)
                            <span class="{{ $p->deadline->isPast() ? 'text-danger' : '' }}">
                                {{ $p->deadline->format('d M Y') }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $p->statusBadgeClass() }}">{{ ucfirst($p->status) }}</span>
                    </td>
                    <td>
                        <a href="{{ route('hr.recruitment.applications.create', $p->id) }}"
                           class="btn btn-xs btn-info" title="Add Application">
                            <i class="bi bi-person-plus"></i>
                        </a>
                        <a href="{{ route('hr.recruitment.postings.edit', $p->id) }}"
                           class="btn btn-xs btn-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('hr.recruitment.postings.destroy', $p->id) }}"
                              method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Delete this posting?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No job postings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Posting Modal --}}
<div class="modal fade" id="addPostingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('hr.recruitment.postings.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-briefcase mr-1"></i>New Job Posting</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="font-weight-bold">Job Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Vacancies</label>
                            <input type="number" name="vacancies" class="form-control" value="1" min="1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Department</label>
                            <select name="department_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Position</label>
                            <select name="position_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Employment Type</label>
                            <select name="employment_type" class="form-control">
                                @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $v=>$l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Deadline</label>
                            <input type="date" name="deadline" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Status</label>
                            <select name="status" class="form-control">
                                <option value="open">Open</option>
                                <option value="on_hold">On Hold</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle mr-1"></i>Create Posting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
