@extends('layouts.master')
@section('page_title', 'Timetable Validation')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-check2-circle mr-2 text-primary"></i>
        Timetable Validation — {{ $validation['timetable']->name }}
        <small class="text-muted" style="font-size:13px;">
            ({{ $validation['timetable']->my_class->name ?? '?' }} · {{ $validation['timetable']->year }})
        </small>
    </h5>
    <a href="{{ route('ttr.manage', $validation['timetable']->id) }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to Manage
    </a>
</div>

{{-- ── Summary banner ──────────────────────────────────────────────────────── --}}
@if($validation['is_valid'])
<div class="alert alert-success d-flex align-items-center mb-4" style="gap:12px;">
    <i class="bi bi-check-circle-fill" style="font-size:2rem;"></i>
    <div>
        <strong>No conflicts found.</strong>
        This timetable is valid — all subjects, teachers, and time slots are properly scheduled.
    </div>
</div>
@else
<div class="alert alert-danger d-flex align-items-center mb-4" style="gap:12px;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size:2rem;"></i>
    <div>
        <strong>{{ $validation['total_conflicts'] }} conflict(s) detected.</strong>
        Review each issue below and apply the suggested fix before publishing this timetable.
    </div>
</div>
@endif

{{-- ── Conflict cards ──────────────────────────────────────────────────────── --}}
@foreach($validation['conflicts'] as $i => $c)
@php
    $isCritical = $c['severity'] === 'critical';
    $headerBg   = $isCritical ? '#ef4444' : '#f59e0b';
    $icon       = $isCritical ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill';
@endphp
<div class="card mb-3" style="border-left:4px solid {{ $headerBg }};">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background:{{ $headerBg }};color:#fff;">
        <h6 class="mb-0">
            <i class="bi {{ $icon }} mr-2"></i>
            Conflict #{{ $i + 1 }} — {{ ucwords(str_replace('_', ' ', $c['type'])) }}
        </h6>
        <span class="badge badge-light" style="color:{{ $headerBg }};">
            {{ ucfirst($c['severity']) }}
        </span>
    </div>
    <div class="card-body">
        <p class="mb-2"><strong>Issue:</strong> {{ $c['message'] }}</p>

        @if(!empty($c['time_slot']))
            <p class="mb-2 text-muted small">
                <i class="bi bi-clock mr-1"></i>Time slot: {{ $c['time_slot'] }}
                @if(!empty($c['day'])) &nbsp;·&nbsp; Day: {{ $c['day'] }} @endif
            </p>
        @endif

        {{-- Conflicting entries table --}}
        @if(!empty($c['entry_1']) && !empty($c['entry_2']))
        <table class="table table-sm table-bordered mb-3" style="font-size:13px;">
            <thead class="thead-light">
                <tr><th></th><th>Subject</th><th>Teacher</th><th>Time Slot</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-muted">Entry 1</td>
                    <td>{{ $c['entry_1']->subject->name ?? '—' }}</td>
                    <td>{{ $c['entry_1']->subject->teacher->name ?? '—' }}</td>
                    <td>{{ $c['entry_1']->time_slot->full ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Entry 2</td>
                    <td>{{ $c['entry_2']->subject->name ?? '—' }}</td>
                    <td>{{ $c['entry_2']->subject->teacher->name ?? '—' }}</td>
                    <td>{{ $c['entry_2']->time_slot->full ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- Suggested fix --}}
        <div class="alert alert-info mb-0 py-2">
            <i class="bi bi-lightbulb-fill mr-2 text-warning"></i>
            <strong>Suggested Fix:</strong> {{ $c['suggested_fix'] }}
        </div>
    </div>
</div>
@endforeach

{{-- ── Conflict type legend ────────────────────────────────────────────────── --}}
<div class="card mt-2">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-info-circle mr-1 text-primary"></i>What is checked?
        </h6>
    </div>
    <div class="card-body py-2">
        <div class="row" style="font-size:13px;">
            <div class="col-md-6">
                <p class="mb-1">
                    <span class="badge badge-danger mr-1">Critical</span>
                    <strong>Class double-booked</strong> — two subjects in the same slot on the same day
                </p>
                <p class="mb-1">
                    <span class="badge badge-danger mr-1">Critical</span>
                    <strong>Teacher double-booked</strong> — same teacher in two classes simultaneously
                </p>
            </div>
            <div class="col-md-6">
                <p class="mb-1">
                    <span class="badge badge-warning mr-1">Warning</span>
                    <strong>Subject not scheduled</strong> — a class subject has no timetable entry
                </p>
                <p class="mb-1">
                    <span class="badge badge-warning mr-1">Warning</span>
                    <strong>Subject repeated same day</strong> — same subject appears twice in one day
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
