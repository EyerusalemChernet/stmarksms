@extends('layouts.master')
@section('page_title', 'Human Resources')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people-fill mr-2"></i>Staff Management</h5>
    <div>
        <a href="{{ route('hr.departments') }}" class="btn btn-sm btn-outline-primary mr-1"><i class="bi bi-building mr-1"></i>Departments</a>
        <a href="{{ route('hr.attendance') }}" class="btn btn-sm btn-outline-success mr-1"><i class="bi bi-clipboard-check mr-1"></i>Staff Attendance</a>
        <a href="{{ route('hr.workload') }}" class="btn btn-sm btn-outline-info"><i class="bi bi-bar-chart mr-1"></i>Workload</a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row mb-3">
    @php
        $byType = $staff->groupBy('user_type');
        $teachers   = $byType->get('teacher', collect());
        $admins     = $byType->get('admin', collect())->merge($byType->get('super_admin', collect()));
        $accountants = $byType->get('accountant', collect());
    @endphp
    <div class="col-md-3">
        <div class="card stat-card stat-primary text-white text-center p-3">
            <h3>{{ $staff->count() }}</h3><small>Total Staff</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-success text-white text-center p-3">
            <h3>{{ $teachers->count() }}</h3><small>Teachers</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-warning text-white text-center p-3">
            <h3>{{ $admins->count() }}</h3><small>Administrators</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-info text-white text-center p-3">
            <h3>{{ $accountants->count() }}</h3><small>Accountants</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">All Staff</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staff as $s)
                <tr>
                    <td>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <img src="{{ $s->photo }}" width="32" height="32" class="rounded-circle" style="object-fit:cover;">
                            {{ $s->name }}
                        </div>
                    </td>
                    <td><span class="badge badge-secondary">{{ ucwords(str_replace('_',' ',$s->user_type)) }}</span></td>
                    <td>{{ $s->email }}</td>
                    <td>{{ $s->phone ?? '-' }}</td>
                    <td>
                        @php $dept = $s->staff->first()->department->name ?? null; @endphp
                        @if($dept)
                            <span class="badge badge-info">{{ $dept }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('hr.show', $s->id) }}" class="btn btn-xs btn-primary"><i class="bi bi-eye"></i> View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
