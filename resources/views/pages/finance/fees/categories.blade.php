@extends('layouts.master')
@section('page_title', 'Fee Categories')
@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags mr-2"></i>Add Fee Category</h6></div>
            <div class="card-body">
                <form action="{{ route('fees.categories.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Tuition Fee" required>
                    </div>
                    <div class="form-group">
                        <label>Code <small class="text-muted">(short, unique)</small></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. TUI" maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary btn-sm">Save Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul mr-2"></i>All Fee Categories</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>#</th><th>Name</th><th>Code</th><th>Structures</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $i => $cat)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $cat->name }}</td>
                            <td><span class="badge badge-secondary">{{ $cat->code }}</span></td>
                            <td>{{ $cat->structures_count }}</td>
                            <td>
                                @if($cat->active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('fees.categories.update', $cat->id) }}" method="POST" class="d-inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="name" value="{{ $cat->name }}">
                                    <input type="hidden" name="active" value="{{ $cat->active ? 0 : 1 }}">
                                    <button class="btn btn-xs {{ $cat->active ? 'btn-warning' : 'btn-success' }}" title="{{ $cat->active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi bi-{{ $cat->active ? 'pause' : 'play' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('fees.categories.destroy', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
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
@endsection
