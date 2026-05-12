@extends('layouts.master')
@section('page_title','Expense Categories')
@section('content')
<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags mr-2"></i>Add Category</h6></div>
      <div class="card-body">
        <form action="{{ route('expense_cats.store') }}" method="POST">@csrf
          <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Utilities"></div>
          <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Category</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul mr-2"></i>Expense Categories</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="thead-light"><tr><th>#</th><th>Name</th><th>Expenses</th><th>Actions</th></tr></thead>
          <tbody>
            @forelse($categories as $i => $cat)
            <tr>
              <td>{{ $i+1 }}</td>
              <td><strong>{{ $cat->name }}</strong><br><small class="text-muted">{{ $cat->description }}</small></td>
              <td><span class="badge badge-secondary">{{ $cat->expenses_count }}</span></td>
              <td class="text-nowrap">
                <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editCat{{ $cat->id }}"><i class="bi bi-pencil"></i></button>
                <form action="{{ route('expense_cats.destroy',$cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button></form>
              </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted py-4">No categories yet.</td></tr>
            @endforelse
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
@foreach($categories as $cat)
<div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h6 class="modal-title">Edit: {{ $cat->name }}</h6><button type="button" class="close" data-dismiss="modal">&times;</button></div>
  <form action="{{ route('expense_cats.update',$cat->id) }}" method="POST">@csrf @method('PUT')
    <div class="modal-body">
      <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="{{ $cat->name }}" required></div>
      <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2">{{ $cat->description }}</textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Update</button></div>
  </form>
</div></div></div>
@endforeach
@endsection