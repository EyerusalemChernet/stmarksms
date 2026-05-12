@extends('layouts.master')
@section('page_title', 'Transport Payments')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('transport.payments.store') }}" method="POST" class="row align-items-end">@csrf
                    <div class="col-md-3"><label>Student ID</label><input type="number" name="student_id" class="form-control form-control-sm" required placeholder="User ID"></div>
                    <div class="col-md-3"><label>Route</label><select name="transport_id" class="form-control form-control-sm" required><option value="">Select Route</option>@foreach($transports as $t)<option value="{{ $t->id }}">{{ $t->route_name }} ({{ number_format($t->fee, 2) }})</option>@endforeach</select></div>
                    <div class="col-md-2"><label>Month</label><select name="month" class="form-control form-control-sm" required>@foreach(['September','October','November','December','January','February','March','April','May','June','July','August'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label>Session</label><input type="text" name="session" class="form-control form-control-sm" value="{{ $session }}" required></div>
                    <div class="col-md-1"><label>Amount</label><input type="number" name="amount" class="form-control form-control-sm" required step="0.01"></div>
                    <div class="col-md-1"><button class="btn btn-primary btn-sm btn-block"><i class="bi bi-cash"></i> Pay</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history mr-2"></i>Recent Transport Payments</h6></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light"><tr><th>Student</th><th>Route</th><th>Session</th><th>Month</th><th>Amount</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td>{{ $p->student->name ?? '-' }}</td>
                    <td>{{ $p->transport->route_name ?? '-' }}</td>
                    <td>{{ $p->session }}</td>
                    <td>{{ $p->month }}</td>
                    <td>{{ number_format($p->amount, 2) }}</td>
                    <td>{{ $p->paid_at->format('d-M-Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No transport payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
