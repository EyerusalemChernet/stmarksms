@extends('layouts.master')
@section('page_title', 'Staff Attendance')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-clipboard-check mr-2"></i>Staff Attendance</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to HR</a>
</div>

<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Mark Attendance</h6></div>
    <div class="card-body">
        <form action="{{ route('hr.attendance.save') }}" method="POST">
            @csrf
            <div class="form-group row align-items-center mb-3">
                <label class="col-sm-2 col-form-label font-weight-bold">Date</label>
                <div class="col-sm-4">
                    <input type="date" name="date" class="form-control" value="{{ $today }}" max="{{ $today }}" required>
                </div>
            </div>
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr><th>Staff Name</th><th>Role</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($staff as $s)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <img src="{{ $s->photo }}" width="28" height="28" class="rounded-circle" style="object-fit:cover;">
                                {{ $s->name }}
                            </div>
                        </td>
                        <td><span class="badge badge-secondary">{{ ucwords(str_replace('_',' ',$s->user_type)) }}</span></td>
                        <td>
                            @php $current = $todayRecords[$s->id] ?? 'present'; @endphp
                            <select name="attendance[{{ $s->id }}]" class="form-control form-control-sm" style="width:130px;">
                                <option value="present" {{ $current === 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent"  {{ $current === 'absent'  ? 'selected' : '' }}>Absent</option>
                                <option value="late"    {{ $current === 'late'    ? 'selected' : '' }}>Late</option>
                                <option value="leave"   {{ $current === 'leave'   ? 'selected' : '' }}>On Leave</option>
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle mr-1"></i>Save Attendance</button>
        </form>
    </div>
</div>
@endsection
