@extends('layouts.master')
@section('page_title', 'Internal Job Board')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-briefcase-fill mr-2"></i>Internal Job Board</h5>
    <a href="{{ route('my.profile') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>My Profile
    </a>
</div>

<p class="text-muted mb-3">
    Open positions at St. Mark School. Click a posting to view details and apply.
</p>

@forelse($postings as $p)
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="font-weight-bold mb-1">{{ $p->title }}</h6>
                <div style="gap:6px;" class="d-flex flex-wrap mb-2">
                    @if($p->department)
                        <span class="badge badge-info">{{ $p->department->name }}</span>
                    @endif
                    <span class="badge badge-light border">{{ ucwords(str_replace('_',' ',$p->employment_type)) }}</span>
                    <span class="badge badge-secondary">{{ $p->vacancies }} vacancy(ies)</span>
                    @if($p->deadline)
                        <span class="badge badge-{{ $p->deadline->isPast() ? 'danger' : 'warning' }}">
                            Deadline: {{ $p->deadline->format('d M Y') }}
                        </span>
                    @endif
                </div>
                @if($p->description)
                <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($p->description, 200) }}</p>
                @endif
            </div>
            <div class="ml-3 text-right" style="min-width:120px;">
                <a href="{{ route('my.job_posting', $p->id) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye mr-1"></i>View & Apply
                </a>
            </div>
        </div>
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-briefcase" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">No open positions at the moment.</p>
    </div>
</div>
@endforelse
@endsection
