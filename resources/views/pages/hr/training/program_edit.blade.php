@extends('layouts.master')
@section('page_title', 'Edit Training Program')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-pencil mr-2"></i>Edit Training Program</h5>
    <a href="{{ route('hr.training.programs') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('hr.training.programs.update', $program->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="font-weight-bold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   value="{{ old('title', $program->title) }}" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-control" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ $program->category === $cat ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_',' ',$cat)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Provider / Trainer</label>
                            <input type="text" name="provider" class="form-control"
                                   value="{{ old('provider', $program->provider) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Duration (hours)</label>
                            <input type="number" name="duration_hours" class="form-control" min="1"
                                   value="{{ old('duration_hours', $program->duration_hours) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">Cost (ETB)</label>
                            <input type="number" name="cost" class="form-control" step="0.01" min="0"
                                   value="{{ old('cost', $program->cost) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $program->description) }}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_mandatory" value="1" class="form-check-input"
                                       id="mandatory" {{ $program->is_mandatory ? 'checked' : '' }}>
                                <label class="form-check-label" for="mandatory">Mandatory for all staff</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                       id="active" {{ $program->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">Active (visible for enrollment)</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-check-circle mr-1"></i>Save Changes
                        </button>
                        <a href="{{ route('hr.training.programs') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
