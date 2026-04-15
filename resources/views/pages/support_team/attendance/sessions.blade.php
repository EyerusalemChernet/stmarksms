@extends('layouts.master')
@section('page_title', 'Attendance Sessions')
@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">All Attendance Sessions</h6>
        <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-primary">New Session</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr><th>#</th><th>Class</th><th>Section</th><th>Date</th><th>Teacher</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach($sessions as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->my_class->name ?? '-' }}</td>
                    <td>{{ $s->section->name ?? '-' }}</td>
                    <td>{{ $s->date }}</td>
                    <td>{{ $s->teacher->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('attendance.manage', $s->id) }}" class="btn btn-xs btn-info">View/Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $sessions->links() }}</div>
    </div>
</div>
@endsection
