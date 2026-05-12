@extends('layouts.master')
@section('page_title','Profit / Loss')
@section('content')
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-3 col-6 mb-2"><label style="font-size:11px;color:#64748b;display:block">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $date_from }}"></div>
    <div class="col-md-3 col-6 mb-2"><label style="font-size:11px;color:#64748b;display:block">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $date_to }}"></div>
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('reports.profit_loss') }}" class="btn btn-light btn-sm">Reset</a>
      <a href="{{ route('reports.profit_loss') }}?export=csv" class="btn btn-success btn-sm"><i class="bi bi-download mr-1"></i>CSV</a>
    </div>
  </form>
</div></div>

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">INCOME</div>
      <div style="font-size:22px;font-weight:700;color:#22c55e">ETB {{ number_format($income,2) }}</div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">EXPENSES</div>
      <div style="font-size:22px;font-weight:700;color:#ef4444">ETB {{ number_format($expenses,2) }}</div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">NET PROFIT / LOSS</div>
      <div style="font-size:22px;font-weight:700;color:{{ $profit >= 0 ? '#22c55e' : '#ef4444' }}">
        ETB {{ number_format(abs($profit),2) }} {{ $profit >= 0 ? '▲' : '▼' }}
      </div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-bar-chart-line mr-2"></i>Monthly Breakdown ({{ now()->year }})</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-sm mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr><th>Month</th><th>Income (ETB)</th><th>Expenses (ETB)</th><th>Profit/Loss (ETB)</th></tr>
      </thead>
      <tbody>
        @php $mnames = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
        @foreach($monthly as $m => $row)
        <tr>
          <td>{{ $mnames[$m] }}</td>
          <td class="text-success">{{ number_format($row['income'],2) }}</td>
          <td class="text-danger">{{ number_format($row['expenses'],2) }}</td>
          <td class="{{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
            {{ $row['profit'] >= 0 ? '+' : '' }}{{ number_format($row['profit'],2) }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
  </div>
</div>
@endsection
