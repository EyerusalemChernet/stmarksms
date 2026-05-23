@extends('layouts.master')
@section('page_title', 'Promotion Summary')
@section('content')

<div class="d-flex align-items-center mb-4" style="gap:12px;">
    <a href="{{ route('promotion.batches.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0;">Promotion Summary</h5>
        <small class="text-muted">{{ $batch->fromClass?->name }} ({{ $batch->fromYear?->name }}) → {{ $batch->toClass?->name }} ({{ $batch->toYear?->name }})</small>
    </div>
    <span class="badge {{ $batch->statusBadgeClass() }} ml-2">{{ ucfirst($batch->status) }}</span>
</div>

@if(session('flash_success'))<div class="alert alert-success border-0">{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger border-0">{{ session('flash_danger') }}</div>@endif

{{-- Stats --}}
<div class="row mb-4">
    @foreach(['total'=>['Total Students','#4f46e5','bi-people'],'promoted'=>['Promoted','#10b981','bi-check-circle'],'conditional'=>['Conditional','#f59e0b','bi-exclamation-circle'],'held'=>['Held Back','#ef4444','bi-x-circle']] as $key=>[$label,$color,$icon])
    <div class="col-md-3 mb-3">
        <div class="card border-0" style="border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.07);">
            <div class="card-body text-center py-3">
                <i class="bi {{ $icon }}" style="font-size:28px;color:{{ $color }};"></i>
                <div style="font-size:28px;font-weight:700;color:#1e293b;margin:4px 0;">{{ $counts[$key] }}</div>
                <div style="font-size:12px;color:#64748b;">{{ $label }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Per-section breakdown --}}
<div class="card mb-4">
    <div class="card-header bg-white"><strong>Section Breakdown</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:13px;">
            <thead class="thead-light">
                <tr><th>Section</th><th>Students</th><th>Boys</th><th>Girls</th><th>Avg Score</th></tr>
            </thead>
            <tbody>
            @foreach($bySection as $sectionId => $sectionDrafts)
            @php
                $sectionName = $sectionDrafts->first()?->proposedSection?->name ?? '—';
                $boys  = $sectionDrafts->filter(fn($d) => strtolower($d->student?->gender ?? '') === 'male')->count();
                $girls = $sectionDrafts->filter(fn($d) => strtolower($d->student?->gender ?? '') === 'female')->count();
                $avg   = round($sectionDrafts->avg('yearly_average'), 1);
            @endphp
            <tr>
                <td><strong>{{ $sectionName }}</strong></td>
                <td>{{ $sectionDrafts->count() }}</td>
                <td>{{ $boys }}</td>
                <td>{{ $girls }}</td>
                <td>{{ $avg ?? '—' }}%</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Actions --}}
<div class="d-flex" style="gap:8px;">
    @if($batch->isFinalized())
    <form method="POST" action="{{ route('promotion.batches.rollback', $batch->id) }}"
          onsubmit="return confirm('Roll back this promotion? All new enrollments will be removed.')">
        @csrf
        <button class="btn btn-outline-warning btn-sm">
            <i class="bi bi-arrow-counterclockwise mr-1"></i>Rollback Promotion
        </button>
    </form>
    @endif
    <a href="{{ route('promotion.batches.index') }}" class="btn btn-secondary btn-sm">Back to Batches</a>
</div>

@endsection
