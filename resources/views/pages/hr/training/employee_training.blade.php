@extends('layouts.master')
@section('page_title', 'Training — ' . $employee->full_name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-mortarboard mr-2"></i>Training History
        <span class="badge badge-light border text-monospace ml-1">{{ $employee->employee_code }}</span>
    </h5>
    <div style="gap:6px;" class="d-flex">
        <a href="{{ route('hr.show', $employee->id) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-person mr-1"></i>Profile
        </a>
        <a href="{{ route('hr.training.enrollments') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left mr-1"></i>All Enrollments
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="card text-center p-3">
            <img src="{{ $employee->photo_url }}" width="55" height="55"
                 class="rounded-circle mx-auto mb-1" style="object-fit:cover;">
            <small class="font-weight-bold">{{ $employee->full_name }}</small>
            <small class="text-muted d-block">{{ $employee->employmentDetails?->department?->name ?? '—' }}</small>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card stat-card stat-primary text-white text-center p-3">
            <h3>{{ $stats['total'] }}</h3><small>Total Programs</small>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card stat-card stat-success text-white text-center p-3">
            <h3>{{ $stats['completed'] }}</h3><small>Completed</small>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card stat-card stat-info text-white text-center p-3">
            <h3>{{ $stats['hours'] }}h</h3><small>Training Hours</small>
        </div>
    </div>
</div>

{{-- Enroll form --}}
<div class="card mb-3">
    <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-plus-circle mr-1"></i>Enroll in Training</h6></div>
    <div class="card-body">
        <form action="{{ route('hr.training.enroll') }}" method="POST" class="form-inline flex-wrap" style="gap:8px;">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <select name="training_program_id" class="form-control form-control-sm" style="min-width:220px;" required>
                <option value="">— Select Program —</option>
                @foreach($programs as $p)
                    <option value="{{ $p->id }}">{{ $p->title }}</option>
                @endforeach
            </select>
            <input type="date" name="start_date" class="form-control form-control-sm" placeholder="Start date">
            <input type="date" name="end_date" class="form-control form-control-sm" placeholder="End date">
            <button type="submit" class="btn btn-sm btn-success">
                <i class="bi bi-plus-circle mr-1"></i>Enroll
            </button>
        </form>
    </div>
</div>

{{-- Training history --}}
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">Training Records</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Program</th>
                    <th>Category</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th class="text-center">Score</th>
                    <th>Certificate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trainings as $t)
                <tr>
                    <td>
                        <div class="font-weight-bold">{{ $t->program->title }}</div>
                        @if($t->program->provider)
                            <small class="text-muted">{{ $t->program->provider }}</small>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $t->program->categoryBadgeClass() }}">{{ $t->program->categoryLabel() }}</span></td>
                    <td class="text-muted small">{{ $t->start_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-muted small">{{ $t->end_date?->format('d M Y') ?? '—' }}</td>
                    <td><span class="badge badge-{{ $t->statusBadgeClass() }}">{{ $t->statusLabel() }}</span></td>
                    <td class="text-center">
                        @if($t->score !== null)
                            <span class="{{ $t->passed ? 'text-success' : 'text-danger' }} font-weight-bold">
                                {{ $t->score }}%
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">
                        @if($t->certificate_number)
                            <i class="bi bi-patch-check text-success mr-1"></i>{{ $t->certificate_number }}
                            @if($t->certificate_expiry)
                                <br><small class="{{ $t->isExpired() ? 'text-danger' : 'text-muted' }}">
                                    Exp: {{ $t->certificate_expiry->format('d M Y') }}
                                </small>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('hr.training.enrollments.destroy', $t->id) }}"
                              method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger"
                                    onclick="return confirm('Remove this training record?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No training records yet. Enroll above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
