@extends('layouts.master')
@section('page_title', 'Leave Request #' . $leaveRequest->id)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-calendar-x mr-2"></i>Leave Request #{{ $leaveRequest->id }}
        <span class="badge badge-{{ $leaveRequest->statusBadgeClass() }} ml-1">
            {{ ucfirst($leaveRequest->status) }}
        </span>
    </h5>
    <a href="{{ route('my.leave.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>My Leave
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Request Details</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="font-weight-bold" style="width:35%">Leave Type</td>
                        <td><span class="badge badge-info">{{ $leaveRequest->leaveTypeLabel() }}</span></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">From</td>
                        <td>{{ $leaveRequest->start_date->format('l, d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">To</td>
                        <td>{{ $leaveRequest->end_date->format('l, d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Working Days</td>
                        <td><span class="badge badge-primary">{{ $leaveRequest->days_requested }}</span></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Reason</td>
                        <td>{{ $leaveRequest->reason ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Status</td>
                        <td>
                            <span class="badge badge-{{ $leaveRequest->statusBadgeClass() }}">
                                {{ ucfirst($leaveRequest->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Submitted</td>
                        <td>{{ $leaveRequest->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @if($leaveRequest->reviewed_at)
                    <tr>
                        <td class="font-weight-bold">Reviewed</td>
                        <td>{{ $leaveRequest->reviewed_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($leaveRequest->review_comment)
                    <tr>
                        <td class="font-weight-bold">HR Comment</td>
                        <td class="text-muted">{{ $leaveRequest->review_comment }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Balance card --}}
        <div class="card mt-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">{{ $leaveRequest->leaveTypeLabel() }} Balance</h6>
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

        {{-- Status messages --}}
        @if($leaveRequest->isPending())
        <div class="alert alert-warning mt-3">
            <i class="bi bi-hourglass-split mr-1"></i>
            Your request is pending HR review. You can cancel it below if needed.
        </div>
        <form action="{{ route('my.leave.cancel', $leaveRequest->id) }}" method="POST" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Cancel this leave request?')">
                <i class="bi bi-slash-circle mr-1"></i>Cancel Request
            </button>
        </form>
        @elseif($leaveRequest->isApproved())
        <div class="alert alert-success mt-3">
            <i class="bi bi-check-circle mr-1"></i>
            Your leave has been <strong>approved</strong>.
            Attendance records have been created for
            <strong>{{ $leaveRequest->days_requested }}</strong> working day(s)
            from {{ $leaveRequest->start_date->format('d M') }}
            to {{ $leaveRequest->end_date->format('d M Y') }}.
        </div>
        @elseif($leaveRequest->status === 'rejected')
        <div class="alert alert-danger mt-3">
            <i class="bi bi-x-circle mr-1"></i>
            Your leave request was <strong>rejected</strong>.
            @if($leaveRequest->review_comment)
                Reason: {{ $leaveRequest->review_comment }}
            @endif
        </div>
        @elseif($leaveRequest->isCancelled())
        <div class="alert alert-secondary mt-3">
            <i class="bi bi-slash-circle mr-1"></i>This request was cancelled.
        </div>
        @endif
    </div>
</div>
@endsection
