@extends('layouts.master')
@section('page_title', 'Edit Transport Route')
@section('content')
<div class="card" style="max-width:600px;">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-pencil mr-2"></i>Edit Route</h5></div>
    <div class="card-body">
        <form action="{{ route('finance.transport.update', $route->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Route Name</label>
                <input type="text" name="name" class="form-control" required value="{{ $route->name }}">
            </div>
            <div class="form-group">
                <label>Area / Zone</label>
                <input type="text" name="area" class="form-control" required value="{{ $route->area }}">
            </div>
            <div class="form-group">
                <label>Fee (ETB)</label>
                <input type="number" name="fee" class="form-control" step="0.01" min="0" required value="{{ $route->fee }}">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2">{{ $route->description }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('finance.transport.index') }}" class="btn btn-light ml-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
