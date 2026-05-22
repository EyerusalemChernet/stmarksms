@extends('layouts.master')
@section('page_title', 'Discount Rule Approvals')
@section('content')

<div class="alert alert-info mb-4" style="font-size:13px;">
    <i class="bi bi-info-circle mr-2"></i>
    <strong>Global discount rules</strong> — Accountants propose sibling and employee-child percentages here.
    When you approve, the system applies them automatically to all eligible families (no per-student approval needed).
</div>

<div class="row mb-4">
    <div class="col-md-4 col-6 mb-3">
        <div class="card border-primary shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div style="font-size:11px;color:#94a3b8">CURRENT SIBLING DISCOUNT</div>
                <div style="font-size:28px;font-weight:700;color:#3b82f6">{{ $currentSibling }}%</div>
                <small class="text-muted">2+ children, same parent</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6 mb-3">
        <div class="card border-success shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div style="font-size:11px;color:#94a3b8">CURRENT EMPLOYEE CHILD DISCOUNT</div>
                <div style="font-size:28px;font-weight:700;color:#22c55e">{{ $currentEmployee }}%</div>
                <small class="text-muted">Parent is school staff</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-warning shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div style="font-size:11px;color:#94a3b8">PENDING PROPOSALS</div>
                <div style="font-size:28px;font-weight:700;color:#f59e0b">{{ $pendingCount }}</div>
                <small class="text-muted">Awaiting your decision</small>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <div class="btn-group" role="group">
        <a href="{{ route('discount_rules.index') }}?status=pending"
           class="btn btn-sm {{ ($status ?? 'pending') === 'pending' ? 'btn-warning' : 'btn-outline-secondary' }}">
            Pending @if($pendingCount > 0)<span class="badge badge-danger ml-1">{{ $pendingCount }}</span>@endif
        </a>
        <a href="{{ route('discount_rules.index') }}?status=approved"
           class="btn btn-sm {{ ($status ?? '') === 'approved' ? 'btn-success' : 'btn-outline-secondary' }}">Approved</a>
        <a href="{{ route('discount_rules.index') }}?status=rejected"
           class="btn btn-sm {{ ($status ?? '') === 'rejected' ? 'btn-danger' : 'btn-outline-secondary' }}">Rejected</a>
        <a href="{{ route('discount_rules.index') }}?status=all"
           class="btn btn-sm {{ ($status ?? '') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
    </div>
    <a href="{{ route('discount_rules.index') }}" class="btn btn-sm btn-light ml-2">
        <i class="bi bi-people mr-1"></i>View Affected Students
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-percent mr-2"></i>Accountant Rule Proposals</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Sibling Discount</th>
                    <th>Employee Child Discount</th>
                    <th>Accountant Reason</th>
                    <th>Submitted By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                <tr>
                    <td>{{ $loop->iteration + ($rules->currentPage() - 1) * $rules->perPage() }}</td>
                    <td>
                        <span class="badge badge-primary px-3 py-2" style="font-size:14px;">
                            {{ $rule->proposed_sibling_pct }}%
                        </span>
                        @if($rule->status === 'pending')
                        <br><small class="text-muted">was {{ $currentSibling }}%</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-success px-3 py-2" style="font-size:14px;">
                            {{ $rule->proposed_employee_pct }}%
                        </span>
                        @if($rule->status === 'pending')
                        <br><small class="text-muted">was {{ $currentEmployee }}%</small>
                        @endif
                    </td>
                    <td style="max-width:220px;">{{ $rule->reason ?? '-' }}</td>
                    <td>
                        <strong>{{ $rule->requester->name ?? '-' }}</strong>
                        <br><small class="text-muted">{{ $rule->created_at->format('d M Y H:i') }}</small>
                    </td>
                    <td>
                        @if($rule->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @elseif($rule->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                            @if($rule->admin_note)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($rule->admin_note, 40) }}</small>@endif
                        @else
                            <span class="badge badge-danger">Rejected</span>
                            @if($rule->admin_note)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($rule->admin_note, 40) }}</small>@endif
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        @if($rule->isPending())
                        <form action="{{ route('discount_rules.approve', Qs::hash($rule->id)) }}" method="POST" class="d-inline" target="_top">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm"
                                data-toggle="modal" data-target="#rejectRule{{ $rule->id }}">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:36px;opacity:.3;"></i>
                        <p class="mt-2 mb-0">No rule proposals found.</p>
                        <small>When an accountant submits new sibling / employee-child rates, they appear here.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="p-3">{{ $rules->withQueryString()->links() }}</div>
    </div>
</div>

@foreach($rules as $rule)
@if($rule->isPending())
<div class="modal fade" id="rejectRule{{ $rule->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Discount Rule Proposal</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('discount_rules.reject', Qs::hash($rule->id)) }}" method="POST" target="_top">
                @csrf
                <div class="modal-body">
                    <p class="mb-2">Rejecting proposed rates:</p>
                    <ul class="mb-3">
                        <li><strong>Sibling:</strong> {{ $rule->proposed_sibling_pct }}%</li>
                        <li><strong>Employee Child:</strong> {{ $rule->proposed_employee_pct }}%</li>
                    </ul>
                    <div class="form-group mb-0">
                        <label for="admin_note_{{ $rule->id }}">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea id="admin_note_{{ $rule->id }}" name="admin_note" class="form-control" rows="3" required
                                  minlength="3" maxlength="500"
                                  placeholder="Explain why these rates are not approved...">{{ old('admin_note') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle mr-1"></i> Reject Proposal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

@endsection


