@extends('layouts.master')
@section('page_title', 'Auto-Promotion')
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent p-0">
        <li class="breadcrumb-item"><a href="{{ route('students.promotion') }}">Promotion</a></li>
        <li class="breadcrumb-item active">Auto-Promotion</li>
    </ol>
</nav>

@if(session('flash_success'))
<div class="alert alert-success border-0">{{ session('flash_success') }}</div>
@endif
@if(session('flash_danger'))
<div class="alert alert-danger border-0">{{ session('flash_danger') }}</div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap:8px;">
                    <i class="bi bi-arrow-up-circle text-success" style="font-size:18px;"></i>
                    <strong>Run Auto-Promotion</strong>
                </div>
                <span class="badge badge-success">{{ $promotion_mode === 'auto' ? 'Auto mode active' : 'Manual mode' }}</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0" style="font-size:13px;">
                    <i class="bi bi-info-circle mr-1"></i>
                    Promotes all students whose session average meets the minimum threshold.
                    Students below the threshold are held back. Students with no marks are skipped.
                    You can undo results from <a href="{{ route('students.promotion_manage') }}">Manage Promotions</a>.
                </div>

                <form method="POST" action="{{ route('students.auto_promote') }}"
                      onsubmit="return confirm('Run auto-promotion for session {{ $current_session }}?\n\nThis will move ALL students to the next session based on their average marks.\n\nThis action can be reversed from Manage Promotions.')">
                    @csrf

                    <div class="form-group">
                        <label class="font-weight-bold">Minimum Average to Promote (%)</label>
                        <div class="input-group">
                            <input type="number" name="min_average" min="0" max="100"
                                   value="{{ $promotion_min_avg }}" class="form-control" required>
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                        <small class="text-muted">Students at or above this average will be promoted</small>
                    </div>

                    <input type="hidden" name="promotion_mode" value="{{ $promotion_mode }}">

                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-arrow-up-circle mr-1"></i>
                        Run Auto-Promotion for {{ $current_session }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Other Promotion Tools</strong></div>
            <div class="card-body d-flex flex-column" style="gap:8px;">
                <a href="{{ route('students.promotion') }}" class="btn btn-outline-primary">
                    <i class="bi bi-person-check mr-1"></i> Manual Promote Students
                </a>
                <a href="{{ route('students.promotion_manage') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-list-check mr-1"></i> Manage Promotions
                </a>
                <a href="{{ route('promotion.batches.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-collection mr-1"></i> Promotion Batches
                </a>
                <a href="{{ route('promotion_rules.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-sliders mr-1"></i> Promotion Rules
                </a>
            </div>
        </div>
        <p class="text-muted small mt-2 mb-0">
            Promotion mode and pass mark defaults are configured under
            <a href="{{ route('term_setup.index') }}">Term & Semester Setup</a>.
        </p>
    </div>
</div>

@endsection
