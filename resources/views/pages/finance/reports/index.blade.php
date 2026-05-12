@extends('layouts.master')
@section('page_title','Financial Reports')
@section('content')
<div class="row">
  @php
  $reports = [
    ['route'=>'reports.income',      'icon'=>'bi-graph-up-arrow',    'color'=>'success', 'title'=>'Income Report',       'desc'=>'Fee payments collected by category and date range'],
    ['route'=>'reports.expenses',    'icon'=>'bi-graph-down-arrow',  'color'=>'danger',  'title'=>'Expense Report',      'desc'=>'School expenses by category and date range'],
    ['route'=>'reports.profit_loss', 'icon'=>'bi-bar-chart-line',    'color'=>'primary', 'title'=>'Profit / Loss',       'desc'=>'Income vs expenses summary with monthly breakdown'],
    ['route'=>'reports.outstanding', 'icon'=>'bi-exclamation-circle','color'=>'warning', 'title'=>'Outstanding Fees',    'desc'=>'Unpaid and partial invoices by class and session'],
    ['route'=>'reports.salary',      'icon'=>'bi-people',            'color'=>'info',    'title'=>'Salary Report',       'desc'=>'Monthly payroll cost breakdown per staff member'],
  ];
  @endphp
  @foreach($reports as $r)
  <div class="col-md-4 mb-3">
    <a href="{{ route($r['route']) }}" class="text-decoration-none">
      <div class="card h-100 border-{{ $r['color'] }} border-left-3">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <i class="bi {{ $r['icon'] }} text-{{ $r['color'] }} mr-2" style="font-size:22px;"></i>
            <h6 class="mb-0">{{ $r['title'] }}</h6>
          </div>
          <p class="text-muted mb-0" style="font-size:13px;">{{ $r['desc'] }}</p>
        </div>
      </div>
    </a>
  </div>
  @endforeach
</div>
@endsection
