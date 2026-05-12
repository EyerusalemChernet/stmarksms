@extends('layouts.master')
@section('page_title', 'Leave Request #' . $request->id)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-calendar-x mr-2"></i>Leave Request #{{ $request->id }}
        <span class="badge badge-{{ $request->statusBadgeClass() }} ml-1">{{ ucfirst($request->status) }}</span>
    </h5>
    <a href="{{ route('hr.leave.requests') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        {{-- Employee card --}}
        <div class="card text-center p-3 mb-3">
            <img src="{{ $request->employee->photo_url }}" width="80" height="80"
                 class="rounded-circle mx-auto mb-2" style="object-fit:cover;">
            <h6 class="mb-0">{{ $request->employee->full_name }}</h6>
            <small class="text-muted">{{ $request->employee->employee_code }}</small>
        </div>

        {{-- Balance card --}}
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">{{ $request->leaveTypeLabel() }} Balance</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Entitled</td><td class="text-right font-weight-bold">{{ $balance->entitled }}</td></tr>
                    <tr><td>Used</td><td class="text-right text-danger">{{ $balance->used }}</td></tr>
                    <tr><td>Pending</td><td class="text-right text-warning">{{ $balance->pending }}</td></tr>
                    <tr class="table-success">
                        <td><strong>Available</strong></td>
                        <td class="text-right font-weight-bold text-success">{{ $balance->available }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        @if($request->isPending())
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Actions</h6></div>
            <div class="card-body">
                <form action="{{ route('hr.leave.requests.approve', $request->id) }}" method="POST" class="mb-2">
                    @csrf
                    <div class="form-group">
                        <label class="small font-weight-bold">Comment (optional)</label>
                        <input type="text" name="comment" class="form-control form-control-sm"
                               placeholder="Approval note">
                    </div>
                    <button type="submit" class="btn btn-success btn-block"
                            onclick="return confirm('Approve this leave request?')">
                        <i class="bi bi-check-circle mr-1"></i>Approve
                    </button>
                </form>
                <form action="{{ route('hr.leave.requests.reject', $request->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="small font-weight-bold">Rejection Reason</label>
                        <input type="text" name="comment" class="form-control form-control-sm"
                               placeholder="Reason for rejection">
                    </div>
                    <button type="submit" class="btn btn-danger btn-block"
                            onclick="return confirm('Reject this leave request?')">
                        <i class="bi bi-x-circle mr-1"></i>Reject
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if(!$request->isCancelled())
        <div class="mt-2">
            <form action="{{ route('hr.leave.requests.cancel', $request->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm btn-block"
                        onclick="return confirm('Cancel this leave request?')">
                    <i class="bi bi-slash-circle mr-1"></i>Cancel Request
                </button>
            </form>
        </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Request Details</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="font-weight-bold" style="width:35%">Leave Type</td>
                        <td><span class="badge badge-info">{{ $request->leaveTypeLabel() }}</span></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">From</td>
                        <td>{{ $request->start_date->format('l, d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">To</td>
                        <td>{{ $request->end_date->format('l, d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Working Days</td>
                        <td><span class="badge badge-primary">{{ $request->days_requested }}</span></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Reason</td>
                        <td>{{ $request->reason ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Submitted</td>
                        <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @if($request->reviewed_at)
                    <tr>
                        <td class="font-weight-bold">Reviewed By</td>
                        <td>{{ $request->reviewedBy?->name ?? '—' }} on {{ $request->reviewed_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($request->review_comment)
                    <tr>
                        <td class="font-weight-bold">Review Comment</td>
                        <td>{{ $request->review_comment }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        @if($request->isApproved())
        <div class="alert alert-success mt-3">
            <i class="bi bi-check-circle mr-1"></i>
            This leave has been approved. Attendance records have been automatically created for
            <strong>{{ $request->days_requested }}</strong> working day(s) from
            {{ $request->start_date->format('d M') }} to {{ $request->end_date->format('d M Y') }}.
        </div>
        @endif
    </div>
</div>
@endsection
