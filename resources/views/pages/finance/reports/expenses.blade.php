@extends('layouts.master')
@section('page_title','Expense Report')
@section('content')
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-3 col-6 mb-2"><label style="font-size:11px;color:#64748b;display:block">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $date_from }}"></div>
    <div class="col-md-3 col-6 mb-2"><label style="font-size:11px;color:#64748b;display:block">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $date_to }}"></div>
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('reports.expenses') }}" class="btn btn-light btn-sm">Reset</a>
      <a href="{{ route('reports.expenses') }}?date_from={{ $date_from }}&date_to={{ $date_to }}&export=csv" class="btn btn-success btn-sm"><i class="bi bi-download mr-1"></i>CSV</a>
    </div>
  </form>
</div></div>

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">TOTAL EXPENSES</div>
      <div style="font-size:24px;font-weight:700;color:#ef4444">ETB {{ number_format($total,2) }}</div>
      <small class="text-muted">{{ $date_from }} to {{ $date_to }}</small>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags mr-2"></i>By Expense Category</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light"><tr><th>Category</th><th>Transactions</th><th>Amount (ETB)</th></tr></thead>
      <tbody>
        @forelse($byCategory as $name => $row)
        <tr>
          <td><strong>{{ $name }}</strong></td>
          <td>{{ $row['count'] }}</td>
          <td class="text-danger font-weight-bold">ETB {{ number_format($row['total'],2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-center text-muted py-4">No expense data for this period.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
  </div>
</div>
@endsection
