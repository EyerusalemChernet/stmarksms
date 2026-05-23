@extends('layouts.master')
@section('page_title','Fee Categories')
@section('content')
@if(!$canManageFeeSetup)
<div class="alert alert-secondary mb-3"><i class="bi bi-eye mr-1"></i>View only — you cannot manage fee categories.</div>
@elseif($canManageFeeSetup && !$canDeleteFeeSetup)
<div class="alert alert-info mb-3 py-2" style="font-size:13px;">
  <i class="bi bi-info-circle mr-1"></i> You can <strong>add</strong> categories and <strong>edit</strong> your own entries.
  Rows with <strong>Super Admin activity</strong> are view-only for you. Only Super Admin can delete categories.
</div>
@endif
<div class="row">
  @if($canCreateFeeSetup)
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
  @endif

  <div class="{{ $canCreateFeeSetup ? 'col-md-7' : 'col-md-12' }}">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul mr-2"></i>Fee Categories</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr><th>#</th><th>Name</th><th>Code</th><th>Structures</th><th>Status</th><th class="d-none d-lg-table-cell">Super Admin activity</th>@if($canManageFeeSetup)<th>Actions</th>@endif</tr>
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
                <td class="d-none d-lg-table-cell" style="font-size:11px;max-width:200px;">
                  @include('pages.finance.fees._admin_activity_cell', ['record' => $cat])
                </td>
                @if($canManageFeeSetup)
                <td class="text-nowrap">
                  @if(\App\Services\FinancePermission::canEditFeeSetupRecord($cat))
                  <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editCat{{ $cat->id }}">
                    <i class="bi bi-pencil"></i>
                  </button>
                  @elseif($canEditFeeSetup)
                  <span class="badge badge-secondary" title="Created or updated by Super Admin — view only"><i class="bi bi-lock"></i></span>
                  @endif
                  @if($canDeleteFeeSetup)
                  <form action="{{ route('fees.categories.destroy', Qs::hash($cat->id)) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this category?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                  </form>
                  @endif
                </td>
                @endif
              </tr>
              @empty
              <tr><td colspan="{{ $canManageFeeSetup ? 7 : 6 }}" class="text-center text-muted py-4">No categories yet.</td></tr>
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
@if(\App\Services\FinancePermission::canEditFeeSetupRecord($cat))
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
@endif
@endforeach
@endsection
