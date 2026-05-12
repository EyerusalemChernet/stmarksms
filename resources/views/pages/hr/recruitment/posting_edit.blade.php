@extends('layouts.master')
@section('page_title', 'Edit Job Posting')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-pencil-square mr-2"></i>Edit Job Posting</h5>
    <a href="{{ route('hr.recruitment.postings') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('hr.recruitment.postings.update', $posting->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label class="font-weight-bold">Job Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $posting->title) }}" required>
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold">Vacancies</label>
                    <input type="number" name="vacancies" class="form-control" value="{{ old('vacancies', $posting->vacancies) }}" min="1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold">Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">— None —</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ old('department_id', $posting->department_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold">Position</label>
                    <select name="position_id" class="form-control">
                        <option value="">— None —</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}" {{ old('position_id', $posting->position_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="font-weight-bold">Employment Type</label>
                    <select name="employment_type" class="form-control">
                        @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $v=>$l)
                            <option value="{{ $v }}" {{ old('employment_type', $posting->employment_type) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold">Deadline</label>
                    <input type="date" name="deadline" class="form-control" value="{{ old('deadline', $posting->deadline?->format('Y-m-d')) }}">
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold">Status</label>
                    <select name="status" class="form-control">
                        @foreach(['open'=>'Open','on_hold'=>'On Hold','closed'=>'Closed'] as $v=>$l)
                            <option value="{{ $v }}" {{ old('status', $posting->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="font-weight-bold">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $posting->description) }}</textarea>
            </div>
            <div class="form-group">
                <label class="font-weight-bold">Requirements</label>
                <textarea name="requirements" class="form-control" rows="4">{{ old('requirements', $posting->requirements) }}</textarea>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle mr-1"></i>Save Changes</button>
        </form>
    </div>
</div>
@endsection
