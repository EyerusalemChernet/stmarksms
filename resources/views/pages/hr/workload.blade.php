@extends('layouts.master')
@section('page_title', 'Teacher Workload')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-bar-chart mr-2"></i>Teacher Workload Overview</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to HR</a>
</div>

<div class="row">
    @forelse($teachers as $teacher)
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex align-items-center" style="gap:10px;">
                <img src="{{ $teacher->photo }}" width="36" height="36" class="rounded-circle" style="object-fit:cover;">
                <div>
                    <strong>{{ $teacher->name }}</strong>
                    <div class="small text-muted">{{ $teacher->email }}</div>
                </div>
                <span class="badge badge-primary ml-auto">{{ $teacher->subjects->count() }} subjects</span>
            </div>
            <div class="card-body p-0">
                @if($teacher->subjects->count())
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Subject</th><th>Class</th></tr></thead>
                    <tbody>
                        @foreach($teacher->subjects as $sub)
                        <tr>
                            <td>{{ $sub->name }}</td>
                            <td>{{ $sub->my_class->name ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p class="text-muted p-3 mb-0">No subjects assigned.</p>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-12"><p class="text-muted">No teachers found.</p></div>
    @endforelse
</div>
@endsection
