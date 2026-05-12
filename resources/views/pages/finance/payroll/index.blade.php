@extends('layouts.master')
@section('page_title','Payroll')
@section('content')

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3 d-flex justify-content-between align-items-center">
      <div><div style="font-size:11px;color:#94a3b8">TOTAL GROSS</div>
      <div style="font-size:20px;font-weight:700;color:#3b82f6">ETB {{ number_format($total_gross,2) }}</div></div>
      <i class="bi bi-people text-primary" style="font-size:28px;opacity:.4"></i>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3 d-flex justify-content-between align-items-center">
      <div><div style="font-size:11px;color:#94a3b8">TOTAL NET</div>
      <div style="font-size:20px;font-weight:700;color:#22c55e">ETB {{ number_format($total_net,2) }}</div></div>
      <i class="bi bi-cash-coin text-success" style="font-size:28px;opacity:.4"></i>
    </div></div>
  </div>
</div>

<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Month</label>
      <select name="month" class="form-control form-control-sm">
        <option value="">All</option>
        @foreach(['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $m)
          @if($i > 0)<option value="{{ $i }}" {{ request('month')==$i?'selected':'' }}>{{ $m }}</option>@endif
        @endforeach
      </select>
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Year</label>
      <input type="number" name="year" class="form-control form-control-sm" value="{{ request('year', now()->year) }}" min="2000">
    </div>
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm">Reset</a>
    </div>
  </form>
</div></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0"><i class="bi bi-people mr-2"></i>Payroll Records ({{ $payrolls->total() }})</h6>
    <div class="d-flex gap-2">
      <a href="{{ route('payroll.csv') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success btn-sm"><i class="bi bi-download mr-1"></i>CSV</a>
      <a href="{{ route('salary.index') }}" class="btn btn-light btn-sm"><i class="bi bi-sliders mr-1"></i>Salary Structures</a>
      <a href="{{ route('payroll.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Process Payroll</a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Staff</th>
          <th>Period</th>
          <th class="d-none d-md-table-cell">Gross</th>
          <th class="d-none d-md-table-cell">Deductions</th>
          <th>Net</th>
          <th>Processed</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @php $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
        @forelse($payrolls as $p)
        <tr>
          <td><strong>{{ $p->staff->name ?? '-' }}</strong></td>
          <td>{{ $months[$p->month] }} {{ $p->year }}</td>
          <td class="d-none d-md-table-cell">ETB {{ number_format($p->gross_salary,2) }}</td>
          <td class="d-none d-md-table-cell text-danger">ETB {{ number_format($p->total_deductions,2) }}</td>
          <td class="text-success font-weight-bold">ETB {{ number_format($p->net_salary,2) }}</td>
          <td>{{ $p->processed_at ? $p->processed_at->format('d M Y') : '-' }}</td>
          <td><a href="{{ route('payroll.show',$p->id) }}" class="btn btn-info btn-xs"><i class="bi bi-eye"></i></a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No payroll records found.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted">Showing {{ $payrolls->firstItem() }}–{{ $payrolls->lastItem() }} of {{ $payrolls->total() }}</small>
      {{ $payrolls->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
