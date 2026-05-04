@extends('layouts.master')
@section('page_title', 'Edit Review')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-pencil-square mr-2"></i>Edit Performance Review</h5>
    <a href="{{ route('hr.performance.reviews.show', $review->id) }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-light border mb-3">
                    <strong>{{ $employee->full_name }}</strong> — Period: <strong>{{ $review->period }}</strong>
                </div>

                <form action="{{ route('hr.performance.reviews.update', $review->id) }}" method="POST">
                    @csrf @method('PUT')

                    <h6 class="font-weight-bold mb-3 border-bottom pb-2">
                        <i class="bi bi-sliders mr-1"></i>Scores
                    </h6>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-center">Weight</th>
                                    <th style="width:140px;">Score (0–10)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $cat)
                                @php
                                    $existing = $review->scores->firstWhere('category_id', $cat->id);
                                    $val = old("scores.{$cat->id}", $existing?->score ?? 5);
                                @endphp
                                <tr>
                                    <td class="font-weight-bold">{{ $cat->name }}</td>
                                    <td class="text-center"><span class="badge badge-primary">{{ $cat->weight }}</span></td>
                                    <td>
                                        <input type="number"
                                               name="scores[{{ $cat->id }}]"
                                               class="form-control form-control-sm score-input"
                                               value="{{ $val }}"
                                               min="0" max="10" step="0.1"
                                               data-weight="{{ $cat->weight }}"
                                               required>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="2" class="text-right font-weight-bold">Overall Score:</td>
                                    <td>
                                        <span id="overall-score-preview" class="font-weight-bold text-primary">
                                            {{ number_format($review->overall_score, 2) }}
                                        </span> / 10
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $review->notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle mr-1"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function recalcOverall() {
    var sumWeighted = 0, sumWeights = 0;
    $('.score-input').each(function() {
        sumWeighted += (parseFloat($(this).val()) || 0) * (parseFloat($(this).data('weight')) || 1);
        sumWeights  += parseFloat($(this).data('weight')) || 1;
    });
    $('#overall-score-preview').text(sumWeights > 0 ? (sumWeighted / sumWeights).toFixed(2) : '—');
}
$(document).on('input', '.score-input', recalcOverall);
recalcOverall();
</script>
@endsection
