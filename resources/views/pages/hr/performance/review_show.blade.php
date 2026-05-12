@extends('layouts.master')
@section('page_title', 'Performance Review')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-star-fill mr-2"></i>Performance Review
        <span class="badge badge-{{ $review->gradeBadgeClass() }} ml-1">{{ $review->gradeLabel() }}</span>
    </h5>
    <div>
        <a href="{{ route('hr.performance.reviews.edit', $review->id) }}" class="btn btn-sm btn-primary mr-1">
            <i class="bi bi-pencil mr-1"></i>Edit
        </a>
        <a href="{{ route('hr.performance.reviews') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card text-center p-3 mb-3">
            <img src="{{ $review->employee->photo_url }}" width="80" height="80"
                 class="rounded-circle mx-auto mb-2" style="object-fit:cover;">
            <h6 class="mb-0">{{ $review->employee->full_name }}</h6>
            <small class="text-muted">{{ $review->employee->employee_code }}</small>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <h3 class="text-{{ $review->gradeBadgeClass() }} mb-0">
                        {{ number_format($review->overall_score, 2) }}
                    </h3>
                    <small class="text-muted">Overall / 10</small>
                </div>
                <div class="col-6">
                    <h5 class="text-{{ $review->gradeBadgeClass() }} mb-0 mt-1">
                        {{ $review->gradeLabel() }}
                    </h5>
                    <small class="text-muted">Grade</small>
                </div>
            </div>
            <hr>
            <p class="small mb-1"><strong>Period:</strong> {{ $review->period }}</p>
            <p class="small mb-1"><strong>Reviewer:</strong> {{ $review->reviewer?->name ?? '—' }}</p>
            <p class="small mb-0"><strong>Date:</strong> {{ $review->created_at->format('d M Y') }}</p>
        </div>

        @if($review->notes)
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Notes</h6></div>
            <div class="card-body small">{{ $review->notes }}</div>
        </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Score Breakdown</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Category</th>
                            <th class="text-center">Weight</th>
                            <th class="text-center">Score</th>
                            <th class="text-center">Weighted Score</th>
                            <th>Bar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($review->scores as $s)
                        <tr>
                            <td class="font-weight-bold">{{ $s->category->name }}</td>
                            <td class="text-center">{{ $s->category->weight }}</td>
                            <td class="text-center font-weight-bold">{{ number_format($s->score, 1) }}</td>
                            <td class="text-center text-primary">{{ number_format($s->weighted_score, 2) }}</td>
                            <td style="width:120px;">
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-{{ $s->score >= 7 ? 'success' : ($s->score >= 4 ? 'warning' : 'danger') }}"
                                         style="width:{{ ($s->score / 10) * 100 }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light font-weight-bold">
                            <td colspan="3" class="text-right">Overall Score:</td>
                            <td class="text-center text-primary">{{ number_format($review->overall_score, 2) }} / 10</td>
                            <td>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-{{ $review->gradeBadgeClass() }}"
                                         style="width:{{ ($review->overall_score / 10) * 100 }}%"></div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('hr.performance.employee', $review->employee_id) }}"
               class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clock-history mr-1"></i>View Full Performance History
            </a>
        </div>
    </div>
</div>
@endsection
