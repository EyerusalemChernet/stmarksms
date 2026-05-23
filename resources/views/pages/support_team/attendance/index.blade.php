@extends('layouts.master')
@section('page_title', 'Attendance')
@section('breadcrumb')
    <span class="breadcrumb-item active">Attendance</span>
@endsection
@section('content')

@if(session('flash_success'))<div class="alert alert-success alert-dismissible border-0"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger alert-dismissible border-0"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('flash_danger') }}</div>@endif

@if(Qs::userIsTeacher())
    @include('pages.support_team.attendance.teacher_index')
@elseif(Qs::userIsTeamSA())
<div class="alert alert-info border-0" style="border-radius:12px;">
    <i class="bi bi-info-circle mr-2"></i>
    <strong>View only.</strong> Student attendance is marked by homeroom teachers.
    Use the links below to review sessions and reports.
</div>
@endif

<div class="card mt-3 border-0 shadow-sm">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
        <div>
            <h6 class="mb-1 font-weight-bold">Attendance Sessions</h6>
            <small class="text-muted">View and edit past attendance records</small>
        </div>
        <div class="d-flex" style="gap:8px;">
            @if(Qs::userIsTeamSA())
            <a href="{{ route('attendance.risk') }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-shield-exclamation mr-1"></i>Early Warning
            </a>
            @endif
            <a href="{{ route('attendance.sessions') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-list-ul mr-1"></i>View All Sessions
            </a>
        </div>
    </div>
</div>

@endsection
