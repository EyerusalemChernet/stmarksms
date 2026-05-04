@extends('layouts.master')
@section('page_title', 'Leave Balances')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-bar-chart-steps mr-2"></i>Leave Balances</h5>
    <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.leave.balances') }}" method="GET" class="form-inline mb-0" style="gap:6px;">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="width:180px;"
                   placeholder="Search employee…">
            <label class="font-weight-bold mb-0">Year:</label>
            <input type="number" name="year" value="{{ $year }}" min="2020" max="2099"
                   class="form-control form-control-sm" style="width:80px;">
            <button type="submit" class="btn btn-sm btn-primary">View</button>
            @if($search)
            <a href="{{ route('hr.leave.balances', ['year'=>$year]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
            @endif
        </form>
        <a href="{{ route('hr.leave.balances', array_merge(request()->query(), ['export'=>'pdf'])) }}"
           class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
        <a href="{{ route('hr.leave.balances', array_merge(request()->query(), ['export'=>'csv'])) }}"
           class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th class="text-center">Annual</th>
                        <th class="text-center">Sick</th>
                        <th class="text-center">Maternity</th>
                        <th class="text-center">Paternity</th>
                        <th class="text-center">Unpaid</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                    @php $empBalances = $allBalances->get($emp->id, collect()); @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center" style="gap:6px;">
                                <img src="{{ $emp->photo_url }}" width="26" height="26"
                                     class="rounded-circle" style="object-fit:cover;">
                                <div>
                                    <a href="{{ route('hr.leave.employee_balance', $emp->id) }}?year={{ $year }}">
                                        {{ $emp->full_name }}
                                    </a>
                                    @if($emp->employmentDetails?->department)
                                        <br><small class="text-muted">{{ $emp->employmentDetails->department->name }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @foreach(['annual','sick','maternity','paternity','unpaid'] as $type)
                        @php $bal = $empBalances->get($type); @endphp
                        <td class="text-center">
                            @if($bal)
                                <span class="{{ $bal->available > 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                                    {{ $bal->available }}
                                </span>
                                <small class="text-muted d-block">/ {{ $bal->entitled }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endforeach
                        <td>
                            <a href="{{ route('hr.leave.employee_balance', $emp->id) }}?year={{ $year }}"
                               class="btn btn-xs btn-outline-primary">Detail</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="text-muted small mt-2">Numbers show: <strong>available / entitled</strong> days for {{ $year }}.</p>
@endsection
