@extends('layouts.master')
@section('page_title', 'Apply — ' . $posting->title)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-send mr-2"></i>Apply — {{ $posting->title }}</h5>
    <a href="{{ route('my.job_posting', $posting->id) }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">Application Form</h6>
                <small class="text-muted">
                    {{ $posting->department?->name ? $posting->department->name.' — ' : '' }}
                    {{ ucwords(str_replace('_',' ',$posting->employment_type)) }}
                </small>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('my.job_apply.store', $posting->id) }}" method="POST">
                    @csrf

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control"
                                   value="{{ old('first_name', $employee?->first_name ?? $user->name) }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control"
                                   value="{{ old('last_name', $employee?->last_name ?? '') }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $employee?->email ?? $user->email) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone', $employee?->phone ?? '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Cover Letter / Message</label>
                        <textarea name="cover_letter" class="form-control" rows="5"
                                  placeholder="Why are you interested in this role? What makes you a good fit?">{{ old('cover_letter') }}</textarea>
                        <small class="text-muted">Optional but recommended.</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send mr-1"></i>Submit Application
                        </button>
                        <a href="{{ route('my.job_board') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
