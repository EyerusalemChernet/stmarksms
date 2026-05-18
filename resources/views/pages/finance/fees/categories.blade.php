@extends('layouts.master')
@section('page_title','Fee Categories')
@section('content')
<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags mr-2"></i>Add Fee Category</h6></div>
      <div class="card-body">
        <form action="{{ route('fees.categories.store') }}" method="POST">@csrf
          <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Tuition Fee">
          </div>
          <div class="form-group">
            <label>Code *</label>
            <input type="text" name="code" class="form-control" required placeholder="e.g. TUI" maxlength="10">
            <small class="text-muted">Unique short code (max 10 chars)</small>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
          </div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Category</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul mr-2"></i>Fee Categories</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr><th>#</th><th>Name</th><th>Code</th><th>Structures</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              @forelse($categories as $i => $cat)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                  <strong>{{ $cat->name }}</strong>
                  @if($cat->description)<br><small class="text-muted">{{ $cat->description }}</small>@endif
                </td>
                <td><span class="badge badge-secondary">{{ $cat->code }}</span></td>
                <td><span class="badge badge-light">{{ $cat->structures_count }}</span></td>
                <td>
                  @if($cat->active)
                    <span class="badge badge-success">Active</span>
                  @else
                    <span class="badge badge-secondary">Inactive</span>
                  @endif
                </td>
                <td class="text-nowrap">
                  <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editCat{{ $cat->id }}">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form action="{{ route('fees.categories.destroy', Qs::hash($cat->id)) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this category?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center text-muted py-4">No categories yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Edit Modals --}}
@foreach($categories as $cat)
<div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Edit: {{ $cat->name }}</h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('fees.categories.update', Qs::hash($cat->id)) }}" method="POST">
        @csrf @method('PUT')
        <div class="modal-body">
          <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="2">{{ $cat->description }}</textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="active" class="form-control">
              <option value="1" {{ $cat->active ? 'selected' : '' }}>Active</option>
              <option value="0" {{ !$cat->active ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach
@endsection
