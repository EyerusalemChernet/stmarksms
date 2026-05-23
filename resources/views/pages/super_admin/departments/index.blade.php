@extends('layouts.master')
@section('page_title', 'Departments')
@section('content')

<div class="d-flex align-items-center mb-4" style="gap:12px;">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0;">Departments</h5>
</div>

@if(session('flash_success'))<div class="alert alert-success border-0 mb-3">{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger border-0 mb-3">{{ session('flash_danger') }}</div>@endif

<div class="row">
    {{-- Add Department --}}
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="bi bi-building mr-1 text-primary"></i>Add Department</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('departments.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-semibold" style="font-size:13px;">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Sciences" required value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold" style="font-size:13px;">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-sm">
                        <i class="bi bi-plus-circle mr-1"></i>Add Department
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Department list --}}
    <div class="col-md-8">
        @forelse($departments as $dept)
        <div class="card mb-3">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <div>
                    <strong style="font-size:14px;">{{ $dept->name }}</strong>
                    <span class="badge badge-secondary ml-2">{{ $dept->teachers->count() }} teacher(s)</span>
                    @if($dept->description)
                    <small class="text-muted ml-2">{{ $dept->description }}</small>
                    @endif
                </div>
            </div>
            <div class="card-body py-3">

                {{-- Current teachers --}}
                @if($dept->teachers->isNotEmpty())
                <div class="mb-3">
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($dept->teachers as $t)
                        <div style="background:#ede9fe;border-radius:20px;padding:4px 12px;display:flex;align-items:center;gap:6px;font-size:12px;">
                            <img src="{{ $t->photo }}" class="rounded-circle" style="width:20px;height:20px;object-fit:cover;" alt="">
                            <span style="font-weight:600;color:#4f46e5;">{{ $t->name }}</span>
                            <form method="POST" action="{{ route('departments.teachers.remove', [$dept->id, $t->id]) }}"
                                  class="d-inline" onsubmit="return confirm('Remove {{ addslashes($t->name) }} from {{ addslashes($dept->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none;border:none;padding:0;color:#7c3aed;cursor:pointer;line-height:1;" title="Remove">
                                    <i class="bi bi-x" style="font-size:14px;"></i>
                                </button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <p class="text-muted small mb-3">No teachers assigned yet.</p>
                @endif

                {{-- Add teachers (multi-select with search) --}}
                <form method="POST" action="{{ route('departments.teachers.add', $dept->id) }}" class="d-flex align-items-end" style="gap:8px;">
                    @csrf
                    <div style="flex:1;">
                        <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;display:block;">
                            Add Teachers (select one or more)
                        </label>
                        <select name="user_ids[]" multiple required
                                class="form-control select-search"
                                data-placeholder="Search and select teachers..."
                                style="width:100%;">
                            @foreach($allTeachers as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}{{ $t->email ? ' — '.$t->email : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                        <i class="bi bi-plus-circle mr-1"></i>Add
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="card">
            <div class="card-body text-muted text-center py-4">
                No departments yet. Add one using the form on the left.
            </div>
        </div>
        @endforelse
    </div>
</div>

@endsection
