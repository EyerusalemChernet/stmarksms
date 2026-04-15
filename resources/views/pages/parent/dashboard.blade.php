@extends('layouts.master')
@section('page_title', 'Parent Dashboard')
@section('content')

{{-- Unread message alert --}}
@if($unread > 0)
<div class="alert alert-info alert-dismissible border-0">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <i class="icon-envelop3 mr-2"></i>
    You have <strong>{{ $unread }}</strong> unread message(s).
    <a href="{{ route('inbox') }}" class="alert-link ml-2">View Inbox</a>
</div>
@endif

{{-- Children Cards --}}
@forelse($childData as $cd)
@php $sr = $cd['sr']; @endphp
<div class="card mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <img src="{{ $sr->user->photo }}" class="rounded-circle mr-3" width="48" height="48" alt="photo">
            <div>
                <h6 class="mb-0 font-weight-bold">{{ $sr->user->name }}</h6>
                <small class="text-muted">{{ $sr->my_class->name ?? '-' }} &bull; {{ $sr->section->name ?? '-' }} &bull; Adm: {{ $sr->adm_no }}</small>
            </div>
        </div>
        <a href="{{ route('parent.child', $sr->user_id) }}" class="btn btn-sm btn-primary">
            <i class="icon-eye mr-1"></i> Full Details
        </a>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Attendance --}}
            <div class="col-md-3 mb-3">
                <div class="card border h-100">
                    <div class="card-body text-center py-3">
                        <i class="icon-calendar3 icon-2x {{ $cd['att_pct'] >= 75 ? 'text-success' : 'text-danger' }} mb-2"></i>
                        <h4 class="{{ $cd['att_pct'] >= 75 ? 'text-success' : 'text-danger' }}">{{ $cd['att_pct'] }}%</h4>
                        <small class="text-muted">Attendance ({{ $cd['att_present'] }}/{{ $cd['att_total'] }} days)</small>
                        @if($cd['att_pct'] < 75)
                        <div class="mt-2"><span class="badge badge-danger">Below 75% threshold</span></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Latest Exam --}}
            <div class="col-md-3 mb-3">
                <div class="card border h-100">
                    <div class="card-body text-center py-3">
                        <i class="icon-books icon-2x text-warning mb-2"></i>
                        @if($cd['blocked'])
                            <p class="text-danger small mb-0"><i class="icon-lock2 mr-1"></i>Results blocked</p>
                            <small class="text-muted">Attendance or fees issue</small>
                        @elseif($cd['latest_exr'])
                            <h4 class="text-warning">{{ $cd['latest_exr']->total ?? '-' }}</h4>
                            <small class="text-muted">Latest Total Score</small>
                            <div><small>Avg: {{ $cd['latest_exr']->ave ?? '-' }} | Pos: {{ $cd['latest_exr']->pos ?? '-' }}</small></div>
                        @else
                            <p class="text-muted small mb-0">No results yet</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Fees --}}
            <div class="col-md-3 mb-3">
                <div class="card border h-100">
                    <div class="card-body text-center py-3">
                        <i class="icon-coin-dollar icon-2x {{ $cd['unpaid'] > 0 ? 'text-danger' : 'text-success' }} mb-2"></i>
                        @if($cd['unpaid'] > 0)
                            <h4 class="text-danger">{{ $cd['unpaid'] }}</h4>
                            <small class="text-muted">Outstanding Fee(s)</small>
                            <div class="mt-1"><span class="badge badge-danger">Payment Required</span></div>
                        @else
                            <h4 class="text-success"><i class="icon-checkmark3"></i></h4>
                            <small class="text-muted">All Fees Cleared</small>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Library --}}
            <div class="col-md-3 mb-3">
                <div class="card border h-100">
                    <div class="card-body text-center py-3">
                        <i class="icon-book icon-2x text-info mb-2"></i>
                        <h4 class="text-info">{{ $cd['borrowed']->count() }}</h4>
                        <small class="text-muted">Book(s) Currently Borrowed</small>
                        @foreach($cd['borrowed'] as $br)
                            <div class="mt-1"><small class="badge badge-info">{{ $br->book->name ?? '-' }}</small></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mt-1">
            <a href="{{ route('parent.timeline', $sr->user_id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="icon-list mr-1"></i> Activity Timeline
            </a>
        </div>
    </div>
</div>
@empty
<div class="card card-body text-center text-muted">
    <i class="icon-users4 icon-3x mb-3"></i>
    <p>No children linked to your account. Please contact the school administrator.</p>
</div>
@endforelse

{{-- Announcements --}}
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0"><i class="icon-megaphone mr-2 text-primary"></i>School Announcements</h6>
        <a href="{{ route('announcements') }}" class="btn btn-xs btn-light">View All</a>
    </div>
    <div class="card-body p-0">
        @forelse($announcements as $a)
        <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between">
                <strong>{{ $a->title }}</strong>
                <small class="text-muted">{{ $a->created_at->diffForHumans() }}</small>
            </div>
            <p class="mb-0 text-muted small mt-1">{{ $a->body }}</p>
        </div>
        @empty
        <p class="p-3 text-muted mb-0">No announcements at this time.</p>
        @endforelse
    </div>
</div>
@endsection
