@extends('layouts.master')
@section('page_title', 'Human Resources')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people-fill mr-2"></i>Employees</h5>
    <div>
        <a href="{{ route('hr.employees.create') }}" class="btn btn-sm btn-success mr-2">
            <i class="bi bi-person-plus mr-1"></i>Create Employee
        </a>
        <a href="{{ route('hr.departments') }}" class="btn btn-sm btn-outline-primary mr-1"><i class="bi bi-building mr-1"></i>Departments</a>
        <a href="{{ route('hr.positions') }}"   class="btn btn-sm btn-outline-secondary mr-1"><i class="bi bi-briefcase mr-1"></i>Positions</a>
        <a href="{{ route('hr.shifts') }}"      class="btn btn-sm btn-outline-dark mr-1"><i class="bi bi-clock mr-1"></i>Shifts</a>
        <a href="{{ route('hr.attendance') }}"  class="btn btn-sm btn-outline-success mr-1"><i class="bi bi-clipboard-check mr-1"></i>Attendance</a>
        <a href="{{ route('hr.payroll') }}"     class="btn btn-sm btn-outline-warning mr-1"><i class="bi bi-cash-stack mr-1"></i>Payroll</a>
        <a href="{{ route('hr.workload') }}"    class="btn btn-sm btn-outline-info"><i class="bi bi-bar-chart mr-1"></i>Workload</a>
    </div>
</div>

{{-- Status filter tabs --}}
<ul class="nav nav-tabs mb-3">
    @foreach(['active'=>['success','Active'],'on_leave'=>['warning','On Leave'],'suspended'=>['danger','Suspended'],'terminated'=>['dark','Terminated'],'all'=>['secondary','All']] as $s=>[$colour,$label])
    <li class="nav-item">
        <a class="nav-link {{ $status === $s ? 'active' : '' }}"
           href="{{ route('hr.index', array_merge(request()->query(), ['status' => $s])) }}">
            {{ $label }}
            <span class="badge badge-{{ $colour }} ml-1">
                {{ $s === 'all' ? array_sum($statusCounts) : ($statusCounts[$s] ?? 0) }}
            </span>
        </a>
    </li>
    @endforeach
</ul>

{{-- Search + Export bar --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.index') }}" method="GET" class="form-inline mb-0 flex-grow-1" style="gap:6px;">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="min-width:220px;"
                   placeholder="Search name, email, code, phone…">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search mr-1"></i>Search
            </button>
            @if($search)
            <a href="{{ route('hr.index', ['status' => $status]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x mr-1"></i>Clear
            </a>
            @endif
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.index', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger">
                <i class="bi bi-file-pdf mr-1"></i>PDF
            </a>
            <a href="{{ route('hr.index', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success">
                <i class="bi bi-file-spreadsheet mr-1"></i>CSV
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            {{ ucwords(str_replace('_',' ',$status)) }} Employees
            <span class="badge badge-secondary ml-1">{{ $employees->count() }}</span>
            @if($search)
                <span class="badge badge-warning ml-1">Filtered: "{{ $search }}"</span>
            @endif
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Salary</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                @php
                    $ed  = $emp->employmentDetails;
                    $sal = $emp->currentSalary;
                @endphp
                <tr>
                    <td><span class="badge badge-light border text-monospace">{{ $emp->employee_code }}</span></td>
                    <td>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <img src="{{ $emp->photo_url }}" width="32" height="32"
                                 class="rounded-circle" style="object-fit:cover;">
                            <div>
                                <div class="font-weight-bold">{{ $emp->full_name }}</div>
                                @if($emp->user)
                                    <small class="text-muted">{{ ucwords(str_replace('_',' ',$emp->user->user_type)) }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($ed && $ed->department)
                            <span class="badge badge-info">{{ $ed->department->name }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($ed && $ed->position)
                            <span class="badge badge-primary">{{ $ed->position->name }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($ed)
                            <span class="badge badge-light border">{{ $ed->employmentTypeLabel() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $emp->statusBadgeClass() }}">
                            {{ ucwords(str_replace('_',' ',$emp->status)) }}
                        </span>
                    </td>
                    <td>
                        @if($sal)
                            <span class="text-success font-weight-bold">
                                {{ $sal->currency }} {{ number_format($sal->amount, 2) }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('hr.show', $emp->id) }}"
                           class="btn btn-xs btn-primary" title="View"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('hr.profile.edit', $emp->id) }}"
                           class="btn btn-xs btn-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        @if($search)
                            No employees found matching "{{ $search }}".
                        @else
                            No {{ str_replace('_',' ',$status) }} employees found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
