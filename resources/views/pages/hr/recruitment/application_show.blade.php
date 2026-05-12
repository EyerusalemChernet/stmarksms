@extends('layouts.master')
@section('page_title', 'Application — ' . $application->full_name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-person-lines-fill mr-2"></i>{{ $application->full_name }}
        <span class="badge badge-{{ $application->statusBadgeClass() }} ml-1">{{ ucfirst($application->status) }}</span>
    </h5>
    <a href="{{ route('hr.recruitment.applications') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back
    </a>
</div>

<div class="row">
    {{-- Left: Applicant Info --}}
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Applicant Details</h6></div>
            <div class="card-body small">
                <p class="mb-1"><strong>Name:</strong> {{ $application->full_name }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ $application->email ?? '—' }}</p>
                <p class="mb-1"><strong>Phone:</strong> {{ $application->phone ?? '—' }}</p>
                <p class="mb-1"><strong>Address:</strong> {{ $application->address ?? '—' }}</p>
                <p class="mb-1"><strong>Applied:</strong> {{ $application->applied_at->format('d M Y') }}</p>
                @if($application->interview_date)
                <p class="mb-1"><strong>Interview:</strong> {{ $application->interview_date->format('d M Y') }}</p>
                @endif
                <hr>
                <p class="mb-1"><strong>Job:</strong> {{ $application->jobPosting?->title ?? '—' }}</p>
                <p class="mb-1"><strong>Department:</strong> {{ $application->jobPosting?->department?->name ?? '—' }}</p>
            </div>
        </div>

        @if($application->cover_letter)
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Cover Letter</h6></div>
            <div class="card-body small">{{ $application->cover_letter }}</div>
        </div>
        @endif

        {{-- Convert to Employee (hired only) --}}
        @if($application->isHired())
        <div class="card border-success mb-3">
            <div class="card-body text-center">
                <p class="text-success font-weight-bold mb-2"><i class="bi bi-check-circle mr-1"></i>Applicant Hired!</p>
                <a href="{{ route('hr.recruitment.applications.convert', $application->id) }}"
                   class="btn btn-success btn-block">
                    <i class="bi bi-person-check mr-1"></i>Convert to Employee
                </a>
                <small class="text-muted d-block mt-1">Pre-fills the employee form with this applicant's data.</small>
            </div>
        </div>
        @endif
    </div>

    {{-- Right: Pipeline + Notes --}}
    <div class="col-md-8">

        {{-- Pipeline Status Update --}}
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-arrow-right-circle mr-1"></i>Update Status</h6></div>
            <div class="card-body">
                <form action="{{ route('hr.recruitment.applications.status', $application->id) }}" method="POST">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-4 mb-0">
                            <label class="font-weight-bold small">New Status</label>
                            <select name="status" class="form-control form-control-sm">
                                @foreach(\App\Models\JobApplication::PIPELINE as $s)
                                    <option value="{{ $s }}" {{ $application->status === $s ? 'selected' : '' }}>
                                        {{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <label class="font-weight-bold small">Interview Date</label>
                            <input type="date" name="interview_date" class="form-control form-control-sm"
                                   value="{{ $application->interview_date?->format('Y-m-d') }}">
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <button type="submit" class="btn btn-primary btn-sm btn-block">
                                <i class="bi bi-check mr-1"></i>Update
                            </button>
                        </div>
                    </div>
                    <div class="form-group mt-2 mb-0">
                        <input type="text" name="note" class="form-control form-control-sm"
                               placeholder="Optional note about this status change...">
                    </div>
                </form>
            </div>
        </div>

        {{-- Pipeline visual --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    @foreach(\App\Models\JobApplication::PIPELINE as $step)
                    @php
                        $pipelineOrder = array_flip(\App\Models\JobApplication::PIPELINE);
                        $currentOrder  = $pipelineOrder[$application->status] ?? 0;
                        $stepOrder     = $pipelineOrder[$step] ?? 0;
                        $isActive      = $application->status === $step;
                        $isPast        = $stepOrder < $currentOrder && $step !== 'rejected';
                        $badgeClass    = $isActive ? 'primary' : ($isPast ? 'success' : 'light border text-muted');
                    @endphp
                    <div class="text-center" style="flex:1;">
                        <span class="badge badge-{{ $badgeClass }} d-block mb-1" style="font-size:11px;">
                            {{ ucfirst($step) }}
                        </span>
                    </div>
                    @if(!$loop->last)
                    <div class="text-muted" style="flex:0 0 20px; text-align:center;">→</div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Notes / History --}}
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-chat-left-text mr-1"></i>Notes & History</h6>
            </div>
            <div class="card-body">
                {{-- Add note --}}
                <form action="{{ route('hr.recruitment.applications.note', $application->id) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="note" class="form-control form-control-sm"
                               placeholder="Add a note..." required>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </form>

                {{-- History timeline --}}
                @forelse($application->notes as $note)
                <div class="d-flex mb-3" style="gap:10px;">
                    <div class="flex-shrink-0">
                        <span class="badge badge-{{ $note->status_changed_to ? 'primary' : 'light border' }}"
                              style="font-size:10px; padding:4px 6px;">
                            {{ $note->status_changed_to ? ucfirst($note->status_changed_to) : 'Note' }}
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="small">{{ $note->note }}</div>
                        <div class="text-muted" style="font-size:11px;">
                            {{ $note->author?->name ?? 'HR' }} · {{ $note->created_at->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-muted small mb-0">No notes yet.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
