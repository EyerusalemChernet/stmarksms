@extends('layouts.master')
@section('page_title', 'Academic Calendar')
@section('content')

<ul class="nav nav-tabs mb-3" id="calendarTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="tab-calendar-link" data-toggle="tab" href="#tab-calendar" role="tab">
            <i class="bi bi-calendar3 mr-1"></i>Visual Calendar
        </a>
    </li>
    @if(App\Helpers\Qs::userIsTeamSA())
    <li class="nav-item">
        <a class="nav-link" id="tab-manager-link" data-toggle="tab" href="#tab-manager" role="tab">
            <i class="bi bi-magic mr-1"></i>Academic Year Manager
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-rules-link" data-toggle="tab" href="#tab-rules" role="tab">
            <i class="bi bi-sliders mr-1"></i>Rules Engine
        </a>
    </li>
    @endif
</ul>

<div class="tab-content" id="calendarTabContent">

{{--  TAB 1: VISUAL CALENDAR  --}}
<div class="tab-pane fade show active" id="tab-calendar" role="tabpanel">

<div class="card mb-3" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
        <div class="d-flex align-items-center" style="gap:16px;">
            <div>
                <div style="font-size:10px;opacity:.7;letter-spacing:1px;text-transform:uppercase;">Today  Ethiopian</div>
                <div style="font-size:20px;font-weight:700;" id="eth-today-label"></div>
            </div>
            <div style="width:1px;height:36px;background:rgba(255,255,255,.25);"></div>
            <div>
                <div style="font-size:10px;opacity:.7;letter-spacing:1px;text-transform:uppercase;">Gregorian</div>
                <div style="font-size:13px;font-weight:500;" id="greg-today-label"></div>
            </div>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
            @if(App\Helpers\Qs::userIsTeamSA())
            <button class="btn btn-sm" id="btn-add-event" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);">
                <i class="bi bi-plus-lg mr-1"></i>Add Event
            </button>
            @endif
            <a href="{{ $gcalSubscribe }}" target="_blank" class="btn btn-sm" style="background:#4285f4;color:#fff;border:none;">
                <i class="bi bi-calendar-plus mr-1"></i>Subscribe Google
            </a>
            <a href="{{ $icsUrl }}" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);">
                <i class="bi bi-download mr-1"></i>ICS
            </a>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:10px;">
    <div class="d-flex align-items-center" style="gap:8px;">
        <button class="btn btn-sm btn-outline-secondary" id="btn-prev-year"><i class="bi bi-chevron-left"></i></button>
        <h5 class="mb-0 font-weight-bold" id="current-year-label" style="min-width:60px;text-align:center;"></h5>
        <button class="btn btn-sm btn-outline-secondary" id="btn-next-year"><i class="bi bi-chevron-right"></i></button>
        <button class="btn btn-sm btn-outline-primary ml-2" id="btn-today-year">Today</button>
    </div>
    <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-primary active" id="view-year">Year</button>
        <button type="button" class="btn btn-outline-primary" id="view-month">Month</button>
        <button type="button" class="btn btn-outline-primary" id="view-week">Week</button>
        <button type="button" class="btn btn-outline-primary" id="view-agenda">Agenda</button>
    </div>
</div>

<div class="row">
    <div class="col-lg-2 col-md-3 mb-3">
        {{-- Search bar --}}
        <div class="card mb-3">
            <div class="card-body py-2 px-3">
                <div class="input-group input-group-sm">
                    <input type="text" id="event-search" class="form-control" placeholder="Search events..." style="font-size:12px;">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" id="btn-clear-search" type="button" style="display:none;"><i class="bi bi-x"></i></button>
                    </div>
                </div>
                <div id="search-results" style="display:none;max-height:300px;overflow-y:auto;margin-top:6px;"></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><strong style="font-size:12px;">Event Types</strong></div>
            <div class="card-body py-2 px-3">
                @foreach(['all'=>['#6b7280','All Events'],'holiday'=>['#ef4444','Holiday'],'exam'=>['#f59e0b','Exam'],'break'=>['#8b5cf6','Break'],'meeting'=>['#10b981','Meeting'],'event'=>['#4f46e5','School Event']] as $type=>$meta)
                <div class="d-flex align-items-center mb-2 legend-item" data-type="{{ $type }}" style="cursor:pointer;padding:3px 4px;border-radius:4px;">
                    <span style="width:11px;height:11px;border-radius:50%;background:{{ $meta[0] }};display:inline-block;margin-right:7px;flex-shrink:0;"></span>
                    <span style="font-size:12px;">{{ $meta[1] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <strong style="font-size:12px;"><i class="bi bi-clock mr-1"></i>Upcoming</strong>
                <span id="upcoming-count" class="badge badge-primary" style="font-size:10px;"></span>
            </div>
            <div class="card-body p-0">
                <div id="upcoming-events" style="max-height:420px;overflow-y:auto;">
                    <div class="text-center text-muted py-3" style="font-size:12px;">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-10 col-md-9">
        <div id="year-view"><div id="year-grid" class="year-grid"></div></div>
        <div id="fc-view" style="display:none;">
            <div class="card"><div class="card-body p-2"><div id="eth-calendar"></div></div></div>
        </div>
    </div>
</div>

{{-- Event detail modal --}}
<div class="modal fade" id="event-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" id="event-modal-header" style="background:#4f46e5;color:#fff;">
                <h5 class="modal-title" id="event-modal-title">Event Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="event-modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                <a id="btn-add-to-gcal" href="#" target="_blank" class="btn btn-sm" style="background:#4285f4;color:#fff;border:none;">
                    <i class="bi bi-calendar-plus mr-1"></i>Add to Google Calendar
                </a>
                @if(App\Helpers\Qs::userIsTeamSA())
                <button type="button" class="btn btn-sm btn-warning" id="btn-edit-event" style="display:none;"><i class="bi bi-pencil mr-1"></i>Edit</button>
                <button type="button" class="btn btn-sm btn-danger" id="btn-delete-event" style="display:none;"><i class="bi bi-trash mr-1"></i>Delete</button>
                @endif
            </div>
        </div>
    </div>
</div>

@if(App\Helpers\Qs::userIsTeamSA())
{{-- Add/Edit event modal --}}
<div class="modal fade" id="form-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#4f46e5;color:#fff;">
                <h5 class="modal-title" id="form-modal-title">Add Event</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="event-form">
                <div class="modal-body">
                    <input type="hidden" id="event-id">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Title <span class="text-danger">*</span></label>
                                <input type="text" id="ev-title" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Type</label>
                                <select id="ev-type" class="form-control">
                                    <option value="event">School Event</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="exam">Exam</option>
                                    <option value="break">Break</option>
                                    <option value="meeting">Meeting</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Start Date <span class="text-danger">*</span></label><input type="date" id="ev-start" class="form-control" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>End Date</label><input type="date" id="ev-end" class="form-control"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Start Time</label><input type="time" id="ev-start-time" class="form-control"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>End Time</label><input type="time" id="ev-end-time" class="form-control"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Color</label>
                                <div class="d-flex flex-wrap" style="gap:8px;">
                                    @foreach(['#4f46e5'=>'Indigo','#ef4444'=>'Red','#f59e0b'=>'Amber','#10b981'=>'Green','#3b82f6'=>'Blue','#8b5cf6'=>'Purple','#ec4899'=>'Pink','#6b7280'=>'Gray'] as $hex=>$name)
                                    <label style="cursor:pointer;" title="{{ $name }}">
                                        <input type="radio" name="ev-color" value="{{ $hex }}" style="display:none;">
                                        <span class="ev-color-swatch" style="display:inline-block;width:22px;height:22px;border-radius:50%;background:{{ $hex }};border:3px solid transparent;"></span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8"><div class="form-group"><label>Description</label><textarea id="ev-desc" class="form-control" rows="2"></textarea></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-switch mt-1">
                                <input type="checkbox" class="custom-control-input" id="ev-notify">
                                <label class="custom-control-label" for="ev-notify">Send email notification</label>
                            </div>
                        </div>
                        <div class="col-md-6" id="notify-roles-wrap" style="display:none;">
                            <div class="d-flex flex-wrap" style="gap:10px;margin-top:4px;">
                                @foreach(['student'=>'Students','teacher'=>'Teachers','parent'=>'Parents','admin'=>'Admins'] as $role=>$lbl)
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input notify-role-cb" id="role-{{ $role }}" value="{{ $role }}">
                                    <label class="custom-control-label" for="role-{{ $role }}">{{ $lbl }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="event-form-submit"><i class="bi bi-check-lg mr-1"></i>Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

</div>{{-- end tab-calendar --}}
{{--  TAB 2: ACADEMIC YEAR MANAGER  --}}
@if(App\Helpers\Qs::userIsTeamSA())
<div class="tab-pane fade" id="tab-manager" role="tabpanel">

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center py-3" style="border-left:4px solid #4f46e5;"><div style="font-size:28px;font-weight:700;color:#4f46e5;">{{ $years->count() }}</div><div class="text-muted" style="font-size:13px;">Academic Years</div></div></div>
    <div class="col-md-3"><div class="card text-center py-3" style="border-left:4px solid #10b981;"><div style="font-size:28px;font-weight:700;color:#10b981;">{{ $rules->where('is_active',true)->count() }}</div><div class="text-muted" style="font-size:13px;">Active Rules</div></div></div>
    <div class="col-md-3"><div class="card text-center py-3" style="border-left:4px solid #f59e0b;"><div style="font-size:28px;font-weight:700;color:#f59e0b;">{{ $current ? $current->events->count() : 0 }}</div><div class="text-muted" style="font-size:13px;">Events This Year</div></div></div>
    <div class="col-md-3"><div class="card text-center py-3" style="border-left:4px solid #ef4444;"><div style="font-size:28px;font-weight:700;color:#ef4444;">{{ $current ? $current->holidays->count() : 0 }}</div><div class="text-muted" style="font-size:13px;">Holidays This Year</div></div></div>
</div>

<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;">
                <h6 class="mb-0"><i class="bi bi-magic mr-2"></i>Generate Academic Year</h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:13px;">
                    Generates the full Ethiopian academic calendar: imports holidays, applies rules, resolves conflicts, and publishes.
                    <strong>After editing rules, re-generate to apply changes to the visual calendar.</strong>
                </p>
                <div class="form-group">
                    <label class="font-weight-semibold">Name</label>
                    <input type="text" id="gen-name" class="form-control" placeholder="e.g. 2026/2027">
                </div>
                <div class="form-group">
                    <label class="font-weight-semibold">Start Date</label>
                    <input type="date" id="gen-start" class="form-control">
                    <small id="gen-eth-label" style="font-size:11px;color:#7c3aed;"></small>
                </div>
                <div class="form-group">
                    <label class="font-weight-semibold">End Date</label>
                    <input type="date" id="gen-end" class="form-control">
                </div>
                <button id="btn-generate" class="btn btn-primary btn-block">
                    <i class="bi bi-play-fill mr-1"></i>Generate / Re-generate
                </button>
                <div id="gen-progress" class="mt-3" style="display:none;">
                    <div class="progress" style="height:6px;"><div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div></div>
                    <small class="text-muted mt-1 d-block">Running pipeline: rules &#8594; holidays &#8594; conflicts &#8594; publish...</small>
                </div>
                <div id="gen-result" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-3">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="bi bi-calendar-range mr-2"></i>Academic Years</h6>
                <a href="#tab-rules" data-toggle="tab" class="btn btn-sm btn-outline-secondary"><i class="bi bi-sliders mr-1"></i>Manage Rules</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>Year</th><th>Ethiopian</th><th>Period</th><th>Events</th><th>Holidays</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($years as $yr)
                    <tr>
                        <td class="font-weight-semibold">{{ $yr->name }}</td>
                        <td><span class="badge badge-light">{{ $yr->eth_name }}</span></td>
                        <td style="font-size:12px;">{{ $yr->start_date->format('d M Y') }}  {{ $yr->end_date->format('d M Y') }}</td>
                        <td>{{ $yr->events->count() }}</td>
                        <td>{{ $yr->holidays->count() }}</td>
                        <td>
                            @if($yr->status==='active')<span class="badge badge-success">Active</span>@if($yr->is_current)<span class="badge badge-primary ml-1">Current</span>@endif
                            @elseif($yr->status==='draft')<span class="badge badge-warning">Draft</span>
                            @else<span class="badge badge-secondary">Archived</span>@endif
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('acal.show', $yr->id) }}" class="btn btn-xs btn-info" title="View details"><i class="bi bi-eye"></i></a>
                            @if(App\Helpers\Qs::userIsSuperAdmin())
                            @if($yr->status === 'active' || $yr->status === 'archived')
                            <form method="post" action="{{ route('acal.activate', $yr->id) }}" class="d-inline">
                                @csrf
                                @if($yr->is_current)
                                <button type="submit" class="btn btn-xs btn-warning" title="Deactivate (archive)" onclick="return confirm('Deactivate {{ $yr->name }}?')"><i class="bi bi-pause-fill"></i></button>
                                @else
                                <button type="submit" class="btn btn-xs btn-success" title="Set as active/current" onclick="return confirm('Set {{ $yr->name }} as the active current year? All other years will be archived.')"><i class="bi bi-play-fill"></i></button>
                                @endif
                            </form>
                            @endif
                            @if($yr->status !== 'archived' && !$yr->is_current)
                            <form method="post" action="{{ route('acal.archive', $yr->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-secondary" title="Archive" onclick="return confirm('Archive this year?')"><i class="bi bi-archive"></i></button>
                            </form>
                            @endif
                            <form method="post" action="{{ route('acal.destroy', $yr->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-danger" title="Delete permanently" onclick="return confirm('Delete {{ $yr->name }} permanently? All events and holidays will be removed.')"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No academic years yet. Generate one above.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>{{-- end tab-manager --}}

{{--  TAB 3: RULES ENGINE  --}}
<div class="tab-pane fade" id="tab-rules" role="tabpanel">
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-sliders mr-2"></i>Calendar Rules Engine
            <small class="text-muted ml-2" style="font-size:12px;">Rules define how the academic calendar is auto-generated. After editing, go to Academic Year Manager and click Re-generate.</small>
        </h6>
        <button class="btn btn-sm btn-primary" id="btn-add-rule"><i class="bi bi-plus-lg mr-1"></i>Add Rule</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th style="width:30px;">#</th><th>Rule Name</th><th>Type</th><th>Event Type</th><th>Rule Value</th><th>Active</th><th style="width:80px;">Actions</th></tr>
            </thead>
            <tbody>
            @foreach($rules as $r)
            <tr data-id="{{ $r->id }}">
                <td>{{ $r->sort_order }}</td>
                <td>
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $r->color }};margin-right:6px;"></span>
                    <strong>{{ $r->name }}</strong>
                    @if($r->description)<br><small class="text-muted">{{ $r->description }}</small>@endif
                </td>
                <td><code style="font-size:11px;">{{ $r->rule_type }}</code></td>
                <td><span class="badge badge-light">{{ ucfirst($r->event_type) }}</span></td>
                <td><code style="font-size:11px;">{{ json_encode($r->rule_value) }}</code></td>
                <td>@if($r->is_active)<span class="badge badge-success">Yes</span>@else<span class="badge badge-secondary">No</span>@endif</td>
                <td>
                    <button class="btn btn-xs btn-warning btn-edit-rule" data-id="{{ $r->id }}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-xs btn-danger btn-delete-rule" data-id="{{ $r->id }}"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>{{-- end tab-rules --}}
@endif

</div>{{-- end tab-content --}}

{{--  RULE MODAL  --}}
@if(App\Helpers\Qs::userIsTeamSA())
<div class="modal fade" id="rule-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#4f46e5;color:#fff;">
                <h5 class="modal-title" id="rule-modal-title">Add Calendar Rule</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="rule-form">
                <input type="hidden" id="rule-id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8"><div class="form-group"><label>Rule Name <span class="text-danger">*</span></label><input type="text" id="r-name" class="form-control" required placeholder="e.g. First Day of School"></div></div>
                        <div class="col-md-2"><div class="form-group"><label>Sort</label><input type="number" id="r-sort" class="form-control" value="99"></div></div>
                        <div class="col-md-2"><div class="form-group"><label>Color</label><input type="color" id="r-color" class="form-control" value="#4f46e5" style="height:38px;padding:2px 4px;"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Rule Type <span class="text-danger">*</span></label>
                                <select id="r-type" class="form-control" required>
                                    <option value="fixed_month_day">Fixed Month/Day  specific date each year</option>
                                    <option value="week_offset_from_start">Week Offset from Semester 1 Start</option>
                                    <option value="week_offset_from_sem2">Week Offset from Semester 2 Start</option>
                                    <option value="nth_weekday">Nth Weekday of Month</option>
                                    <option value="easter_offset">Orthodox Easter Offset</option>
                                    <option value="islamic_holiday">Islamic Holiday</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Event Type</label>
                                <select id="r-event-type" class="form-control">
                                    <option value="event">School Event</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="exam">Exam</option>
                                    <option value="break">Break</option>
                                    <option value="meeting">Meeting</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- fixed_month_day --}}
                    <div id="fields-fixed_month_day" class="rule-fields">
                        <div class="alert alert-light border mb-2 py-2 px-3" style="font-size:12px;"><i class="bi bi-info-circle mr-1 text-primary"></i><strong>Fixed Month/Day</strong>  fires on the same Gregorian date every year. Example: Ethiopian New Year always falls on <strong>September 11</strong>.</div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Month <span class="text-danger">*</span></label><select id="fmd-month" class="form-control"><option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option><option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8">August</option><option value="9" selected>September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option></select></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Day <span class="text-danger">*</span></label><input type="number" id="fmd-day" class="form-control" min="1" max="31" value="11"></div></div>
                        </div>
                        <small class="text-muted">Will generate as: <strong id="fmd-preview">September 11</strong> each year.</small>
                    </div>

                    {{-- week_offset --}}
                    <div id="fields-week_offset" class="rule-fields" style="display:none;">
                        <div class="alert alert-light border mb-2 py-2 px-3" style="font-size:12px;"><i class="bi bi-info-circle mr-1 text-primary"></i><strong>Week Offset</strong>  counts weeks from the semester start (Sep 11). Pick a target date and the weeks are calculated automatically.</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Target Date (weeks auto-calculated from Sep 11)</label>
                                    <input type="date" id="wo-weeks-picker" class="form-control">
                                    <input type="hidden" id="wo-weeks" value="8">
                                    <small class="text-muted" id="wo-preview">8 weeks from start, lasting 5 days</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Duration: <strong id="wo-duration-label">5</strong> days</label>
                                    <input type="range" id="wo-duration" class="form-control-range mt-2" min="1" max="30" value="5" style="accent-color:#4f46e5;">
                                    <div class="d-flex justify-content-between" style="font-size:11px;color:#9ca3af;"><span>1 day</span><span>30 days</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- easter_offset --}}
                    <div id="fields-easter_offset" class="rule-fields" style="display:none;">
                        <div class="alert alert-light border mb-2 py-2 px-3" style="font-size:12px;"><i class="bi bi-info-circle mr-1 text-primary"></i><strong>Orthodox Easter Offset</strong>  Ethiopian Orthodox Easter (Fasika) falls on a different date each year. Use offset 0 for Easter Sunday, -2 for Good Friday (Siklet), +1 for Easter Monday, etc.</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Days offset from Orthodox Easter</label>
                                    <div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Days</span></div><input type="number" id="eo-offset" class="form-control" value="0" min="-30" max="30"></div>
                                    <small class="text-muted">Negative = before Easter &nbsp;|&nbsp; 0 = Easter Sunday &nbsp;|&nbsp; Positive = after Easter</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Calendar System</label>
                                    <select id="eo-calendar" class="form-control">
                                        <option value="orthodox" selected>Orthodox (Ethiopian/Coptic)  used in Ethiopia</option>
                                        <option value="western">Western (Catholic/Protestant)</option>
                                    </select>
                                    <small class="text-muted">Orthodox Easter is typically 15 weeks later than Western Easter.</small>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 px-3 mb-0" style="font-size:12px;"><i class="bi bi-calendar-heart mr-1"></i><span id="eo-preview">Easter Sunday  Orthodox calendar</span></div>
                    </div>

                    {{-- nth_weekday --}}
                    <div id="fields-nth_weekday" class="rule-fields" style="display:none;">
                        <div class="alert alert-light border mb-2 py-2 px-3" style="font-size:12px;"><i class="bi bi-info-circle mr-1 text-primary"></i><strong>Nth Weekday</strong>  e.g. "2nd Monday of October".</div>
                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Occurrence</label><select id="nw-n" class="form-control"><option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option><option value="4">4th</option></select></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Weekday</label><select id="nw-weekday" class="form-control"><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="7">Sunday</option></select></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Month</label><select id="nw-month" class="form-control"><option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option><option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8">August</option><option value="9">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option></select></div></div>
                        </div>
                    </div>

                    {{-- islamic_holiday --}}
                    <div id="fields-islamic_holiday" class="rule-fields" style="display:none;">
                        <div class="alert alert-light border mb-2 py-2 px-3" style="font-size:12px;"><i class="bi bi-info-circle mr-1 text-primary"></i><strong>Islamic Holiday</strong>  shifts ~11 days earlier each Gregorian year (lunar calendar). The system approximates the date automatically.</div>
                        <div class="form-group"><label>Holiday</label><select id="ih-holiday" class="form-control"><option value="eid_al_fitr">Eid al-Fitr  End of Ramadan</option><option value="eid_al_adha">Eid al-Adha  Feast of Sacrifice</option><option value="mawlid">Mawlid al-Nabi  Prophet's Birthday</option></select></div>
                        <div class="form-group">
                            <label>Duration: <strong id="ih-duration-label">2</strong> days</label>
                            <input type="range" id="ih-duration" class="form-control-range" min="1" max="5" value="2" style="accent-color:#4f46e5;">
                            <div class="d-flex justify-content-between" style="font-size:11px;color:#9ca3af;"><span>1 day</span><span>5 days</span></div>
                        </div>
                    </div>

                    <div class="form-group mt-3"><label>Description <small class="text-muted">(optional)</small></label><input type="text" id="r-desc" class="form-control" placeholder="Brief explanation"></div>
                    <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="r-active" checked><label class="custom-control-label" for="r-active">Active  include in calendar generation</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="rule-save-btn"><i class="bi bi-check-lg mr-1"></i>Save Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Safe JSON data island  avoids Blade/JS escaping issues --}}
@if(App\Helpers\Qs::userIsTeamSA())
<script type="application/json" id="rules-json-data">
{!! json_encode($rules->keyBy('id')->map(function($r){ return ['id'=>$r->id,'name'=>$r->name,'rule_type'=>$r->rule_type,'event_type'=>$r->event_type,'rule_value'=>$r->rule_value,'color'=>$r->color,'description'=>$r->description??'','sort_order'=>$r->sort_order,'is_active'=>$r->is_active?1:0]; }), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}
</script>
@endif

@endsection
@section('scripts')
@include('pages.calendar._scripts')
<script>
jQuery(function($){

//  Auto-populate generate form 
(function(){
    var now=new Date(), yr=now.getMonth()>=8?now.getFullYear():now.getFullYear()-1, et=yr-7;
    $('#gen-name').val(yr+'/'+(yr+1));
    $('#gen-start').val(yr+'-09-11');
    $('#gen-end').val((yr+1)+'-07-07');
    $('#gen-eth-label').text('Ethiopian: '+et+'/'+(et+1)+' E.C.  (Meskerem 1 to Sene 30)');
})();

$('#gen-start').on('change',function(){
    var d=new Date(this.value+'T00:00:00'); if(!d||isNaN(d)) return;
    var yr=d.getFullYear(), et=yr-7;
    $('#gen-name').val(yr+'/'+(yr+1)); $('#gen-end').val((yr+1)+'-07-07');
    $('#gen-eth-label').text('Ethiopian: '+et+'/'+(et+1)+' E.C.');
});

//  Generate 
$('#btn-generate').on('click',function(){
    var startDate=$('#gen-start').val();
    if(!startDate){ flash({msg:'Please select a start date.',type:'warning'}); return; }
    var startYear=parseInt(startDate.split('-')[0]);
    var $btn=$(this).prop('disabled',true).html('<i class="bi bi-hourglass-split mr-1"></i>Generating...');
    $('#gen-progress').show(); $('#gen-result').hide();
    $.post('{{ route("acal.generate") }}',{_token:$('meta[name="csrf-token"]').attr('content'),start_year:startYear})
    .done(function(r){
        var cls=r.ok?'success':'danger';
        var html='<div class="alert alert-'+cls+' border-0 mb-0"><strong>'+(r.ok?'&#10003; Done':'&#10007; Failed')+'</strong><br>'+r.msg+'</div>';
        if(r.ok&&r.summary){
            html+='<div class="row text-center mt-2"><div class="col-4"><strong>'+r.summary.events+'</strong><br><small class="text-muted">Events</small></div><div class="col-4"><strong>'+r.summary.holidays+'</strong><br><small class="text-muted">Holidays</small></div><div class="col-4"><strong>'+r.summary.conflicts+'</strong><br><small class="text-muted">Conflicts fixed</small></div></div>';
            if(r.year_id) html+='<a href="{{ url("/academic-calendar") }}/'+r.year_id+'" class="btn btn-sm btn-primary btn-block mt-2">View Calendar &#8594;</a>';
        }
        $('#gen-result').html(html).show();
        if(r.ok) setTimeout(function(){location.reload();},3000);
    })
    .fail(function(xhr){ $('#gen-result').html('<div class="alert alert-danger border-0">'+(xhr.responseJSON?xhr.responseJSON.msg:'Server error')+'</div>').show(); })
    .always(function(){ $('#gen-progress').hide(); $btn.prop('disabled',false).html('<i class="bi bi-play-fill mr-1"></i>Generate / Re-generate'); });
});

//  Rules Engine 
var rulesStoreUrl  = '{{ route("acal.rules.store") }}';
var rulesUpdateUrl = '{{ url("/academic-calendar/rules") }}';
var rulesDataEl    = document.getElementById('rules-json-data');
var rulesData      = rulesDataEl ? JSON.parse(rulesDataEl.textContent) : {};
var monthNames     = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

function showRuleFields(type){
    $('.rule-fields').hide();
    if(type==='fixed_month_day')                                          $('#fields-fixed_month_day').show();
    else if(type==='week_offset_from_start'||type==='week_offset_from_sem2'){ $('#fields-week_offset').show(); updateWoPreview(); }
    else if(type==='easter_offset')                                       { $('#fields-easter_offset').show(); updateEoPreview(); }
    else if(type==='nth_weekday')                                           $('#fields-nth_weekday').show();
    else if(type==='islamic_holiday')                                       $('#fields-islamic_holiday').show();
}
$('#r-type').on('change',function(){ showRuleFields($(this).val()); });

function updateFmdPreview(){ $('#fmd-preview').text(monthNames[parseInt($('#fmd-month').val())||9]+' '+(parseInt($('#fmd-day').val())||11)); }
$('#fmd-month,#fmd-day').on('change input',updateFmdPreview);

function updateWoPreview(){
    var p=$('#wo-weeks-picker').val(), w=parseInt($('#wo-weeks').val())||8, dur=parseInt($('#wo-duration').val())||5;
    if(p){ var d=new Date(p+'T00:00:00'),s=new Date(d.getFullYear()+'-09-11T00:00:00'); w=Math.max(0,Math.round((d-s)/604800000)); $('#wo-weeks').val(w); }
    $('#wo-preview').text(w+' week'+(w!==1?'s':'')+' from semester start, lasting '+dur+' day'+(dur!==1?'s':''));
}
$('#wo-weeks-picker').on('change',updateWoPreview);
$('#wo-duration').on('input',function(){ $('#wo-duration-label').text($(this).val()); updateWoPreview(); });

function updateEoPreview(){
    var o=parseInt($('#eo-offset').val())||0, cal=$('#eo-calendar').val();
    var lbl=o===0?'Easter Sunday itself':o<0?Math.abs(o)+' day'+(Math.abs(o)!==1?'s':'')+' before Easter':o+' day'+(o!==1?'s':'')+' after Easter';
    $('#eo-preview').text(lbl+' - '+(cal==='orthodox'?'Orthodox (Ethiopian)':'Western')+' calendar');
}
$('#eo-offset,#eo-calendar').on('change input',updateEoPreview);
$('#ih-duration').on('input',function(){ $('#ih-duration-label').text($(this).val()); });

function buildRuleValue(){
    var t=$('#r-type').val();
    if(t==='fixed_month_day')          return JSON.stringify({month:parseInt($('#fmd-month').val()),day:parseInt($('#fmd-day').val())});
    if(t==='week_offset_from_start'||t==='week_offset_from_sem2') return JSON.stringify({weeks:parseInt($('#wo-weeks').val()),duration_days:parseInt($('#wo-duration').val())});
    if(t==='easter_offset')            return JSON.stringify({offset_days:parseInt($('#eo-offset').val()),calendar:$('#eo-calendar').val()});
    if(t==='nth_weekday')              return JSON.stringify({n:parseInt($('#nw-n').val()),weekday:parseInt($('#nw-weekday').val()),month:parseInt($('#nw-month').val())});
    if(t==='islamic_holiday')          return JSON.stringify({holiday:$('#ih-holiday').val(),duration_days:parseInt($('#ih-duration').val())});
    return '{}';
}

function populateRuleFields(type,v){
    if(type==='fixed_month_day'){ $('#fmd-month').val(v.month||9); $('#fmd-day').val(v.day||11); updateFmdPreview(); }
    else if(type==='week_offset_from_start'||type==='week_offset_from_sem2'){
        var w=v.weeks||8,dur=v.duration_days||5;
        $('#wo-weeks').val(w); $('#wo-duration').val(dur); $('#wo-duration-label').text(dur);
        var approx=new Date(new Date().getFullYear()+'-09-11T00:00:00'); approx.setDate(approx.getDate()+w*7);
        $('#wo-weeks-picker').val(approx.toISOString().split('T')[0]); updateWoPreview();
    } else if(type==='easter_offset'){ $('#eo-offset').val(v.offset_days!==undefined?v.offset_days:0); $('#eo-calendar').val(v.calendar||'orthodox'); updateEoPreview(); }
    else if(type==='nth_weekday'){ $('#nw-n').val(v.n||1); $('#nw-weekday').val(v.weekday||1); $('#nw-month').val(v.month||9); }
    else if(type==='islamic_holiday'){ $('#ih-holiday').val(v.holiday||'eid_al_fitr'); $('#ih-duration').val(v.duration_days||2); $('#ih-duration-label').text(v.duration_days||2); }
}

$('#btn-add-rule').on('click',function(){
    $('#rule-id').val(''); $('#rule-form')[0].reset(); $('#r-color').val('#4f46e5'); $('#r-active').prop('checked',true); $('#r-sort').val('99');
    $('#rule-modal-title').text('Add Calendar Rule'); showRuleFields('fixed_month_day'); updateFmdPreview(); $('#rule-modal').modal('show');
});

$(document).on('click','.btn-edit-rule',function(){
    var id=$(this).data('id'), d=rulesData[id];
    if(!d){ flash({msg:'Rule not found. Refresh the page.',type:'danger'}); return; }
    $('#rule-id').val(d.id); $('#r-name').val(d.name); $('#r-type').val(d.rule_type); $('#r-event-type').val(d.event_type);
    $('#r-color').val(d.color||'#4f46e5'); $('#r-desc').val(d.description||''); $('#r-sort').val(d.sort_order); $('#r-active').prop('checked',d.is_active==1);
    $('#rule-modal-title').text('Edit Calendar Rule'); showRuleFields(d.rule_type); populateRuleFields(d.rule_type,d.rule_value); $('#rule-modal').modal('show');
});

$(document).on('click','.btn-delete-rule',function(){
    var id=$(this).data('id'), $row=$(this).closest('tr');
    swal({title:'Delete Rule?',text:'This cannot be undone.',icon:'warning',buttons:true,dangerMode:true}).then(function(ok){
        if(!ok) return;
        $.ajax({url:rulesUpdateUrl+'/'+id+'/delete',type:'POST',data:{_token:$('meta[name="csrf-token"]').attr('content'),_rule_id:id}})
        .done(function(r){ if(r.ok){ $row.fadeOut(300,function(){$row.remove();}); flash({msg:r.msg,type:'success'}); } else flash({msg:r.msg||'Failed.',type:'danger'}); })
        .fail(function(){ flash({msg:'Server error.',type:'danger'}); });
    });
});

$('#rule-form').on('submit',function(e){
    e.preventDefault();
    var id=$('#rule-id').val(), url=id?rulesUpdateUrl+'/'+id+'/update':rulesStoreUrl;
    var payload={_token:$('meta[name="csrf-token"]').attr('content'),_rule_id:id,name:$('#r-name').val(),rule_type:$('#r-type').val(),event_type:$('#r-event-type').val(),rule_value:buildRuleValue(),color:$('#r-color').val(),description:$('#r-desc').val(),sort_order:$('#r-sort').val(),is_active:$('#r-active').is(':checked')?1:0};
    var $btn=$('#rule-save-btn').prop('disabled',true).html('<i class="bi bi-hourglass-split mr-1"></i>Saving...');
    $.post(url,payload)
    .done(function(r){ if(r.ok){ $('#rule-modal').modal('hide'); flash({msg:r.msg,type:'success'}); setTimeout(function(){location.reload();},800); } else flash({msg:r.msg||'Save failed.',type:'danger'}); })
    .fail(function(xhr){ flash({msg:xhr.status===422?Object.values((xhr.responseJSON||{}).errors||{}).flat().join(' '):'Server error.',type:'danger'}); })
    .always(function(){ $btn.prop('disabled',false).html('<i class="bi bi-check-lg mr-1"></i>Save Rule'); });
});

$('#rule-modal').on('show.bs.modal',function(){ if(!$('#rule-id').val()) showRuleFields('fixed_month_day'); });
$(document).on('click','a[href="#tab-rules"]',function(e){ e.preventDefault(); $('#tab-rules-link').tab('show'); });
$(document).on('click','a[href="#tab-manager"]',function(e){ e.preventDefault(); $('#tab-manager-link').tab('show'); });

@if(session('open_tab') === 'manager')
    $('#tab-manager-link').tab('show');
@endif

showRuleFields('fixed_month_day');
updateFmdPreview();

}); // end jQuery ready
</script>
@endsection
