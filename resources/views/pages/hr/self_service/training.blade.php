@extends('layouts.master')
@section('page_title', 'My Training')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-mortarboard mr-2"></i>My Training & Development</h5>
    <a href="{{ route('my.profile') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>My Profile
    </a>
</div>

{{-- Stats --}}
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-3">
            <h3 class="text-primary mb-0">{{ $stats['total'] }}</h3>
            <small class="text-muted">Total Programs</small>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-3">
            <h3 class="text-success mb-0">{{ $stats['completed'] }}</h3>
            <small class="text-muted">Completed</small>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-3">
            <h3 class="text-info mb-0">{{ $stats['ongoing'] }}</h3>
            <small class="text-muted">Ongoing</small>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center p-3">
            <h3 class="text-warning mb-0">{{ $stats['hours'] }}h</h3>
            <small class="text-muted">Training Hours</small>
        </div>
    </div>
</div>

{{-- Training records --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Program</th>
                    <th>Category</th>
                    <th>Provider</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th class="text-center">Score</th>
                    <th>Certificate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trainings as $t)
                <tr>
                    <td>
                        <div class="font-weight-bold">{{ $t->program->title }}</div>
                        @if($t->program->duration_hours)
                            <small class="text-muted">{{ $t->program->duration_hours }}h</small>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $t->program->categoryBadgeClass() }}">{{ $t->program->categoryLabel() }}</span></td>
                    <td class="text-muted small">{{ $t->program->provider ?? '—' }}</td>
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
                            <i class="bi bi-patch-check text-success mr-1"></i>
                            {{ $t->certificate_number }}
                            @if($t->certificate_expiry)
                                <br><small class="{{ $t->isExpired() ? 'text-danger' : 'text-muted' }}">
                                    Exp: {{ $t->certificate_expiry->format('d M Y') }}
                                    @if($t->isExpired()) <strong>(Expired)</strong> @endif
                                </small>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-mortarboard" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">No training records yet.</p>
                        <small>Contact HR to enroll in a training program.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
