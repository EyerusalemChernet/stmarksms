@extends('layouts.master')
@section('page_title', 'Performance Reviews')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-star-fill mr-2"></i>Performance Reviews</h5>
    <div>
        <a href="{{ route('hr.performance.categories') }}" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="bi bi-sliders mr-1"></i>Categories
        </a>
        <a href="{{ route('hr.performance.reviews.create') }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-circle mr-1"></i>New Review
        </a>
    </div>
</div>

{{-- Period filter + search + export --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:8px;">
        <form action="{{ route('hr.performance.reviews') }}" method="GET" class="form-inline mb-0 flex-grow-1" style="gap:6px;">
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control form-control-sm" style="min-width:180px;"
                   placeholder="Search employee name or code…">
            <label class="font-weight-bold mb-0">Period:</label>
            <select name="period" class="form-control form-control-sm">
                <option value="">— All Periods —</option>
                @foreach($availablePeriods as $p)
                    <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
            @if($search || request('period'))
            <a href="{{ route('hr.performance.reviews') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
            @endif
        </form>
        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.performance.reviews', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('hr.performance.reviews', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Employee</th>
                    <th>Period</th>
                    <th class="text-center">Overall Score</th>
                    <th class="text-center">Grade</th>
                    <th>Reviewer</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $rev)
                <tr>
                    <td>
                        <div class="d-flex align-items-center" style="gap:6px;">
                            <img src="{{ $rev->employee->photo_url }}" width="26" height="26"
                                 class="rounded-circle" style="object-fit:cover;">
                            <a href="{{ route('hr.performance.employee', $rev->employee_id) }}">
                                {{ $rev->employee->full_name }}
                            </a>
                        </div>
                    </td>
                    <td>{{ $rev->period }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $rev->gradeBadgeClass() }} px-2">
                            {{ number_format($rev->overall_score, 2) }} / 10
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $rev->gradeBadgeClass() }}">{{ $rev->gradeLabel() }}</span>
                    </td>
                    <td class="text-muted small">{{ $rev->reviewer?->name ?? '—' }}</td>
                    <td class="text-muted small">{{ $rev->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('hr.performance.reviews.show', $rev->id) }}"
                           class="btn btn-xs btn-primary"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('hr.performance.reviews.edit', $rev->id) }}"
                           class="btn btn-xs btn-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('hr.performance.reviews.destroy', $rev->id) }}"
                              method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Delete this review?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No reviews found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $reviews->links() }}</div>
    </div>
</div>
@endsection
