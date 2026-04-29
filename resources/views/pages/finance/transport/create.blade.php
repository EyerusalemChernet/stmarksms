@extends('layouts.master')
@section('page_title', 'Add Transport Route')
@section('content')
<div class="card" style="max-width:600px;">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-bus-front mr-2"></i>Add Transport Route</h5></div>
    <div class="card-body">
        <form action="{{ route('finance.transport.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Route Name</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
            </div>
            <div class="form-group">
                <label>Area / Zone</label>
                <input type="text" name="area" class="form-control" required value="{{ old('area') }}">
            </div>
            <div class="form-group">
                <label>Fee (ETB)</label>
                <input type="number" name="fee" class="form-control" step="0.01" min="0" required value="{{ old('fee') }}">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Route</button>
            <a href="{{ route('finance.transport.index') }}" class="btn btn-light ml-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
