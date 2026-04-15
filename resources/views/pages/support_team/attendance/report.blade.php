@extends('layouts.master')
@section('page_title', 'Attendance Report')
@section('content')
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title">Attendance Report — {{ $sr->user->name }}</h6></div>
    <div class="card-body">
        @if($blocked)
            <div class="alert alert-danger"><strong>Warning:</strong> This student's exam results are blocked due to low attendance.</div>
        @endif
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-success text-white text-center p-3">
                    <h4>{{ $present }}</h4><small>Days Present</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white text-center p-3">
                    <h4>{{ $absent }}</h4><small>Days Absent</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white text-center p-3">
                    <h4>{{ $total }}</h4><small>Total Sessions</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card {{ $pct >= 75 ? 'bg-success' : 'bg-warning' }} text-white text-center p-3">
                    <h4>{{ $pct }}%</h4><small>Attendance Rate</small>
                </div>
            </div>
        </div>
        <table class="table table-bordered table-sm datatable-basic">
            <thead class="thead-light">
                <tr><th>#</th><th>Date</th><th>Class</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($records as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->session->date ?? '-' }}</td>
                    <td>{{ $r->session->my_class->name ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $r->status === 'present' ? 'success' : ($r->status === 'late' ? 'warning' : 'danger') }}">
                            {{ ucfirst($r->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
