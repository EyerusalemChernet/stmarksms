@extends('layouts.master')
@section('page_title', 'Mark Attendance')
@section('breadcrumb')
    <a href="{{ route('attendance.index') }}" class="breadcrumb-item">Attendance</a>
    <span class="breadcrumb-item active">Mark</span>
@endsection
@section('content')
@include('partials.back_button')
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title">
            Attendance — {{ $session->my_class->name }} / {{ $session->section->name }}
            &nbsp;<span class="badge badge-info">{{ $session->date }}</span>
        </h6>
    </div>
    <div class="card-body">
        @if($students->count() < 1)
            <div class="alert alert-warning">No students found in this class/section.</div>
        @else
        <form method="POST" action="{{ route('attendance.store', $session->id) }}">
            @csrf
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Adm No</th>
                        <th>Present</th>
                        <th>Late</th>
                        <th>Absent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $st)
                    @php $current = $existing[$st->user_id] ?? 'absent'; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $st->user->name }}</td>
                        <td>{{ $st->adm_no }}</td>
                        <td class="text-center">
                            <input type="radio" name="status_{{ $st->user_id }}" value="present" {{ $current === 'present' ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="status_{{ $st->user_id }}" value="late" {{ $current === 'late' ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="status_{{ $st->user_id }}" value="absent" {{ $current === 'absent' ? 'checked' : '' }}>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-success mt-2">Save Attendance</button>
            <a href="{{ route('attendance.index') }}" class="btn btn-secondary mt-2 ml-2">Cancel</a>
        </form>
        @endif
    </div>
</div>
@endsection
