@extends('layouts.master')
@section('page_title', 'Academic Reports')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-journal-check mr-2"></i>Academic Reports — {{ $year }}</h5>
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to Reports</a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.academic') }}" class="form-inline" style="gap:8px;">
            <select name="class_id" class="form-control form-control-sm">
                <option value="">All Classes</option>
                @foreach($allClasses as $c)
                    <option value="{{ $c->id }}" {{ $class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel mr-1"></i>Filter</button>
            <a href="{{ route('reports.academic', array_merge(request()->query(), ['export'=>'pdf'])) }}" class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
            <a href="{{ route('reports.academic', array_merge(request()->query(), ['export'=>'csv'])) }}" class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Average Score Per Class</h6></div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0 datatable-basic">
                    <thead class="thead-light">
                        <tr><th>Class</th><th>Students</th><th>Average Score</th><th>Performance</th></tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->student_count }}</td>
                            <td>{{ $c->avg_score }}</td>
                            <td>
                                <div class="progress" style="height:16px;">
                                    <div class="progress-bar {{ $c->avg_score >= 50 ? 'bg-success' : 'bg-warning' }}"
                                         style="width:{{ $c->avg_score }}%">{{ $c->avg_score }}%</div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Top 10 Students</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>#</th><th>Student</th><th>Class</th><th>Avg</th></tr>
                    </thead>
                    <tbody>
                        @foreach($topStudents as $i => $exr)
                        <tr>
                            <td>
                                @if($i === 0) 🥇
                                @elseif($i === 1) 🥈
                                @elseif($i === 2) 🥉
                                @else {{ $i + 1 }}
                                @endif
                            </td>
                            <td>{{ $exr->student->name ?? '-' }}</td>
                            <td class="small text-muted">{{ $exr->student->student_record->my_class->name ?? '—' }}</td>
                            <td><span class="badge badge-success">{{ round($exr->overall_avg, 1) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
