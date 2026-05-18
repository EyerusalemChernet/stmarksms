@extends('layouts.master')
@section('page_title','Fee Invoices')
@section('content')

<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Search</label>
      <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Student / Invoice#">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Class</label>
      <select name="class_id" class="form-control form-control-sm">
        <option value="">All Classes</option>
        @foreach($classes as $c)<option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Status</label>
      <select name="status" class="form-control form-control-sm">
        <option value="">All</option>
        <option value="unpaid" {{ request('status')==='unpaid'?'selected':'' }}>Unpaid</option>
        <option value="partial" {{ request('status')==='partial'?'selected':'' }}>Partial</option>
        <option value="paid" {{ request('status')==='paid'?'selected':'' }}>Paid</option>
      </select>
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Session</label>
      <input type="text" name="session_filter" class="form-control form-control-sm" value="{{ $session }}">
    </div>
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('fees.invoices') }}" class="btn btn-light btn-sm">Reset</a>
    </div>
  </form>
</div></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoices ({{ $invoices->total() }})</h6>
    <a href="{{ route('fees.structures') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Assign Fee</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Invoice</th>
          <th>Student</th>
          <th class="d-none d-md-table-cell">Class</th>
          <th class="d-none d-md-table-cell">Fee</th>
          <th>Net</th>
          <th class="d-none d-md-table-cell">Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th class="d-none d-md-table-cell">Due</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $inv)
        <tr>
          <td><code style="font-size:11px">{{ $inv->invoice_no }}</code></td>
          <td>{{ $inv->student->name ?? '-' }}</td>
          <td class="d-none d-md-table-cell">{{ optional($inv->fee_structure->my_class)->name ?? '-' }}</td>
          <td class="d-none d-md-table-cell">{{ optional($inv->fee_structure->category)->name ?? '-' }}</td>
          <td>{{ number_format($inv->net_amount,2) }}</td>
          <td class="text-success d-none d-md-table-cell">{{ number_format($inv->amount_paid,2) }}</td>
          <td class="{{ $inv->balance>0?'text-danger':'text-success' }}">{{ number_format($inv->balance,2) }}</td>
          <td>
            @if($inv->status==='paid')<span class="badge badge-success">Paid</span>
            @elseif($inv->status==='partial')<span class="badge badge-warning">Partial</span>
            @else<span class="badge badge-danger">Unpaid</span>@endif
          </td>
          <td class="d-none d-md-table-cell">{{ $inv->due_date ?? '-' }}</td>
          <td><a href="{{ route('fees.invoice', Qs::hash($inv->id)) }}" class="btn btn-info btn-xs"><i class="bi bi-eye"></i></a></td>
        </tr>
        @empty
        <tr><td colspan="10" class="text-center text-muted py-4">No invoices found.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted">
        @if($invoices->total() > 0)Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }}@endif
      </small>
      {{ $invoices->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
