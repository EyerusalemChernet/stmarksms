@extends('layouts.master')
@section('page_title', 'Academic Year: '.$year->name.' ('.$year->eth_name.')')
@section('content')

{{-- Header --}}
<div class="card mb-3" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
        <div>
            <h4 class="mb-1">
                {{ $year->name }}
                <small style="opacity:.75;font-size:15px;">{{ $year->eth_name }}</small>
            </h4>
            <div style="font-size:13px;opacity:.85;">
                <i class="bi bi-calendar-range mr-1"></i>
                {{ $year->start_date->format('d M Y') }} &mdash; {{ $year->end_date->format('d M Y') }}
                &nbsp;&bull;&nbsp;
                @if($year->status === 'active')
                    <span class="badge badge-light" style="color:#10b981;">Active</span>
                @elseif($year->status === 'draft')
                    <span class="badge badge-warning">Draft</span>
                @else
                    <span class="badge badge-secondary">Archived</span>
                @endif
                @if($year->is_current)
                    <span class="badge badge-light ml-1">Current</span>
                @endif
                &nbsp;&bull;&nbsp;
                <span>{{ $events->count() }} events &nbsp;&bull;&nbsp; {{ $year->holidays->count() }} holidays</span>
            </div>
        </div>
        <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('calendar.index') }}?tab=manager" class="btn btn-sm"
               style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);">
                <i class="bi bi-arrow-left mr-1"></i>Back
            </a>
            <a href="{{ route('calendar.index') }}" class="btn btn-sm"
               style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);">
                <i class="bi bi-calendar3 mr-1"></i>Visual Calendar
            </a>
            @if(App\Helpers\Qs::userIsSuperAdmin())
            @if($year->status !== 'archived')
            <form method="post" action="{{ route('acal.archive', $year->id) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm"
                        style="background:rgba(255,200,0,.25);color:#fff;border:1px solid rgba(255,200,0,.4);"
                        onclick="return confirm('Archive this academic year?')">
                    <i class="bi bi-archive mr-1"></i>Archive
                </button>
            </form>
            @endif
            <form method="post" action="{{ route('acal.destroy', $year->id) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm"
                        style="background:rgba(239,68,68,.35);color:#fff;border:1px solid rgba(239,68,68,.5);"
                        onclick="return confirm('Permanently delete {{ $year->name }}? This will remove all its events, holidays and conflicts. This cannot be undone.')">
                    <i class="bi bi-trash mr-1"></i>Delete
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

<div class="row">
    {{-- Events --}}
    <div class="col-lg-7 mb-3">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0">
                    <i class="bi bi-calendar-event mr-2"></i>
                    All Events for {{ $year->name }} ({{ $events->count() }})
                </h6>
                <div class="d-flex align-items-center" style="gap:8px;">
                    <input type="text" id="show-event-search" class="form-control form-control-sm"
                           placeholder="Search events..." style="width:180px;font-size:12px;">
                    <small class="text-muted d-none d-md-block">Includes auto-generated + manually added events</small>
                </div>
            </div>
            <div class="card-body p-0" style="max-height:520px;overflow-y:auto;">
                @if($events->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-calendar-x" style="font-size:32px;opacity:.3;"></i>
                    <p class="mt-2 mb-0">No events yet for this academic year.</p>
                    <small>Go to the <a href="{{ route('calendar.index') }}">Visual Calendar</a> to add events, or re-generate this year from the Academic Year Manager.</small>
                </div>
                @else
                <table class="table table-sm mb-0">
                    <thead class="thead-light sticky-top">
                        <tr><th>Event</th><th>Type</th><th>Date (GC)</th><th>Ethiopian</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach($events as $ev)
                    @php
                        $eth = App\Models\CalendarEvent::toEthiopian(new DateTime($ev->start_date->format('Y-m-d')));
                        $ethLabel = App\Models\CalendarEvent::$ethiopianMonths[$eth['month']].' '.$eth['day'].', '.$eth['year'].' E.C.';
                    @endphp
                    <tr data-event-row>
                        <td>
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $ev->color ?? '#4f46e5' }};margin-right:6px;"></span>
                            <strong>{{ $ev->title }}</strong>
                            @if($ev->conflict_resolved ?? false)
                                <i class="bi bi-exclamation-triangle text-warning ml-1" title="{{ $ev->conflict_note }}"></i>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-light" style="font-size:11px;">{{ ucfirst($ev->type) }}</span>
                        </td>
                        <td style="font-size:12px;">
                            {{ $ev->start_date->format('d M Y') }}
                            @if($ev->end_date && $ev->end_date->format('Y-m-d') !== $ev->start_date->format('Y-m-d'))
                                <br><small class="text-muted">&#8594; {{ $ev->end_date->format('d M Y') }}</small>
                            @endif
                        </td>
                        <td style="font-size:11px;color:#6b7280;">{{ $ethLabel }}</td>
                        <td>
                            @if($ev->auto_generated ?? false)
                                <span class="badge badge-light" style="font-size:10px;">auto</span>
                            @else
                                <span class="badge badge-info" style="font-size:10px;">manual</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    <tr id="no-event-results" style="display:none;"><td colspan="5" class="text-center text-muted py-3" style="font-size:13px;">No events match your search.</td></tr>
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Holidays + Conflicts --}}
    <div class="col-lg-5 mb-3">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="bi bi-calendar-x mr-2 text-danger"></i>Holidays ({{ $year->holidays->count() }})</h6>
                <input type="text" id="show-holiday-search" class="form-control form-control-sm"
                       placeholder="Search holidays..." style="width:160px;font-size:12px;">
            </div>
            <div class="card-body p-0" style="max-height:240px;overflow-y:auto;">
                @if($year->holidays->isEmpty())
                <div class="text-center text-muted py-3" style="font-size:13px;">No holidays imported.</div>
                @else
                <table class="table table-sm mb-0">
                    <thead class="thead-light sticky-top"><tr><th>Holiday</th><th>Date</th><th>Source</th></tr></thead>
                    <tbody>
                    @foreach($year->holidays->sortBy('date') as $h)
                    <tr data-holiday-row>
                        <td style="font-size:13px;">{{ $h->name }}</td>
                        <td style="font-size:12px;">{{ $h->date->format('d M Y') }}</td>
                        <td><span class="badge badge-{{ $h->source === 'api' ? 'info' : 'secondary' }}" style="font-size:10px;">{{ $h->source }}</span></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        @if($conflicts->count())
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle mr-2 text-warning"></i>Resolved Conflicts ({{ $conflicts->count() }})</h6>
            </div>
            <div class="card-body p-0" style="max-height:220px;overflow-y:auto;">
                @foreach($conflicts as $c)
                <div class="px-3 py-2 border-bottom">
                    <div style="font-size:13px;font-weight:500;">{{ $c->event->title ?? 'Unknown event' }}</div>
                    <div style="font-size:12px;color:#6b7280;">
                        <span class="badge badge-warning" style="font-size:10px;">{{ str_replace('_',' ',$c->conflict_type) }}</span>
                        <span class="ml-1">{{ $c->original_date->format('d M') }} &#8594; {{ $c->resolved_date->format('d M Y') }}</span>
                    </div>
                    @if($c->ai_suggestion)
                    <div style="font-size:11px;color:#8b5cf6;margin-top:3px;"><i class="bi bi-robot mr-1"></i>{{ $c->ai_suggestion }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
<script>
jQuery(function($) {
    // Search events table
    $('#show-event-search').on('input', function() {
        var q = $(this).val().trim().toLowerCase();
        $('tbody tr[data-event-row]').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(!q || text.includes(q));
        });
        // Show no-results row if all hidden
        var visible = $('tbody tr[data-event-row]:visible').length;
        $('#no-event-results').toggle(visible === 0 && q.length > 0);
    });

    // Search holidays table
    $('#show-holiday-search').on('input', function() {
        var q = $(this).val().trim().toLowerCase();
        $('tbody tr[data-holiday-row]').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(!q || text.includes(q));
        });
    });
});
</script>
@endsection