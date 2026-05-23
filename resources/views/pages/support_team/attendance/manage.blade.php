@extends('layouts.master')
@section('page_title', 'Mark Attendance')
@section('breadcrumb')
    <a href="{{ route('attendance.index') }}" class="breadcrumb-item">Attendance</a>
    <span class="breadcrumb-item active">Mark</span>
@endsection
@section('content')

@if(Qs::userIsTeacher())
    @include('pages.support_team.attendance.teacher_manage')
@else
@include('partials.back_button')
<div class="card">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0">
            <i class="bi bi-clipboard-check mr-2 text-primary"></i>
            Attendance — {{ $session->my_class->name }} / {{ $session->section->name }}
            &nbsp;<span class="badge badge-info">{{ \Carbon\Carbon::parse($session->date)->format('d M Y') }}</span>
        </h6>
        <span class="badge badge-secondary">Read-only (Admin view)</span>
    </div>
    <div class="card-body">
        @if($students->count() < 1)
            <div class="alert alert-warning">No students found in this class/section.</div>
        @else
        <table class="table table-bordered table-sm">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Adm No</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $i => $st)
                @php $current = $existing[$st->user_id] ?? 'absent'; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $st->user->name }}</td>
                    <td>{{ $st->adm_no }}</td>
                    <td>
                        @if($current === 'present')
                            <span class="badge badge-success">Present</span>
                        @elseif($current === 'late')
                            <span class="badge badge-warning">Late</span>
                        @else
                            <span class="badge badge-danger">Absent</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <a href="{{ route('attendance.sessions') }}" class="btn btn-secondary btn-sm mt-2">
            <i class="bi bi-arrow-left mr-1"></i>Back to Sessions
        </a>
        @endif
    </div>
</div>
@endif

@endsection
