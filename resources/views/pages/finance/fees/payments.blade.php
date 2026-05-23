@extends('layouts.master')
@section('page_title','Payment Transactions')
@section('content')

<div class="row mb-3">
  <div class="col-md-4 col-6 mb-2">
    <div class="card border-0 shadow-sm"><div class="card-body py-3 d-flex justify-content-between align-items-center">
      <div><div style="font-size:11px;color:#94a3b8">TODAY</div>
      <div style="font-size:20px;font-weight:700;color:#22c55e">ETB {{ number_format($total_today,2) }}</div></div>
      <i class="bi bi-calendar-check text-success" style="font-size:28px;opacity:.4"></i>
    </div></div>
  </div>
  <div class="col-md-4 col-6 mb-2">
    <div class="card border-0 shadow-sm"><div class="card-body py-3 d-flex justify-content-between align-items-center">
      <div><div style="font-size:11px;color:#94a3b8">THIS MONTH</div>
      <div style="font-size:20px;font-weight:700;color:#3b82f6">ETB {{ number_format($total_month,2) }}</div></div>
      <i class="bi bi-calendar-month text-primary" style="font-size:28px;opacity:.4"></i>
    </div></div>
  </div>
  <div class="col-md-4 col-12 mb-2">
    <div class="card border-0 shadow-sm"><div class="card-body py-3 d-flex justify-content-between align-items-center">
      <div><div style="font-size:11px;color:#94a3b8">ACCESS</div>
      <div style="font-size:16px;font-weight:600;color:#6366f1">
        @if($paymentsViewOnly)View only @else Full access @endif
      </div></div>
      <i class="bi bi-{{ $paymentsViewOnly ? 'eye' : 'shield-check' }}" style="font-size:28px;opacity:.4"></i>
    </div></div>
  </div>
</div>

@if($paymentsViewOnly)
<div class="alert alert-secondary mb-3 py-2" style="font-size:13px;">
  <i class="bi bi-eye mr-1"></i><strong>Super Admin — view only.</strong> You can browse payment transactions but cannot record or modify payments here.
</div>
@endif

<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Student</label>
      <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Method</label>
      <select name="method" class="form-control form-control-sm">
        <option value="">All</option>
        <option value="cash" {{ request('method')==='cash'?'selected':'' }}>Cash</option>
        <option value="bank_transfer" {{ request('method')==='bank_transfer'?'selected':'' }}>Bank Transfer</option>
        <option value="mobile_money" {{ request('method')==='mobile_money'?'selected':'' }}>Mobile Money</option>
        <option value="chapa" {{ request('method')==='chapa'?'selected':'' }}>Chapa</option>
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
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('fees.payments') }}" class="btn btn-light btn-sm">Reset</a>
    </div>
  </form>
</div></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-cash-stack mr-2"></i>Payment Transactions ({{ $payments->total() }})</h6>
    @if($paymentsViewOnly)
    <small class="text-muted">View only — no payment actions</small>
    @else
    <small class="text-muted">{{ ucfirst(str_replace('_', ' ', auth()->user()->user_type)) }}: payment history</small>
    @endif
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Receipt</th>
          <th>Student</th>
          <th class="d-none d-md-table-cell">Fee Type</th>
          <th class="d-none d-md-table-cell">Inst.</th>
          <th>Amount</th>
          <th class="d-none d-md-table-cell">Method</th>
          <th class="d-none d-md-table-cell">Collected By</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $pay)
        <tr>
          <td><code>{{ $pay->receipt_no }}</code></td>
          <td>{{ $pay->student->name ?? '-' }}</td>
          <td class="d-none d-md-table-cell">{{ optional(optional($pay->invoice->fee_structure)->category)->name ?? '-' }}</td>
          <td class="d-none d-md-table-cell">#{{ $pay->installment_no }}</td>
          <td class="text-success font-weight-bold">ETB {{ number_format($pay->amount,2) }}</td>
          <td class="d-none d-md-table-cell"><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$pay->payment_method)) }}</span></td>
          <td class="d-none d-md-table-cell">{{ $pay->collector->name ?? '-' }}</td>
          <td>{{ $pay->paid_at ? $pay->paid_at->format('d M Y') : '-' }}</td>
          <td><a href="{{ route('fees.receipt', Qs::hash($pay->id)) }}" class="btn btn-light btn-xs" target="_blank"><i class="bi bi-printer"></i></a></td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-muted py-4">No payments found.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted">
        @if($payments->total() > 0)Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}@endif
      </small>
      {{ $payments->withQueryString()->links() }}
    </div>
  </div>
</div>

@if(auth()->user()->user_type === 'super_admin' && isset($auditLogs) && $auditLogs->count() > 0)
<div class="card mt-4">
  <div class="card-header">
    <h6 class="mb-0"><i class="bi bi-shield-exclamation mr-2"></i>Payment Audit Logs (Super Admin Only)</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-sm mb-0" style="font-size:12px;">
      <thead class="thead-light">
        <tr>
          <th>Action</th>
          <th>User</th>
          <th>Description</th>
          <th>IP Address</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        @foreach($auditLogs as $log)
        <tr>
          <td>
            <span class="badge badge-{{ $log->action === 'payment_verified' ? 'success' : ($log->action === 'payment_failed' ? 'danger' : 'info') }}">
              {{ ucfirst(str_replace('_', ' ', $log->action)) }}
            </span>
          </td>
          <td>{{ $log->user->name ?? 'System' }}</td>
          <td>{{ $log->description }}</td>
          <td><code>{{ $log->ip_address }}</code></td>
          <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
  </div>
</div>
@endif

@endsection
