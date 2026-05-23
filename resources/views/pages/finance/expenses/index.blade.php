@extends('layouts.master')
@section('page_title', $canApprove ? 'Expense Approvals' : 'Expenses')
@section('content')

@if(!$canApprove)
<div class="alert alert-info mb-3" style="font-size:13px;">
  <i class="bi bi-info-circle mr-1"></i>
  Expenses you submit stay <strong>Pending</strong> until an administrator approves them. Approved expenses appear in reports and totals.
  Only <strong>Super Admin</strong> can delete expenses.
</div>
@else
<div class="alert alert-info mb-3 py-2" style="font-size:13px;">
  <i class="bi bi-info-circle mr-1"></i>
  All expenses go <strong>Pending</strong> first. You can approve or reject expenses submitted by others. Only <strong>Super Admin</strong> can delete expenses.
</div>
@endif

{{-- Status tabs --}}
<div class="mb-3">
  <div class="btn-group" role="group">
    <a href="{{ route('expenses.index') }}?status=pending{{ request()->except('status') ? '&'.http_build_query(request()->except(['status','page'])) : '' }}"
       class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-secondary' }}">
      Pending
      @if($pendingCount > 0)<span class="badge badge-danger ml-1">{{ $pendingCount }}</span>@endif
    </a>
    <a href="{{ route('expenses.index') }}?status=approved{{ request()->except('status') ? '&'.http_build_query(request()->except(['status','page'])) : '' }}"
       class="btn btn-sm {{ request('status') === 'approved' ? 'btn-success' : 'btn-outline-secondary' }}">Approved</a>
    <a href="{{ route('expenses.index') }}?status=rejected{{ request()->except('status') ? '&'.http_build_query(request()->except(['status','page'])) : '' }}"
       class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-danger' : 'btn-outline-secondary' }}">Rejected</a>
    <a href="{{ route('expenses.index', request()->except(['status','page'])) }}"
       class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
  </div>
</div>

{{-- Summary --}}
<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3 d-flex justify-content-between align-items-center">
      <div><div style="font-size:11px;color:#94a3b8">FILTERED TOTAL</div>
      <div style="font-size:20px;font-weight:700;color:#ef4444">ETB {{ number_format($total,2) }}</div></div>
      <i class="bi bi-cash-stack text-danger" style="font-size:28px;opacity:.4"></i>
    </div></div>
  </div>
</div>

{{-- Filters --}}
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Search</label>
      <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Title...">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Category</label>
      <select name="category_id" class="form-control form-control-sm">
        <option value="">All</option>
        @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">From</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Recurring</label>
      <select name="recurring" class="form-control form-control-sm">
        <option value="">All</option>
        <option value="1" {{ request('recurring')==='1'?'selected':'' }}>Yes</option>
        <option value="0" {{ request('recurring')==='0'?'selected':'' }}>No</option>
      </select>
    </div>
    <div class="col-md-2 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('expenses.index') }}" class="btn btn-light btn-sm">Reset</a>
    </div>
  </form>
</div></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0"><i class="bi bi-receipt-cutoff mr-2"></i>Expenses ({{ $expenses->total() }})</h6>
    <div class="d-flex gap-2">
      <a href="{{ route('expenses.csv') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success btn-sm"><i class="bi bi-download mr-1"></i>CSV</a>
      <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Expense</a>
      <a href="{{ route('expense_cats.index') }}" class="btn btn-light btn-sm"><i class="bi bi-tags mr-1"></i>Categories</a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Date</th>
          <th>Title</th>
          <th class="d-none d-md-table-cell">Category</th>
          <th>Amount</th>
          <th class="d-none d-md-table-cell">By</th>
          <th>Status</th>
          <th class="d-none d-md-table-cell">Recurring</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($expenses as $exp)
        <tr>
          <td>{{ $exp->expense_date->format('d M Y') }}</td>
          <td>
            <strong>{{ $exp->title }}</strong>
            @if($exp->description)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($exp->description,60) }}</small>@endif
          </td>
          <td class="d-none d-md-table-cell">{{ $exp->category->name ?? '-' }}</td>
          <td class="text-danger font-weight-bold">ETB {{ number_format($exp->amount,2) }}</td>
          <td class="d-none d-md-table-cell">{{ $exp->creator->name ?? '-' }}</td>
          <td>
            @if($exp->status === 'pending')
              <span class="badge badge-warning">Pending</span>
            @elseif($exp->status === 'approved')
              <span class="badge badge-success">Approved</span>
              @if($exp->approver)<br><small class="text-muted">{{ $exp->approver->name }}</small>@endif
            @else
              <span class="badge badge-danger">Rejected</span>
              @if($exp->rejection_reason)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($exp->rejection_reason,30) }}</small>@endif
            @endif
          </td>
          <td class="d-none d-md-table-cell">
            @if($exp->recurring)<span class="badge badge-info">{{ ucfirst($exp->recurrence_interval) }}</span>@else<span class="text-muted">-</span>@endif
          </td>
          <td class="text-nowrap">
            @if($canApprove && $exp->isPending() && $exp->created_by !== Auth::id())
              <button class="btn btn-success btn-xs" data-toggle="modal" data-target="#approveExp{{ $exp->id }}" title="Approve"><i class="bi bi-check-lg"></i></button>
              <button class="btn btn-danger btn-xs" data-toggle="modal" data-target="#rejectExp{{ $exp->id }}" title="Reject"><i class="bi bi-x-lg"></i></button>
            @elseif($canApprove && $exp->isPending() && $exp->created_by === Auth::id())
              <span class="badge badge-secondary" title="Cannot approve own expense">Self</span>
            @elseif(!$canApprove && $exp->isPending())
              <span class="badge badge-light text-muted"><i class="bi bi-hourglass-split"></i> Awaiting admin</span>
            @endif
            @if(\App\Services\FinancePermission::canEditExpense($exp))
              <a href="{{ route('expenses.edit',$exp->id) }}" class="btn btn-warning btn-xs"><i class="bi bi-pencil"></i></a>
            @elseif($exp->isApproved())
              <span class="badge badge-secondary" title="Approved — cannot edit"><i class="bi bi-lock"></i></span>
            @endif
            @if($canDeleteExpenses)
              <form action="{{ route('expenses.destroy',$exp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?')">@csrf @method('DELETE')<button class="btn btn-danger btn-xs" title="Delete (Super Admin only)"><i class="bi bi-trash"></i></button></form>
            @endif
            @if($exp->receipt_file && $exp->isApproved())
            <a href="{{ \Illuminate\Support\Facades\Storage::url($exp->receipt_file) }}" target="_blank" class="btn btn-light btn-xs" title="View receipt"><i class="bi bi-paperclip"></i></a>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No expenses found.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted">Showing {{ $expenses->firstItem() }}–{{ $expenses->lastItem() }} of {{ $expenses->total() }}</small>
      {{ $expenses->withQueryString()->links() }}
    </div>
  </div>
</div>

@if($canApprove)
@foreach($expenses as $exp)
@if($exp->isPending() && $exp->created_by !== Auth::id())
<div class="modal fade" id="approveExp{{ $exp->id }}" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="bi bi-check-circle mr-2"></i>Approve Expense</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form action="{{ route('expenses.approve', $exp->id) }}" method="POST">@csrf
      <div class="modal-body">
        <p class="mb-2"><strong>{{ $exp->title }}</strong> — <span class="text-danger">ETB {{ number_format($exp->amount,2) }}</span></p>
        <p class="text-muted small mb-3">Submitted by {{ $exp->creator->name ?? 'Unknown' }} on {{ $exp->expense_date->format('d M Y') }}</p>
        <div class="form-group mb-0"><label>Approval Note <small class="text-muted">(optional)</small></label><textarea name="approval_note" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle mr-1"></i>Approve</button></div>
    </form>
  </div></div>
</div>
<div class="modal fade" id="rejectExp{{ $exp->id }}" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-x-circle mr-2"></i>Reject Expense</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form action="{{ route('expenses.reject', $exp->id) }}" method="POST">@csrf
      <div class="modal-body">
        <p class="mb-2">Reject <strong>{{ $exp->title }}</strong> (ETB {{ number_format($exp->amount,2) }})</p>
        <div class="form-group mb-0"><label>Rejection Reason *</label><textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why this expense is not approved..."></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle mr-1"></i>Reject</button></div>
    </form>
  </div></div>
</div>
@endif
@endforeach
@endif

@endsection
