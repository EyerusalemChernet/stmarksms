@extends('layouts.master')
@section('page_title', $sr->user->name . ' — Details')
@section('content')

{{-- Breadcrumb + back --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent p-0">
        <li class="breadcrumb-item"><a href="{{ route('parent.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">{{ $sr->user->name }}</li>
    </ol>
</nav>

{{-- Profile header --}}
<div class="card mb-3">
    <div class="card-body d-flex align-items-center">
        <img src="{{ $sr->user->photo }}" class="rounded-circle mr-4" width="72" height="72" alt="photo">
        <div>
            <h5 class="mb-1 font-weight-bold">{{ $sr->user->name }}</h5>
            <p class="mb-0 text-muted">
                <span class="badge badge-primary mr-1">{{ $sr->my_class->name ?? '-' }}</span>
                <span class="badge badge-secondary mr-1">{{ $sr->section->name ?? '-' }}</span>
                Adm No: {{ $sr->adm_no }} &bull; Year: {{ $sr->year_admitted }}
            </p>
        </div>
        <div class="ml-auto">
            <a href="{{ route('parent.timeline', $sr->user_id) }}" class="btn btn-sm btn-outline-primary mr-1">
                <i class="icon-list mr-1"></i> Timeline
            </a>
            @if(!$blocked)
            <a href="{{ route('marks.show', [$sr->user_id, $year]) }}" class="btn btn-sm btn-outline-success" target="_blank">
                <i class="bi bi-file-pdf mr-1"></i> Report Card
            </a>
            @endif
        </div>
    </div>
</div>

@if($blocked)
<div class="alert alert-danger">
    <i class="icon-lock2 mr-2"></i>
    <strong>Results Blocked:</strong> Exam results are currently blocked due to low attendance or outstanding fees. Please contact the school.
</div>
@endif

<div class="row">
    {{-- Attendance --}}
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="icon-calendar3 mr-2 text-success"></i>Attendance — {{ $year }}</h6></div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <h4 class="{{ $attPct >= 75 ? 'text-success' : 'text-danger' }}">{{ $attPct }}%</h4>
                        <small class="text-muted">Rate</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-success">{{ $present }}</h4>
                        <small class="text-muted">Present</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-danger">{{ $total - $present }}</h4>
                        <small class="text-muted">Absent</small>
                    </div>
                </div>
                <div class="progress mb-3" style="height:10px;">
                    <div class="progress-bar {{ $attPct >= 75 ? 'bg-success' : 'bg-danger' }}" style="width:{{ $attPct }}%"></div>
                </div>
                <div style="max-height:200px;overflow-y:auto;">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light"><tr><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($attRecords->take(20) as $r)
                            <tr>
                                <td>{{ $r->session->date ?? '-' }}</td>
                                <td><span class="badge badge-{{ $r->status === 'present' ? 'success' : ($r->status === 'late' ? 'warning' : 'danger') }}">{{ ucfirst($r->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Exam Results --}}
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="icon-books mr-2 text-warning"></i>Exam Results — {{ $year }}</h6></div>
            <div class="card-body">
                @if($blocked)
                    <div class="text-center text-danger py-4"><i class="icon-lock2 icon-2x mb-2"></i><p>Results are currently blocked.</p></div>
                @elseif($examRecords->isEmpty())
                    <p class="text-muted text-center py-4">No exam results recorded yet.</p>
                @else
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light"><tr><th>Exam</th><th>Total</th><th>Avg</th><th>Position</th></tr></thead>
                        <tbody>
                            @foreach($examRecords as $exr)
                            <tr>
                                <td>{{ $exr->exam->name ?? '-' }}</td>
                                <td>{{ $exr->total ?? '-' }}</td>
                                <td>{{ $exr->ave ?? '-' }}</td>
                                <td>{{ $exr->pos ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Fees --}}
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="icon-coin-dollar mr-2 text-danger"></i>Fee Status</h6></div>
            <div class="card-body">
                @if($unpaidFees->isEmpty())
                    <div class="text-center text-success py-3"><i class="icon-checkmark-circle icon-2x mb-2"></i><p class="mb-0">All fees are cleared.</p></div>
                @else
                    <div class="alert alert-warning py-2 mb-2"><i class="icon-warning2 mr-1"></i> {{ $unpaidFees->count() }} outstanding payment(s)</div>
                    <table class="table table-sm table-bordered mb-2">
                        <thead class="thead-light"><tr><th>Fee</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Pay Online</th></tr></thead>
                        <tbody>
                            @foreach($unpaidFees as $pr)
                            <tr>
                                <td>{{ $pr->payment->title ?? '-' }}</td>
                                <td>ETB {{ number_format($pr->payment->amount ?? 0) }}</td>
                                <td>ETB {{ number_format($pr->amt_paid ?? 0) }}</td>
                                <td class="text-danger">ETB {{ number_format(($pr->payment->amount ?? 0) - ($pr->amt_paid ?? 0)) }}</td>
                                <td>
                                    <form action="{{ route('chapa.initiate', $pr->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-warning">
                                            <i class="bi bi-credit-card mr-1"></i>Pay via Chapa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                @if($paidFees->isNotEmpty())
                <p class="font-weight-semibold mb-1 mt-2">Payment History:</p>
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light"><tr><th>Fee</th><th>Amount Paid</th></tr></thead>
                    <tbody>
                        @foreach($paidFees as $pr)
                        <tr>
                            <td>{{ $pr->payment->title ?? '-' }}</td>
                            <td class="text-success">ETB {{ number_format($pr->amt_paid ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Library --}}
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="icon-book mr-2 text-info"></i>Library</h6></div>
            <div class="card-body">
                @if($borrowed->isNotEmpty())
                <p class="font-weight-semibold mb-1">Currently Borrowed:</p>
                <ul class="list-unstyled mb-3">
                    @foreach($borrowed as $br)
                    <li><span class="badge badge-info mr-1">{{ ucfirst($br->status) }}</span> {{ $br->book->name ?? '-' }}</li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted small">No books currently borrowed.</p>
                @endif
                @if($borrowHistory->isNotEmpty())
                <p class="font-weight-semibold mb-1">Recent History:</p>
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light"><tr><th>Book</th><th>Returned</th></tr></thead>
                    <tbody>
                        @foreach($borrowHistory as $br)
                        <tr>
                            <td>{{ $br->book->name ?? '-' }}</td>
                            <td>{{ $br->returned_at ? \Carbon\Carbon::parse($br->returned_at)->format('d M Y') : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Messages from Teachers --}}
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="icon-envelop3 mr-2 text-primary"></i>Messages from Teachers</h6>
                <a href="{{ route('compose') }}" class="btn btn-xs btn-primary">Reply / Compose</a>
            </div>
            <div class="card-body p-0">
                @forelse($messages as $msg)
                <div class="p-3 border-bottom {{ !$msg->read ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $msg->sender->name ?? 'Teacher' }}</strong>
                        <small class="text-muted">{{ $msg->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-0 mt-1">{{ $msg->body }}</p>
                </div>
                @empty
                <p class="p-3 text-muted mb-0">No messages from teachers yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
