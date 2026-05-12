@extends('layouts.master')
@section('page_title', 'Leave Balance — ' . $employee->full_name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-person-check mr-2"></i>Leave Balance — {{ $employee->full_name }}
    </h5>
    <div>
        <a href="{{ route('hr.leave.balances') }}?year={{ $year }}" class="btn btn-sm btn-secondary mr-1">
            <i class="bi bi-arrow-left mr-1"></i>All Balances
        </a>
        <a href="{{ route('hr.show', $employee->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-person mr-1"></i>Profile
        </a>
    </div>
</div>

{{-- Year selector --}}
<form action="{{ route('hr.leave.employee_balance', $employee->id) }}" method="GET" class="form-inline mb-3">
    <label class="mr-2 font-weight-bold">Year:</label>
    <input type="number" name="year" value="{{ $year }}" min="2020" max="2099"
           class="form-control form-control-sm mr-2" style="width:80px;">
    <button type="submit" class="btn btn-sm btn-primary">View</button>
</form>

{{-- Balance summary cards --}}
<div class="row mb-3">
    @foreach($summary as $type => $data)
    <div class="col-md-2 mb-2">
        <div class="card text-center p-2 h-100">
            <div class="font-weight-bold small mb-1">{{ $data['label'] }}</div>
            <h4 class="{{ $data['available'] > 0 ? 'text-success' : 'text-danger' }} mb-0">
                {{ $data['available'] }}
            </h4>
            <small class="text-muted">available</small>
            <hr class="my-1">
            <div class="d-flex justify-content-around small">
                <span class="text-muted">{{ $data['entitled'] }} entitled</span>
            </div>
            <div class="d-flex justify-content-around small">
                <span class="text-danger">{{ $data['used'] }} used</span>
                @if($data['pending'] > 0)
                    <span class="text-warning">{{ $data['pending'] }} pending</span>
                @endif
            </div>
            @if(!$data['is_paid'])
                <span class="badge badge-secondary mt-1" style="font-size:9px;">Unpaid</span>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Leave request history --}}
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Leave History — {{ $year }}</h6>
        <a href="{{ route('hr.leave.requests.create') }}" class="btn btn-xs btn-primary">
            <i class="bi bi-plus mr-1"></i>New Request
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th><th>Type</th><th>From</th><th>To</th>
                    <th class="text-center">Days</th><th>Status</th><th>Reason</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td class="text-muted small">{{ $req->id }}</td>
                    <td><span class="badge badge-info">{{ $req->leaveTypeLabel() }}</span></td>
                    <td>{{ $req->start_date->format('d M Y') }}</td>
                    <td>{{ $req->end_date->format('d M Y') }}</td>
                    <td class="text-center font-weight-bold">{{ $req->days_requested }}</td>
                    <td>
                        <span class="badge badge-{{ $req->statusBadgeClass() }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ Str::limit($req->reason ?? '—', 40) }}</td>
                    <td>
                        <a href="{{ route('hr.leave.requests.show', $req->id) }}"
                           class="btn btn-xs btn-outline-primary"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No leave requests for {{ $year }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
