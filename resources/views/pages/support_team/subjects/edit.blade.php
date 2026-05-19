@extends('layouts.master')
@section('page_title', 'Edit Assignment — '.$s->name.' ('.$s->my_class->name.')')
@section('content')

<div class="card" style="max-width:600px;">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-pencil mr-2 text-primary"></i>
            Edit Assignment — <strong>{{ $s->name }}</strong> in <strong>{{ $s->my_class->name }}</strong>
        </h6>
    </div>
    <div class="card-body">

        <div class="alert alert-info border-0" style="font-size:13px;border-radius:8px;">
            <i class="bi bi-info-circle mr-2"></i>
            Subject name and code are managed in the <strong>Subject Catalog</strong>.
            Here you can only change the department assignment for this class.
        </div>

        <form class="ajax-update" method="POST" action="{{ route('subjects.update', $s->id) }}">
            @csrf @method('PUT')

            <div class="form-group">
                <label class="font-weight-semibold" style="font-size:13px;">Subject</label>
                <input type="text" class="form-control" value="{{ $s->name }}" disabled style="background:#f8fafc;">
            </div>

            <div class="form-group">
                <label class="font-weight-semibold" style="font-size:13px;">Class</label>
                <input type="text" class="form-control" value="{{ $s->my_class->name }}" disabled style="background:#f8fafc;">
            </div>

            <div class="form-group">
                <label class="font-weight-semibold" style="font-size:13px;">Department</label>
                <select name="department_id" class="form-control select-search" data-placeholder="Select department">
                    <option value=""></option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $s->department_id == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex" style="gap:8px;">
                <button type="submit" class="btn btn-primary">
                    Save <i class="icon-paperplane ml-1"></i>
                </button>
                <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
