@extends('layouts.master')
@section('page_title', 'Academic Years')
@section('content')

<div class="row">
<div class="col-md-8">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Academic Years</h6>
        </div>
        <div class="card-body p-0">
            @if(session('flash_success'))<div class="alert alert-success m-3 mb-0">{{ session('flash_success') }}</div>@endif
            @if(session('flash_danger'))<div class="alert alert-danger m-3 mb-0">{{ session('flash_danger') }}</div>@endif
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead class="thead-light">
                    <tr><th>Year Name</th><th>Status</th><th>Enrollments</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($years as $y)
                <tr>
                    <td><strong>{{ $y->name }}</strong></td>
                    <td>
                        @if($y->is_active || $y->is_current)
                        <span class="badge badge-success">Active</span>
                        @else
                        <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $y->enrollment_count }}</td>
                    <td>
                        <div class="d-flex" style="gap:4px;">
                            @if(!$y->is_active && !$y->is_current)
                            <form method="POST" action="{{ route('academic_years.activate', $y->id) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-xs btn-outline-success">Activate</button>
                            </form>
                            @endif
                            @if($y->enrollment_count == 0)
                            <form method="POST" action="{{ route('academic_years.destroy', $y->id) }}"
                                  onsubmit="return confirm('Delete {{ $y->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No academic years yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card">
        <div class="card-header bg-white"><h6 class="card-title mb-0">Add Academic Year</h6></div>
        <div class="card-body">
            <form method="POST" action="{{ route('academic_years.store') }}">
                @csrf
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;">Year Name <span class="text-danger">*</span></label>
                    <input type="text" name="year_name" required class="form-control"
                           placeholder="e.g. 2025-2026" value="{{ old('year_name') }}">
                    @error('year_name')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="bi bi-plus-circle mr-1"></i>Create Year
                </button>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
