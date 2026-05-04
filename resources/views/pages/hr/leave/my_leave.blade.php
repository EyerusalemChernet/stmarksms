@extends('layouts.master')
@section('page_title', 'My Leave')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-calendar-heart mr-2"></i>My Leave</h5>
    <a href="{{ route('my.leave.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle mr-1"></i>Apply for Leave
    </a>
</div>

{{-- Balance cards --}}
<div class="row mb-3">
    @foreach($summary as $type => $bal)
    @if($bal['entitled'] > 0 || in_array($type, ['annual','sick']))
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card text-center p-2 h-100">
            <div class="font-weight-bold" style="font-size:1.4rem;
                color:{{ $bal['available'] > 0 ? '#28a745' : '#dc3545' }}">
                {{ $bal['available'] }}
            </div>
            <small class="text-muted d-block">{{ $bal['label'] }}</small>
            <small class="text-muted" style="font-size:10px;">
                {{ $bal['used'] }} used / {{ $bal['entitled'] }} entitled
                @if($bal['pending'] > 0)
                    <br><span class="text-warning">{{ $bal['pending'] }} pending</span>
                @endif
            </small>
        </div>
    </div>
    @endif
    @endforeach
</div>

{{-- Year filter --}}
<div class="d-flex align-items-center mb-3" style="gap:8px;">
    <form action="{{ route('my.leave.index') }}" method="GET" class="form-inline mb-0">
        <label class="font-weight-bold mr-2">Year:</label>
        <select name="year" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
            @foreach(range(now()->year, now()->year - 2) as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>
    <span class="text-muted small">{{ $requests->count() }} request(s) in {{ $year }}</span>
</div>

{{-- Requests table --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Leave Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-center">Days</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td><span class="badge badge-info">{{ $req->leaveTypeLabel() }}</span></td>
                    <td>{{ $req->start_date->format('d M Y') }}</td>
                    <td>{{ $req->end_date->format('d M Y') }}</td>
                    <td class="text-center font-weight-bold">{{ $req->days_requested }}</td>
                    <td>
                        <span class="badge badge-{{ $req->statusBadgeClass() }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $req->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('my.leave.show', $req->id) }}"
                           class="btn btn-xs btn-primary"><i class="bi bi-eye"></i></a>

                        @if($req->isPending())
                        <form action="{{ route('my.leave.cancel', $req->id) }}"
                              method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-outline-danger"
                                    title="Cancel"
                                    onclick="return confirm('Cancel this leave request?')">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No leave requests for {{ $year }}.
                        <a href="{{ route('my.leave.create') }}">Apply now</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
