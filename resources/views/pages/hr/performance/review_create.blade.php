@extends('layouts.master')
@section('page_title', 'New Performance Review')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-star mr-2"></i>New Performance Review</h5>
    <a href="{{ route('hr.performance.reviews') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('hr.performance.reviews.store') }}" method="POST">
                    @csrf

                    <div class="form-row mb-3">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">— Select Employee —</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }} ({{ $emp->employee_code }})
                                        @if($emp->employmentDetails?->department)
                                            — {{ $emp->employmentDetails->department->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Review Period <span class="text-danger">*</span></label>
                            <input type="month" name="period" class="form-control"
                                   value="{{ old('period', now()->format('Y-m')) }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Reviewer</label>
                            <input type="text" class="form-control bg-light"
                                   value="{{ auth()->user()->name }}" readonly>
                        </div>
                    </div>

                    {{-- Score Categories --}}
                    <h6 class="font-weight-bold mb-3 border-bottom pb-2">
                        <i class="bi bi-sliders mr-1"></i>Score Categories
                        <small class="text-muted font-weight-normal ml-2">Enter score 0–10 for each category</small>
                    </h6>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-center" style="width:80px;">Weight</th>
                                    <th>Description</th>
                                    <th style="width:140px;">Score (0–10)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $cat)
                                <tr>
                                    <td class="font-weight-bold">{{ $cat->name }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">{{ $cat->weight }}</span>
                                    </td>
                                    <td class="text-muted small">{{ $cat->description ?? '—' }}</td>
                                    <td>
                                        <input type="number"
                                               name="scores[{{ $cat->id }}]"
                                               class="form-control form-control-sm score-input"
                                               value="{{ old("scores.{$cat->id}", 5) }}"
                                               min="0" max="10" step="0.1"
                                               data-weight="{{ $cat->weight }}"
                                               required>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="text-right font-weight-bold">
                                        Calculated Overall Score:
                                    </td>
                                    <td>
                                        <span id="overall-score-preview" class="font-weight-bold text-primary">—</span>
                                        <small class="text-muted"> / 10</small>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Overall comments about this employee's performance...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-check-circle mr-1"></i>Save Review
                        </button>
                        <a href="{{ route('hr.performance.reviews') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Live overall score calculation: sum(score × weight) / sum(weights)
function recalcOverall() {
    var sumWeighted = 0;
    var sumWeights  = 0;
    $('.score-input').each(function() {
        var score  = parseFloat($(this).val()) || 0;
        var weight = parseFloat($(this).data('weight')) || 1;
        sumWeighted += score * weight;
        sumWeights  += weight;
    });
    var overall = sumWeights > 0 ? (sumWeighted / sumWeights).toFixed(2) : '—';
    $('#overall-score-preview').text(overall);
}
$(document).on('input', '.score-input', recalcOverall);
recalcOverall();
</script>
@endsection
