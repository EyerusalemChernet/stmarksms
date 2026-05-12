@extends('layouts.master')
@section('page_title', $posting->title)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-briefcase mr-2"></i>{{ $posting->title }}</h5>
    <a href="{{ route('my.job_board') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Job Board
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Job Details</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <td class="font-weight-bold text-muted" style="width:30%">Department</td>
                        <td>{{ $posting->department?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold text-muted">Position</td>
                        <td>{{ $posting->position?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold text-muted">Employment Type</td>
                        <td>{{ ucwords(str_replace('_',' ',$posting->employment_type)) }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold text-muted">Vacancies</td>
                        <td>{{ $posting->vacancies }}</td>
                    </tr>
                    @if($posting->deadline)
                    <tr>
                        <td class="font-weight-bold text-muted">Application Deadline</td>
                        <td class="{{ $posting->deadline->isPast() ? 'text-danger' : '' }}">
                            {{ $posting->deadline->format('d M Y') }}
                            @if($posting->deadline->isPast())
                                <span class="badge badge-danger">Closed</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>

                @if($posting->description)
                <h6 class="font-weight-bold">Description</h6>
                <p class="text-muted">{{ $posting->description }}</p>
                @endif

                @if($posting->requirements)
                <h6 class="font-weight-bold">Requirements</h6>
                <p class="text-muted">{{ $posting->requirements }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($alreadyApplied)
                <div class="alert alert-success py-2">
                    <i class="bi bi-check-circle mr-1"></i>
                    You have already applied for this position.
                </div>
                @elseif($posting->deadline && $posting->deadline->isPast())
                <div class="alert alert-danger py-2">
                    <i class="bi bi-x-circle mr-1"></i>
                    The application deadline has passed.
                </div>
                @else
                <p class="text-muted small mb-3">
                    Interested in this role? Submit your application below.
                </p>
                <a href="{{ route('my.job_apply', $posting->id) }}"
                   class="btn btn-primary btn-block">
                    <i class="bi bi-send mr-1"></i>Apply Now
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
