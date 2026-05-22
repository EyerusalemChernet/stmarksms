@extends('layouts.master')
@section('page_title', $canApprove ? 'Invoice Discount Approvals' : 'Discount Requests')
@section('content')

@if(!$canApprove)
<div class="alert alert-warning mb-3" style="font-size:13px;">
    <i class="bi bi-exclamation-triangle mr-1"></i>
    <strong>This page is for one student / one invoice only</strong> (e.g. scholarship on a single bill).
    For <strong>global sibling &amp; employee-child percentages</strong>, use
    <a href="{{ route('discount_rules.index') }}" class="alert-link font-weight-bold">View Discount Rules</a>
    in the menu — that is where your rule proposal appears.
</div>
@endif

{{-- Status tabs --}}
<div class="mb-3">
    <div class="btn-group" role="group">
        <a href="{{ route('discount_requests.index') }}?status=pending"
           class="btn btn-sm {{ request('status','pending') === 'pending' ? 'btn-warning' : 'btn-outline-secondary' }}">
            Pending
            @if($pendingCount > 0)<span class="badge badge-danger ml-1">{{ $pendingCount }}</span>@endif
        </a>
        <a href="{{ route('discount_requests.index') }}?status=approved"
           class="btn btn-sm {{ request('status') === 'approved' ? 'btn-success' : 'btn-outline-secondary' }}">Approved</a>
        <a href="{{ route('discount_requests.index') }}?status=rejected"
           class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-danger' : 'btn-outline-secondary' }}">Rejected</a>
        <a href="{{ route('discount_requests.index') }}"
           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
    </div>
    @if(\App\Services\FinancePermission::has('view_discount_rules'))
    <a href="{{ route('discount_rules.index') }}" class="btn btn-sm btn-primary ml-2">
        <i class="bi bi-sliders mr-1"></i>Global Discount Rules
    </a>
    @endif
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-percent mr-2"></i>Discount Requests ({{ $requests->total() }})</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead class="thead-light">
                <tr>
                    <th>#</th><th>Student</th><th>Invoice</th><th>Type</th>
                    <th>Requested</th><th class="d-none d-md-table-cell">Reason</th>
                    <th class="d-none d-md-table-cell">By</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $dr)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $dr->student->name ?? '-' }}</strong></td>
                    <td><a href="{{ route('fees.invoice', Qs::hash($dr->invoice_id)) }}" class="text-primary"><code style="font-size:11px;">{{ $dr->invoice->invoice_no ?? '-' }}</code></a></td>
                    <td><span class="badge badge-info">{{ \App\Models\DiscountRequest::typeLabel($dr->discount_type) }}</span></td>
                    <td class="font-weight-bold text-info">ETB {{ number_format($dr->requested_amount, 2) }}</td>
                    <td class="d-none d-md-table-cell" style="max-width:180px;"><span title="{{ $dr->reason }}">{{ \Illuminate\Support\Str::limit($dr->reason, 50) }}</span>@if($dr->supporting_info)<br><small class="text-muted">{{ $dr->supporting_info }}</small>@endif</td>
                    <td class="d-none d-md-table-cell">{{ $dr->requester->name ?? '-' }}<br><small class="text-muted">{{ $dr->created_at->format('d M Y') }}</small></td>
                    <td>
                        @if($dr->status === 'pending') <span class="badge badge-warning">Pending</span>
                        @elseif($dr->status === 'approved') <span class="badge badge-success">Approved<br><small>ETB {{ number_format($dr->approved_amount,2) }}</small></span>
                        @else <span class="badge badge-danger">Rejected</span>@if($dr->admin_note)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($dr->admin_note,30) }}</small>@endif
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($canApprove && $dr->isPending() && $dr->requested_by !== Auth::id())
                            <button class="btn btn-success btn-xs" data-toggle="modal" data-target="#approveModal{{ $dr->id }}" title="Approve"><i class="bi bi-check-lg"></i></button>
                            <button class="btn btn-danger btn-xs" data-toggle="modal" data-target="#rejectModal{{ $dr->id }}" title="Reject"><i class="bi bi-x-lg"></i></button>
                        @elseif($canApprove && $dr->isPending() && $dr->requested_by === Auth::id())
                            <span class="badge badge-secondary" title="Cannot approve own request">Self</span>
                        @elseif(!$canApprove && $dr->isPending())
                            <span class="badge badge-light text-muted" title="Waiting for an administrator to approve or reject">
                                <i class="bi bi-hourglass-split"></i> Awaiting admin
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:32px;opacity:.3;"></i><p class="mt-2 mb-0">No discount requests found.</p></td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
            <small class="text-muted">@if($requests->total() > 0)Showing {{ $requests->firstItem() }}{{ $requests->lastItem() }} of {{ $requests->total() }}@endif</small>
            {{ $requests->withQueryString()->links() }}
        </div>
    </div>
</div>

@if($canApprove)
@foreach($requests as $dr)
@if($dr->isPending() && $dr->requested_by !== Auth::id())
<div class="modal fade" id="approveModal{{ $dr->id }}" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="bi bi-check-circle mr-2"></i>Approve Discount</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
        <form action="{{ route('discount_requests.approve', $dr->id) }}" method="POST" target="_top">@csrf
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><td class="text-muted" style="width:110px">Student</td><td><strong>{{ $dr->student->name ?? '-' }}</strong></td></tr>
                    <tr><td class="text-muted">Invoice</td><td><code>{{ $dr->invoice->invoice_no ?? '-' }}</code></td></tr>
                    <tr><td class="text-muted">Type</td><td>{{ \App\Models\DiscountRequest::typeLabel($dr->discount_type) }}</td></tr>
                    <tr><td class="text-muted">Requested</td><td class="text-info font-weight-bold">ETB {{ number_format($dr->requested_amount,2) }}</td></tr>
                    <tr><td class="text-muted">Balance</td><td class="text-danger">ETB {{ number_format($dr->invoice->balance ?? 0,2) }}</td></tr>
                    <tr><td class="text-muted">Reason</td><td>{{ $dr->reason }}</td></tr>
                    @if($dr->supporting_info)<tr><td class="text-muted">Support</td><td>{{ $dr->supporting_info }}</td></tr>@endif
                </table>
                <div class="form-group"><label>Approved Amount (ETB) *</label><input type="number" name="approved_amount" class="form-control" step="0.01" min="1" max="{{ $dr->invoice->balance ?? $dr->requested_amount }}" value="{{ $dr->requested_amount }}" required><small class="text-muted">Max: ETB {{ number_format($dr->invoice->balance ?? 0,2) }}</small></div>
                <div class="form-group mb-0"><label>Admin Note <small class="text-muted">(optional)</small></label><textarea name="admin_note" class="form-control" rows="2" placeholder="e.g. Verified sibling enrollment"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle mr-1"></i>Approve & Apply Discount</button></div>
        </form>
    </div></div>
</div>
<div class="modal fade" id="rejectModal{{ $dr->id }}" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-x-circle mr-2"></i>Reject Discount Request</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
        <form action="{{ route('discount_requests.reject', $dr->id) }}" method="POST" target="_top">@csrf
            <div class="modal-body">
                <p class="mb-2">Rejecting discount for <strong>{{ $dr->student->name ?? '-' }}</strong>  <span class="text-info">ETB {{ number_format($dr->requested_amount,2) }}</span></p>
                <div class="form-group mb-0"><label>Rejection Reason *</label><textarea name="admin_note" class="form-control" rows="3" required placeholder="Explain why this discount is not approved..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle mr-1"></i>Reject Request</button></div>
        </form>
    </div></div>
</div>
@endif
@endforeach
@endif

@endsection

