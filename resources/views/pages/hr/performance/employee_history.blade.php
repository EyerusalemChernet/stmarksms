@extends('layouts.master')
@section('page_title', 'Performance History — ' . $employee->full_name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-clock-history mr-2"></i>Performance History
    </h5>
    <div>
        @if(\App\Helpers\Qs::userIsHRManager() || \App\Helpers\Qs::userIsTeamSA())
        <a href="{{ route('hr.performance.reviews.create') }}" class="btn btn-sm btn-success mr-1">
            <i class="bi bi-plus-circle mr-1"></i>New Review
        </a>
        <a href="{{ route('hr.performance.reviews') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>All Reviews
        </a>
        @endif
    </div>
</div>

{{-- Employee card --}}
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <img src="{{ $employee->photo_url }}" width="70" height="70"
                 class="rounded-circle mx-auto mb-2" style="object-fit:cover;">
            <h6 class="mb-0">{{ $employee->full_name }}</h6>
            <small class="text-muted">{{ $employee->employee_code }}</small>
            @if($employee->employmentDetails?->department)
                <span class="badge badge-info mt-1">{{ $employee->employmentDetails->department->name }}</span>
            @endif
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-primary text-white text-center p-3">
            <h3>{{ $reviews->count() }}</h3><small>Total Reviews</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-success text-white text-center p-3">
            <h3>{{ $reviews->count() > 0 ? number_format($reviews->avg('overall_score'), 2) : '—' }}</h3>
            <small>Average Score</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-info text-white text-center p-3">
            @php $latest = $reviews->first(); @endphp
            <h3>{{ $latest ? number_format($latest->overall_score, 2) : '—' }}</h3>
            <small>Latest Score</small>
        </div>
    </div>
</div>

@forelse($reviews as $rev)
<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <span class="font-weight-bold">{{ $rev->period }}</span>
            <span class="badge badge-{{ $rev->gradeBadgeClass() }} ml-2">{{ $rev->gradeLabel() }}</span>
            <span class="text-muted small ml-2">Reviewed by {{ $rev->reviewer?->name ?? '—' }}</span>
        </div>
        <div>
            <span class="h5 text-{{ $rev->gradeBadgeClass() }} mb-0">
                {{ number_format($rev->overall_score, 2) }} / 10
            </span>
            @if(\App\Helpers\Qs::userIsHRManager() || \App\Helpers\Qs::userIsTeamSA())
            <a href="{{ route('hr.performance.reviews.show', $rev->id) }}"
               class="btn btn-xs btn-primary ml-2"><i class="bi bi-eye"></i></a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Category</th>
                    <th class="text-center">Score</th>
                    <th>Performance Bar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rev->scores as $s)
                <tr>
                    <td>{{ $s->category->name }}</td>
                    <td class="text-center font-weight-bold">{{ number_format($s->score, 1) }} / 10</td>
                    <td>
                        <div class="progress" style="height:10px; min-width:120px;">
                            <div class="progress-bar bg-{{ $s->score >= 7 ? 'success' : ($s->score >= 4 ? 'warning' : 'danger') }}"
                                 style="width:{{ ($s->score / 10) * 100 }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($rev->notes)
        <div class="px-3 py-2 border-top">
            <small class="text-muted"><i class="bi bi-chat-left-text mr-1"></i>{{ $rev->notes }}</small>
        </div>
        @endif
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-star" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">No performance reviews yet.</p>
    </div>
</div>
@endforelse
@endsection
