@extends('layouts.master')
@section('page_title', 'Activity Timeline — ' . $sr->user->name)
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent p-0">
        <li class="breadcrumb-item"><a href="{{ route('parent.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('parent.child', $sr->user_id) }}">{{ $sr->user->name }}</a></li>
        <li class="breadcrumb-item active">Timeline</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="icon-list mr-2 text-primary"></i>
            Activity Timeline for <strong>{{ $sr->user->name }}</strong> — {{ $year }}
        </h6>
    </div>
    <div class="card-body">
        @if($timeline->isEmpty())
            <p class="text-muted text-center py-4">No activity recorded yet for this session.</p>
        @else
        <div class="timeline">
            @foreach($timeline as $event)
            <div class="timeline-row">
                <div class="timeline-icon">
                    <i class="{{ $event['icon'] }}"></i>
                </div>
                <div class="timeline-content">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $event['title'] }}</strong>
                        <small class="text-muted">{{ $event['date'] }}</small>
                    </div>
                    <p class="mb-0 text-muted small">{{ $event['body'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<style>
.timeline { position: relative; padding-left: 30px; }
.timeline::before { content:''; position:absolute; left:10px; top:0; bottom:0; width:2px; background:#e9ecef; }
.timeline-row { position:relative; margin-bottom:20px; }
.timeline-icon { position:absolute; left:-30px; width:22px; height:22px; background:#fff; border:2px solid #dee2e6; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; }
.timeline-content { background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px; padding:10px 14px; }
</style>
@endsection
