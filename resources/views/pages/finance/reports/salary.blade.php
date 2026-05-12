@extends('layouts.master')
@section('page_title','Salary Report')
@section('content')
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Year</label>
      <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" min="2000">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Month</label>
      <select name="month" class="form-control form-control-sm">
        <option value="">All</option>
        @foreach($months as $i => $m)
          @if($i > 0)<option value="{{ $i }}" {{ $month==$i?'selected':'' }}>{{ $m }}</option>@endif
        @endforeach
      </select>
    </div>
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('reports.salary') }}" class="btn btn-light btn-sm">Reset</a>
      <a href="{{ route('reports.salary') }}?year={{ $year }}&month={{ $month }}&export=csv" class="btn btn-success btn-sm"><i class="bi bi-download mr-1"></i>CSV</a>
    </div>
  </form>
</div></div>

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">TOTAL GROSS</div>
      <div style="font-size:22px;font-weight:700;color:#3b82f6">ETB {{ number_format($total_gross,2) }}</div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">TOTAL DEDUCTIONS</div>
      <div style="font-size:22px;font-weight:700;color:#ef4444">ETB {{ number_format($total_ded,2) }}</div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">TOTAL NET</div>
      <div style="font-size:22px;font-weight:700;color:#22c55e">ETB {{ number_format($total_net,2) }}</div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-people mr-2"></i>Payroll Breakdown</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Staff</th>
          <th>Period</th>
          <th class="d-none d-md-table-cell">Gross (ETB)</th>
          <th class="d-none d-md-table-cell">Deductions (ETB)</th>
          <th>Net (ETB)</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($payrolls as $p)
        <tr>
          <td><strong>{{ $p->staff->name ?? '-' }}</strong></td>
          <td>{{ $months[$p->month] }} {{ $p->year }}</td>
          <td class="d-none d-md-table-cell">{{ number_format($p->gross_salary,2) }}</td>
          <td class="d-none d-md-table-cell text-danger">{{ number_format($p->total_deductions,2) }}</td>
          <td class="text-success font-weight-bold">{{ number_format($p->net_salary,2) }}</td>
          <td><a href="{{ route('payroll.show',$p->id) }}" class="btn btn-light btn-xs"><i class="bi bi-eye"></i></a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No payroll data found.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
  </div>
</div>
@endsection
