@extends('layouts.master')
@section('page_title','Fee Collection Report')
@section('content')

{{-- Filter Bar --}}
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
    <div><label style="font-size:11px;color:#64748b;display:block">Session</label>
      <input type="text" name="session" class="form-control form-control-sm" value="{{ $session }}" style="width:120px"></div>
    <div><label style="font-size:11px;color:#64748b;display:block">Class</label>
      <select name="class_id" class="form-control form-control-sm" style="width:140px">
        <option value="">All Classes</option>
        @foreach($classes as $c)<option value="{{ $c->id }}" {{ $class_id==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
      </select></div>
    <button class="btn btn-secondary btn-sm">Filter</button>
    <a href="{{ route('fees.report') }}" class="btn btn-light btn-sm">Reset</a>
    <a href="{{ route('fees.report') }}?session={{ $session }}&class_id={{ $class_id }}&export=csv" class="btn btn-success btn-sm ml-auto"><i class="bi bi-download mr-1"></i>Export CSV</a>
  </form>
</div></div>

{{-- Summary Cards --}}
<div class="row mb-3">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">TOTAL INVOICED</div>
      <div style="font-size:22px;font-weight:700;color:#3b82f6">ETB {{ number_format($total_invoiced,2) }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">COLLECTED</div>
      <div style="font-size:22px;font-weight:700;color:#22c55e">ETB {{ number_format($total_collected,2) }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">OUTSTANDING</div>
      <div style="font-size:22px;font-weight:700;color:#ef4444">ETB {{ number_format($total_balance,2) }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body py-3">
      <div style="font-size:11px;color:#94a3b8">COLLECTION RATE</div>
      <div style="font-size:22px;font-weight:700;color:#f59e0b">
        {{ $total_invoiced > 0 ? number_format(($total_collected/$total_invoiced)*100,1) : 0 }}%
      </div>
    </div></div>
  </div>
</div>

{{-- Status Badges --}}
<div class="row mb-3">
  <div class="col-md-6">
    <div class="card border-success"><div class="card-body py-2 d-flex justify-content-between align-items-center">
      <span style="font-size:13px;color:#64748b">Fully Paid</span>
      <span class="badge badge-success px-3 py-2" style="font-size:15px;">{{ $count_paid }}</span>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card border-danger"><div class="card-body py-2 d-flex justify-content-between align-items-center">
      <span style="font-size:13px;color:#64748b">Unpaid</span>
      <span class="badge badge-danger px-3 py-2" style="font-size:15px;">{{ $count_unpaid }}</span>
    </div></div>
  </div>
</div>

<div class="row">
  {{-- By Category --}}
  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags mr-2"></i>By Fee Category</h6></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:13px;">
          <thead class="thead-light"><tr><th>Category</th><th>Invoices</th><th>Invoiced</th><th>Collected</th><th>Balance</th></tr></thead>
          <tbody>
            @forelse($byCategory as $name => $row)
            <tr>
              <td><strong>{{ $name }}</strong></td>
              <td>{{ $row['count'] }}</td>
              <td>{{ number_format($row['invoiced'],2) }}</td>
              <td class="text-success">{{ number_format($row['collected'],2) }}</td>
              <td class="{{ $row['balance']>0?'text-danger':'text-success' }}">{{ number_format($row['balance'],2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- By Class --}}
  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-grid-3x3-gap mr-2"></i>By Class</h6></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:13px;">
          <thead class="thead-light"><tr><th>Class</th><th>Invoices</th><th>Invoiced</th><th>Collected</th><th>Balance</th></tr></thead>
          <tbody>
            @forelse($byClass as $name => $row)
            <tr>
              <td><strong>{{ $name }}</strong></td>
              <td>{{ $row['count'] }}</td>
              <td>{{ number_format($row['invoiced'],2) }}</td>
              <td class="text-success">{{ number_format($row['collected'],2) }}</td>
              <td class="{{ $row['balance']>0?'text-danger':'text-success' }}">{{ number_format($row['balance'],2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Monthly Trend --}}
<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-graph-up mr-2"></i>Monthly Collection ({{ now()->year }})</h6></div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0" style="font-size:13px;">
      <thead class="thead-light"><tr>
        @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $m)
        <th class="text-center">{{ $m }}</th>
        @endforeach
      </tr></thead>
      <tbody><tr>
        @for($i=1;$i<=12;$i++)
        <td class="text-center {{ isset($monthly[$i])&&$monthly[$i]>0?'text-success font-weight-bold':'' }}">
          {{ isset($monthly[$i]) ? number_format($monthly[$i],0) : '-' }}
        </td>
        @endfor
      </tr></tbody>
    </table>
  </div>
</div>

@endsection
