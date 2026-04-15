@extends('layouts.master')
@section('page_title', 'Finance Reports')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-cash-stack mr-2"></i>Finance Reports — {{ $year }}</h5>
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to Reports</a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.finance') }}" class="form-inline" style="gap:8px;">
            <select name="class_id" class="form-control form-control-sm">
                <option value="">All Classes</option>
                @foreach($allClasses as $c)
                    <option value="{{ $c->id }}" {{ $class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel mr-1"></i>Filter</button>
            <a href="{{ route('reports.finance', array_merge(request()->query(), ['export'=>'pdf'])) }}" class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('reports.finance', array_merge(request()->query(), ['export'=>'csv'])) }}" class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card stat-card stat-success text-white text-center p-3">
            <h3>ETB {{ number_format($total_collected) }}</h3><small>Total Collected</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-danger text-white text-center p-3">
            <h3>ETB {{ number_format($total_outstanding) }}</h3><small>Outstanding Balance</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-primary text-white text-center p-3">
            <h3>{{ $students_paid }}</h3><small>Students Fully Paid</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-warning text-white text-center p-3">
            <h3>{{ $students_unpaid }}</h3><small>Students With Balance</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Payments Per Class</h6></div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr><th>Class</th><th>Amount Collected</th><th>Unpaid Records</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($classes as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>ETB {{ number_format($c->paid_amount) }}</td>
                    <td>{{ $c->unpaid_count }}</td>
                    <td>
                        @if($c->unpaid_count > 0)
                            <span class="badge badge-danger">{{ $c->unpaid_count }} unpaid</span>
                        @else
                            <span class="badge badge-success">All Cleared</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
