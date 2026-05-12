@extends('layouts.master')
@section('page_title', 'Add Application')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-person-plus mr-2"></i>Add Job Application</h5>
    <a href="{{ route('hr.recruitment.applications') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('hr.recruitment.applications.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Job Posting <span class="text-danger">*</span></label>
                        <select name="job_posting_id" class="form-control" required>
                            <option value="">— Select Job Posting —</option>
                            @foreach($postings as $p)
                                <option value="{{ $p->id }}"
                                    {{ (old('job_posting_id') == $p->id || ($selected && $selected->id == $p->id)) ? 'selected' : '' }}>
                                    {{ $p->title }}
                                    @if($p->department) ({{ $p->department->name }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="09XXXXXXXX">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Address</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Cover Letter</label>
                        <textarea name="cover_letter" class="form-control" rows="4" placeholder="Applicant's cover letter or notes...">{{ old('cover_letter') }}</textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-send mr-1"></i>Submit Application
                        </button>
                        <a href="{{ route('hr.recruitment.applications') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
