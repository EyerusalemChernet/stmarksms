@extends('layouts.master')
@section('page_title', 'Student Reports')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people mr-2"></i>Student Reports — {{ $year }}</h5>
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to Reports</a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.students') }}" class="form-inline" style="gap:8px;">
            <select name="class_id" class="form-control form-control-sm">
                <option value="">All Classes</option>
                @foreach($allClasses as $c)
                    <option value="{{ $c->id }}" {{ $class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel mr-1"></i>Filter</button>
            <a href="{{ route('reports.students', array_merge(request()->query(), ['export'=>'pdf'])) }}" class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('reports.students', array_merge(request()->query(), ['export'=>'csv'])) }}" class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card stat-card stat-primary text-white text-center p-3">
            <h3>{{ $total }}</h3><small>Total Active Students</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-success text-white text-center p-3">
            <h3>{{ $male }}</h3><small>Male Students</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-warning text-white text-center p-3">
            <h3>{{ $female }}</h3><small>Female Students</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-info text-white text-center p-3">
            <h3>{{ $promotions->get('P', 0) }}</h3><small>Promoted This Session</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Students Per Class</h6></div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0 datatable-basic">
                    <thead class="thead-light">
                        <tr><th>Class</th><th>Active Students</th><th>Distribution</th></tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->active_count }}</td>
                            <td>
                                <div class="progress" style="height:16px;">
                                    <div class="progress-bar bg-primary" style="width:{{ $total > 0 ? round(($c->active_count/$total)*100) : 0 }}%">
                                        {{ $total > 0 ? round(($c->active_count/$total)*100) : 0 }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Promotion Statistics</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td>Promoted</td><td><span class="badge badge-success">{{ $promotions->get('P', 0) }}</span></td></tr>
                    <tr><td>Not Promoted</td><td><span class="badge badge-warning">{{ $promotions->get('D', 0) }}</span></td></tr>
                    <tr><td>Graduated</td><td><span class="badge badge-info">{{ $promotions->get('G', 0) }}</span></td></tr>
                </table>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Gender Breakdown</h6></div>
            <div class="card-body">
                <div class="d-flex justify-content-around text-center">
                    <div><h4 class="text-primary">{{ $male }}</h4><small>Male</small></div>
                    <div><h4 class="text-danger">{{ $female }}</h4><small>Female</small></div>
                </div>
                @if($total > 0)
                <div class="progress mt-2" style="height:12px;">
                    <div class="progress-bar bg-primary" style="width:{{ round(($male/$total)*100) }}%" title="Male"></div>
                    <div class="progress-bar bg-danger" style="width:{{ round(($female/$total)*100) }}%" title="Female"></div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
