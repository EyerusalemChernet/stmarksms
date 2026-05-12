@extends('layouts.master')
@section('page_title', 'Leave Requests')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-calendar-x mr-2"></i>Leave Requests</h5>
    <a href="{{ route('hr.leave.requests.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle mr-1"></i>New Request
    </a>
</div>

{{-- Status tabs --}}
<ul class="nav nav-tabs mb-3">
    @foreach(['pending'=>['warning','Pending'],'approved'=>['success','Approved'],'rejected'=>['danger','Rejected'],'cancelled'=>['secondary','Cancelled'],'all'=>['dark','All']] as $s=>[$cls,$lbl])
    <li class="nav-item">
        <a class="nav-link {{ $status === $s ? 'active' : '' }}"
           href="{{ route('hr.leave.requests', array_merge(request()->query(), ['status' => $s])) }}">
            {{ $lbl }}
            <span class="badge badge-{{ $cls }} ml-1">{{ $statusCounts[$s] ?? 0 }}</span>
        </a>
    </li>
    @endforeach
</ul>

{{-- Search + Export bar --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.leave.requests') }}" method="GET" class="form-inline mb-0 flex-grow-1" style="gap:6px;">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="min-width:200px;"
                   placeholder="Search employee name or code…">
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
            @if($search || $month)
            <a href="{{ route('hr.leave.requests', ['status'=>$status]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
            @endif
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.leave.requests', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('hr.leave.requests', array_merge(request()->query(), ['export'=>'csv'])) }}"
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
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-center">Days</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td class="text-muted small">{{ $req->id }}</td>
                    <td>
                        <div class="d-flex align-items-center" style="gap:6px;">
                            <img src="{{ $req->employee->photo_url }}" width="26" height="26"
                                 class="rounded-circle" style="object-fit:cover;">
                            <a href="{{ route('hr.show', $req->employee_id) }}">{{ $req->employee->full_name }}</a>
                        </div>
                    </td>
                    <td><span class="badge badge-info">{{ $req->leaveTypeLabel() }}</span></td>
                    <td>{{ $req->start_date->format('d M Y') }}</td>
                    <td>{{ $req->end_date->format('d M Y') }}</td>
                    <td class="text-center font-weight-bold">{{ $req->days_requested }}</td>
                    <td>
                        <span class="badge badge-{{ $req->statusBadgeClass() }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $req->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('hr.leave.requests.show', $req->id) }}"
                           class="btn btn-xs btn-primary"><i class="bi bi-eye"></i></a>

                        @if($req->isPending())
                        <form action="{{ route('hr.leave.requests.approve', $req->id) }}"
                              method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-success" title="Approve"
                                    onclick="return confirm('Approve this leave request?')">
                                <i class="bi bi-check"></i>
                            </button>
                        </form>
                        <form action="{{ route('hr.leave.requests.reject', $req->id) }}"
                              method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-danger" title="Reject"
                                    onclick="return confirm('Reject this leave request?')">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No {{ $status !== 'all' ? $status : '' }} leave requests found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
