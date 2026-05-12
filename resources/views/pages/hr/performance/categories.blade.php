@extends('layouts.master')
@section('page_title', 'Performance Categories')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-sliders mr-2"></i>Performance Score Categories</h5>
    <a href="{{ route('hr.performance.reviews') }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-star mr-1"></i>Reviews
    </a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Add Category</h6></div>
            <div class="card-body">
                <form action="{{ route('hr.performance.categories.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name') }}" placeholder="e.g. Punctuality, Teamwork" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Weight <span class="text-danger">*</span></label>
                        <input type="number" name="weight" class="form-control"
                               value="{{ old('weight', 1) }}" step="0.1" min="0.1" max="100" required>
                        <small class="text-muted">Higher weight = more impact on overall score.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="What this category measures">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle mr-1"></i>Add Category
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-3">
                <h6 class="font-weight-bold mb-2"><i class="bi bi-info-circle mr-1"></i>Scoring Formula</h6>
                <p class="small text-muted mb-1">
                    <strong>Overall Score</strong> = Σ(score × weight) ÷ Σ(weights)
                </p>
                <p class="small text-muted mb-0">
                    Scores are entered 0–10 per category. The weighted average gives the final performance score.
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <h6 class="card-title mb-0">
                    All Categories
                    <span class="badge badge-secondary ml-1">{{ $categories->count() }}</span>
                    @if($search)<span class="badge badge-warning ml-1">Filtered: "{{ $search }}"</span>@endif
                </h6>
                <div class="d-flex" style="gap:6px;">
                    <a href="{{ route('hr.performance.categories', array_merge(request()->query(), ['export'=>'pdf'])) }}"
                       class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
                    <a href="{{ route('hr.performance.categories', array_merge(request()->query(), ['export'=>'csv'])) }}"
                       class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
                </div>
            </div>
            <div class="card-body py-2 border-bottom">
                <form action="{{ route('hr.performance.categories') }}" method="GET" class="form-inline mb-0" style="gap:6px;">
                    <input type="text" name="search" value="{{ $search }}"
                           class="form-control form-control-sm" style="min-width:200px;"
                           placeholder="Search categories…">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search mr-1"></i>Search</button>
                    @if($search)
                    <a href="{{ route('hr.performance.categories') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x mr-1"></i>Clear</a>
                    @endif
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Name</th><th class="text-center">Weight</th><th>Description</th><th>Active</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                        <tr>
                            <td class="font-weight-bold">{{ $cat->name }}</td>
                            <td class="text-center"><span class="badge badge-primary">{{ $cat->weight }}</span></td>
                            <td class="text-muted small">{{ $cat->description ?? '—' }}</td>
                            <td>
                                @if($cat->is_active)
                                    <span class="badge badge-success">Yes</span>
                                @else
                                    <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-xs btn-primary cat-edit-btn"
                                        data-id="{{ $cat->id }}"
                                        data-name="{{ $cat->name }}"
                                        data-weight="{{ $cat->weight }}"
                                        data-desc="{{ $cat->description }}"
                                        data-active="{{ $cat->is_active ? 1 : 0 }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('hr.performance.categories.destroy', $cat->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete this category? Existing scores will be removed.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No categories yet. Add one on the left.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Edit card --}}
        <div class="card mt-3 d-none" id="cat-edit-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Edit Category</h6>
                <button type="button" class="btn btn-xs btn-outline-secondary" id="cat-edit-cancel">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="card-body">
                <form id="cat-edit-form">
                    @csrf @method('PUT')
                    <input type="hidden" id="edit-cat-id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Name</label>
                            <input type="text" id="edit-cat-name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Weight</label>
                            <input type="number" id="edit-cat-weight" class="form-control" step="0.1" min="0.1" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Active</label>
                            <select id="edit-cat-active" class="form-control">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea id="edit-cat-desc" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle mr-1"></i>Save
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).on('click', '.cat-edit-btn', function() {
    $('#edit-cat-id').val($(this).data('id'));
    $('#edit-cat-name').val($(this).data('name'));
    $('#edit-cat-weight').val($(this).data('weight'));
    $('#edit-cat-desc').val($(this).data('desc') || '');
    $('#edit-cat-active').val($(this).data('active'));
    $('#cat-edit-card').removeClass('d-none');
});
$('#cat-edit-cancel').on('click', function() { $('#cat-edit-card').addClass('d-none'); });

$('#cat-edit-form').on('submit', function(e) {
    e.preventDefault();
    var id = $('#edit-cat-id').val();
    $.ajax({
        url: '/hr/performance/categories/' + id,
        method: 'PUT',
        data: {
            _token:      '{{ csrf_token() }}',
            name:        $('#edit-cat-name').val(),
            weight:      $('#edit-cat-weight').val(),
            description: $('#edit-cat-desc').val(),
            is_active:   $('#edit-cat-active').val()
        }
    }).done(function(r) {
        if (r.ok || r.message) { location.reload(); }
        else { alert(r.msg || 'Error'); }
    }).fail(function(xhr) {
        var err = xhr.responseJSON;
        alert(err && err.errors ? Object.values(err.errors).flat().join('\n') : 'Error');
    });
});
</script>
@endsection
