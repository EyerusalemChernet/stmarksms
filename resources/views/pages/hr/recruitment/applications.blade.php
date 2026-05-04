@extends('layouts.master')
@section('page_title', 'Job Applications')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people-fill mr-2"></i>Job Applications</h5>
    <div>
        <a href="{{ route('hr.recruitment.postings') }}" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="bi bi-briefcase mr-1"></i>Job Postings
        </a>
        <a href="{{ route('hr.recruitment.applications.create') }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-circle mr-1"></i>Add Application
        </a>
    </div>
</div>

{{-- Pipeline tabs --}}
<ul class="nav nav-tabs mb-3">
    @foreach(['all'=>['dark','All'],'applied'=>['secondary','Applied'],'shortlisted'=>['info','Shortlisted'],'interviewed'=>['warning','Interviewed'],'hired'=>['success','Hired'],'rejected'=>['danger','Rejected']] as $s=>[$cls,$lbl])
    <li class="nav-item">
        <a class="nav-link {{ $status === $s ? 'active' : '' }}"
           href="{{ route('hr.recruitment.applications', ['status' => $s, 'posting_id' => $postingId]) }}">
            {{ $lbl }}
            <span class="badge badge-{{ $cls }} ml-1">{{ $statusCounts[$s] ?? ($s === 'all' ? array_sum($statusCounts) : 0) }}</span>
        </a>
    </li>
    @endforeach
</ul>

{{-- Filter by posting --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.recruitment.applications') }}" method="GET" class="form-inline mb-0 flex-grow-1" style="gap:6px;">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="min-width:180px;"
                   placeholder="Search name, email, position…">
            <select name="posting_id" class="form-control form-control-sm">
                <option value="">— All Postings —</option>
                @foreach($postings as $p)
                    <option value="{{ $p->id }}" {{ $postingId == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
            @if($search || $postingId)
            <a href="{{ route('hr.recruitment.applications', ['status'=>$status]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
            @endif
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.recruitment.applications', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('hr.recruitment.applications', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Applicant</th>
                    <th>Job Posting</th>
                    <th>Applied</th>
                    <th>Interview</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                <tr>
                    <td class="text-muted small">{{ $app->id }}</td>
                    <td>
                        <div class="font-weight-bold">{{ $app->full_name }}</div>
                        <small class="text-muted">{{ $app->email ?? $app->phone ?? '—' }}</small>
                    </td>
                    <td>{{ $app->jobPosting?->title ?? '—' }}</td>
                    <td>{{ $app->applied_at->format('d M Y') }}</td>
                    <td>{{ $app->interview_date?->format('d M Y') ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ $app->statusBadgeClass() }}">
                            {{ ucfirst($app->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('hr.recruitment.applications.show', $app->id) }}"
                           class="btn btn-xs btn-primary"><i class="bi bi-eye"></i></a>
                        @if($app->isHired())
                        <a href="{{ route('hr.recruitment.applications.convert', $app->id) }}"
                           class="btn btn-xs btn-success" title="Convert to Employee">
                            <i class="bi bi-person-check"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No applications found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $applications->links() }}</div>
    </div>
</div>
@endsection
