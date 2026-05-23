@extends('layouts.master')
@section('page_title', 'New Promotion Batch')
@section('content')

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex align-items-center mb-4" style="gap:12px;">
    <a href="{{ route('promotion.batches.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0;">New Promotion Batch</h5>
</div>

@if(session('flash_danger'))<div class="alert alert-danger border-0">{{ session('flash_danger') }}</div>@endif

<form method="POST" action="{{ route('promotion.batches.store') }}">
    @csrf

    <div class="card mb-3">
        <div class="card-header bg-white"><strong>Step 1 — Source (From)</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Academic Year <span class="text-danger">*</span></label>
                        <select name="from_academic_year_id" required class="form-control select">
                            <option value="">— Select year —</option>
                            @foreach($years as $y)
                            <option value="{{ $y->id }}" {{ old('from_academic_year_id')==$y->id ? 'selected':'' }}>
                                {{ $y->name }} {{ $y->is_active ? '(Active)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="from_class_id" required class="form-control select">
                            <option value="">— Select class —</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ old('from_class_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white"><strong>Step 2 — Target (To)</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Academic Year <span class="text-danger">*</span></label>
                        <select name="to_academic_year_id" required class="form-control select">
                            <option value="">— Select year —</option>
                            @foreach($years as $y)
                            <option value="{{ $y->id }}" {{ old('to_academic_year_id')==$y->id ? 'selected':'' }}>
                                {{ $y->name }} {{ $y->is_active ? '(Active)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="to_class_id" required class="form-control select">
                            <option value="">— Select class —</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ old('to_class_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white"><strong>Step 3 — Redistribution Mode</strong></div>
        <div class="card-body">
            <div class="row">
                @foreach(['random'=>['Random','Shuffle students and distribute evenly across sections.','bi-shuffle'],'balanced'=>['Balanced','Balance by gender, score, and capacity.','bi-bar-chart-line'],'keep_same'=>['Keep Same Section','7A→8A, 7B→8B based on section name.','bi-arrow-right-circle'],'manual'=>['Manual','Assign every student manually in the workspace.','bi-hand-index']] as $val=>[$label,$desc,$icon])
                <div class="col-md-6 mb-3">
                    <label style="border:2px solid {{ old('redistribution_mode')==$val ? '#4f46e5' : '#e2e8f0' }};border-radius:10px;padding:14px;cursor:pointer;display:block;transition:border-color .15s;"
                           onclick="this.style.borderColor='#4f46e5'">
                        <input type="radio" name="redistribution_mode" value="{{ $val }}" {{ old('redistribution_mode','random')==$val ? 'checked':'' }} style="margin-right:8px;">
                        <i class="bi {{ $icon }} mr-1 text-primary"></i>
                        <strong>{{ $label }}</strong>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;margin-left:22px;">{{ $desc }}</div>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex" style="gap:8px;">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-play-circle mr-1"></i>Create Batch &amp; Open Workspace
        </button>
        <a href="{{ route('promotion.batches.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

</div>
</div>
@endsection
