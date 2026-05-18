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
            <small class="text-muted">My Programs</small>
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

{{-- My enrolled training records --}}
<div class="card mb-4">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0"><i class="bi bi-list-check mr-1"></i>My Training Records</h6>
    </div>
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
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-mortarboard" style="font-size:1.5rem;"></i>
                        <p class="mt-1 mb-0">You haven't been enrolled in any training yet.</p>
                        <small>Browse available programs below and contact HR to enroll.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Available programs (not yet enrolled) --}}
@if($availablePrograms->count() > 0)
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-grid mr-1 text-primary"></i>Available Training Programs
            <span class="badge badge-secondary ml-1">{{ $availablePrograms->count() }}</span>
        </h6>
        <small class="text-muted">Contact HR to enroll in any of these programs.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Program</th>
                    <th>Category</th>
                    <th>Provider</th>
                    <th class="text-center">Duration</th>
                    <th class="text-center">Mandatory</th>
                </tr>
            </thead>
            <tbody>
                @foreach($availablePrograms as $p)
                <tr>
                    <td>
                        <div class="font-weight-bold">{{ $p->title }}</div>
                        @if($p->description)
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($p->description, 100) }}</small>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $p->categoryBadgeClass() }}">{{ $p->categoryLabel() }}</span></td>
                    <td class="text-muted small">{{ $p->provider ?? '—' }}</td>
                    <td class="text-center text-muted small">{{ $p->duration_hours ? $p->duration_hours.'h' : '—' }}</td>
                    <td class="text-center">
                        @if($p->is_mandatory)
                            <span class="badge badge-danger">Required</span>
                        @else
                            <span class="text-muted small">Optional</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-light py-2">
        <small class="text-muted">
            <i class="bi bi-info-circle mr-1"></i>
            To enroll in a program, contact your HR manager or send a message via
            <a href="{{ route('compose') }}">Compose</a>.
        </small>
    </div>
</div>
@endif

@endsection
