@extends('layouts.master')
@section('page_title', 'Attendance Sessions')
@section('content')

<div class="d-flex align-items-center mb-4" style="gap:12px;">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 2px;">Attendance Sessions</h5>
        <small class="text-muted">All recorded attendance sessions</small>
    </div>
    @if(Qs::userIsTeacher())
    <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-primary ml-auto">
        <i class="bi bi-plus-circle mr-1"></i>New Session
    </a>
    @endif
</div>

@if(session('flash_success'))<div class="alert alert-success border-0">{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger border-0">{{ session('flash_danger') }}</div>@endif

{{-- ── Filters ──────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('attendance.sessions') }}" class="row align-items-end" style="gap:0;">
            <div class="col-md-2 mb-2">
                <label style="font-size:12px;font-weight:600;color:#475569;">Class</label>
                <select name="class_id" class="form-control form-control-sm select">
                    <option value="">All Classes</option>
                    @foreach(\App\Models\MyClass::orderBy('name')->get() as $c)
                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label style="font-size:12px;font-weight:600;color:#475569;">Section</label>
                <select name="section_id" class="form-control form-control-sm select">
                    <option value="">All Sections</option>
                    @foreach(\App\Models\Section::orderBy('name')->get() as $s)
                    <option value="{{ $s->id }}" {{ request('section_id') == $s->id ? 'selected' : '' }}>{{ $s->my_class->name ?? '' }} {{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label style="font-size:12px;font-weight:600;color:#475569;">Teacher</label>
                <select name="teacher_id" class="form-control form-control-sm select-search">
                    <option value="">All Teachers</option>
                    @foreach(\App\User::where('user_type','teacher')->orderBy('name')->get() as $t)
                    <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label style="font-size:12px;font-weight:600;color:#475569;">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 mb-2">
                <label style="font-size:12px;font-weight:600;color:#475569;">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 mb-2 d-flex" style="gap:6px;">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-funnel mr-1"></i>Filter
                </button>
                <a href="{{ route('attendance.sessions') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($sessions->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-clipboard-x" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p>No attendance sessions found.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead class="thead-light">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Date</th>
                        <th>Teacher</th>
                        <th>Records</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($sessions as $i => $s)
                <tr>
                    <td class="text-muted">{{ ($sessions->currentPage() - 1) * $sessions->perPage() + $i + 1 }}</td>
                    <td><strong>{{ $s->my_class->name ?? '—' }}</strong></td>
                    <td>{{ $s->section->name ?? '—' }}</td>
                    <td>
                        <span style="font-weight:600;">{{ \Carbon\Carbon::parse($s->date)->format('d M Y') }}</span>
                        <small class="text-muted d-block">{{ \Carbon\Carbon::parse($s->date)->diffForHumans() }}</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <img src="{{ $s->teacher->photo ?? asset('global_assets/images/user.png') }}"
                                 class="rounded-circle" style="width:28px;height:28px;object-fit:cover;" alt="">
                            <span>{{ $s->teacher->name ?? '—' }}</span>
                        </div>
                    </td>
                    <td>
                        @php
                            $count   = \App\Models\AttendanceRecord::where('session_id', $s->id)->count();
                            $present = \App\Models\AttendanceRecord::where('session_id', $s->id)->whereIn('status',['present','late'])->count();
                        @endphp
                        <span class="badge badge-{{ $count > 0 ? ($present == $count ? 'success' : 'warning') : 'secondary' }}">
                            {{ $present }}/{{ $count }} present
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('attendance.manage', $s->id) }}"
                           class="btn btn-xs btn-outline-primary">
                            <i class="bi bi-eye mr-1"></i>View
                        </a>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }} sessions</small>
            {{ $sessions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
