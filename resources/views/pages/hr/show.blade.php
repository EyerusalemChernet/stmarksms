@extends('layouts.master')
@section('page_title', 'Staff Profile — ' . $user->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-person-badge mr-2"></i>Staff Profile</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to HR</a>
</div>

<div class="row">
    {{-- Profile Card --}}
    <div class="col-md-4">
        <div class="card text-center p-3">
            <img src="{{ $user->photo }}" width="100" height="100" class="rounded-circle mx-auto mb-2" style="object-fit:cover;border:3px solid #e0e0e0;">
            <h5 class="mb-0">{{ $user->name }}</h5>
            <p class="text-muted mb-1">{{ ucwords(str_replace('_',' ',$user->user_type)) }}</p>
            @php $dept = $user->staff->first()->department->name ?? null; @endphp
            @if($dept)
                <span class="badge badge-info">{{ $dept }}</span>
            @endif
            <hr>
            <div class="text-left small">
                <p><i class="bi bi-envelope mr-1"></i> {{ $user->email }}</p>
                <p><i class="bi bi-phone mr-1"></i> {{ $user->phone ?? '—' }}</p>
                <p><i class="bi bi-geo-alt mr-1"></i> {{ $user->address ?? '—' }}</p>
            </div>
            {{-- Assign Department --}}
            <form action="{{ route('hr.assign_department', $user->id) }}" method="POST" class="mt-2">
                @csrf
                <div class="form-group text-left">
                    <label class="small font-weight-bold">Assign Department</label>
                    <select name="department_id" class="form-control form-control-sm">
                        <option value="">— None —</option>
                        @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                            <option value="{{ $d->id }}" {{ ($user->staff->first()->department_id ?? null) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary btn-block">Update Department</button>
            </form>
        </div>

        {{-- Attendance Summary --}}
        <div class="card mt-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Attendance (Last 30 Days)</h6></div>
            <div class="card-body text-center">
                <div class="row">
                    <div class="col-4"><h4 class="text-success">{{ $presentCount }}</h4><small>Present</small></div>
                    <div class="col-4"><h4 class="text-danger">{{ $totalCount - $presentCount }}</h4><small>Absent</small></div>
                    <div class="col-4"><h4 class="text-primary">{{ $attPct }}%</h4><small>Rate</small></div>
                </div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar {{ $attPct >= 75 ? 'bg-success' : 'bg-danger' }}" style="width:{{ $attPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        {{-- Subjects / Workload --}}
        @if($subjects->count())
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Assigned Subjects</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Subject</th><th>Class</th></tr></thead>
                    <tbody>
                        @foreach($subjects as $sub)
                        <tr>
                            <td>{{ $sub->name }}</td>
                            <td>{{ $sub->my_class->name ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Recent Attendance --}}
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Recent Attendance Records</h6></div>
            <div class="card-body p-0">
                @if($attendance->count())
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Date</th><th>Status</th><th>Remark</th></tr></thead>
                    <tbody>
                        @foreach($attendance as $a)
                        <tr>
                            <td>{{ $a->date }}</td>
                            <td>
                                @php $cls = ['present'=>'success','late'=>'warning','absent'=>'danger','leave'=>'info'][$a->status] ?? 'secondary'; @endphp
                                <span class="badge badge-{{ $cls }}">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td>{{ $a->remark ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p class="text-muted p-3 mb-0">No attendance records found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
