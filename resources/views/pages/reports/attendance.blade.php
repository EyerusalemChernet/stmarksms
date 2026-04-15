@extends('layouts.master')
@section('page_title', 'Attendance Reports')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Attendance Reports — {{ $year }}</h5>
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary"><i class="icon-arrow-left8 mr-1"></i> Back to Reports</a>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card card-body bg-info-400 text-white text-center">
            <h3>{{ $total_sessions }}</h3><small>Total Attendance Sessions</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Attendance Rate Per Class</h6></div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr><th>Class</th><th>Total Records</th><th>Present</th><th>Attendance Rate</th></tr>
            </thead>
            <tbody>
                @foreach($classes as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->att_total }}</td>
                    <td>{{ $c->att_present }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 mr-2" style="height:16px;">
                                <div class="progress-bar {{ $c->att_pct >= 75 ? 'bg-success' : 'bg-danger' }}"
                                     style="width:{{ $c->att_pct }}%">{{ $c->att_pct }}%</div>
                            </div>
                            <span>{{ $c->att_pct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
