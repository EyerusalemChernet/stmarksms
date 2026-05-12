@extends('layouts.master')
@section('page_title','Outstanding Fees')
@section('content')
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Session</label>
      <input type="text" name="session" class="form-control form-control-sm" value="{{ $session }}">
    </div>
    <div class="col-md-3 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Class</label>
      <select name="class_id" class="form-control form-control-sm">
        <option value="">All Classes</option>
        @foreach($classes as $c)<option value="{{ $c->id }}" {{ $class_id==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('reports.outstanding') }}" class="btn btn-light btn-sm">Reset</a>
      <a href="{{ route('reports.outstanding') }}?session={{ $session }}&class_id={{ $class_id }}&export=csv" class="btn btn-success btn-sm"><i class="bi bi-download mr-1"></i>CSV</a>
    </div>
  </form>
</div></div>

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-danger border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">TOTAL OUTSTANDING</div>
      <div style="font-size:24px;font-weight:700;color:#ef4444">ETB {{ number_format($total_balance,2) }}</div>
      <small class="text-muted">{{ $invoices->count() }} invoices</small>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-exclamation-circle mr-2 text-danger"></i>Outstanding Invoices</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Student</th>
          <th class="d-none d-md-table-cell">Class</th>
          <th class="d-none d-md-table-cell">Fee Type</th>
          <th>Net</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th>Due</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $inv)
        <tr>
          <td><strong>{{ $inv->student->name ?? '-' }}</strong></td>
          <td class="d-none d-md-table-cell">{{ optional($inv->fee_structure->my_class)->name ?? '-' }}</td>
          <td class="d-none d-md-table-cell">{{ optional($inv->fee_structure->category)->name ?? '-' }}</td>
          <td>{{ number_format($inv->net_amount,2) }}</td>
          <td class="text-success">{{ number_format($inv->amount_paid,2) }}</td>
          <td class="text-danger font-weight-bold">{{ number_format($inv->balance,2) }}</td>
          <td>@if($inv->status==='partial')<span class="badge badge-warning">Partial</span>@else<span class="badge badge-danger">Unpaid</span>@endif</td>
          <td>@if($inv->due_date && $inv->due_date < now()->toDateString())<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> {{ $inv->due_date }}</span>@else{{ $inv->due_date ?? '-' }}@endif</td>
          <td><a href="{{ route('fees.invoice', Qs::hash($inv->id)) }}" class="btn btn-success btn-xs"><i class="bi bi-cash-coin"></i></a></td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-success py-4"><i class="bi bi-check-circle mr-2"></i>All fees cleared!</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
  </div>
</div>
@endsection
