@extends('layouts.master')
@section('page_title', 'Pending Balances')
@section('content')

<div class="card mb-3 border-danger">
    <div class="card-body py-2 d-flex justify-content-between align-items-center">
        <div>
            <span class="text-muted" style="font-size:12px;">TOTAL PENDING BALANCE</span>
            <div style="font-size:24px;font-weight:700;color:#ef4444;">ETB {{ number_format($total_pending, 2) }}</div>
        </div>
        <form method="GET" class="d-flex" style="gap:8px;">
            <select name="class_id" class="form-control form-control-sm" style="width:140px">
                <option value="">All Classes</option>
                @foreach($classes as $cls)
                <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>{{ $cls->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-exclamation-circle mr-2 text-danger"></i>Pending / Partial Payments — {{ $session }}</h6></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead class="thead-light">
                <tr><th>#</th><th>Student</th><th>Fee Type</th><th>Class</th><th>Net Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($invoices as $i => $inv)
                <tr class="{{ $inv->status === 'unpaid' ? 'table-danger' : 'table-warning' }}" style="--bs-table-bg:transparent;">
                    <td>{{ $i+1 }}</td>
                    <td>
                        <a href="{{ route('fees.student_history', $inv->student_id) }}">{{ $inv->student->name ?? 'N/A' }}</a>
                    </td>
                    <td>{{ $inv->fee_structure->category->name ?? '-' }}</td>
                    <td>{{ $inv->fee_structure->my_class->name ?? '-' }}</td>
                    <td>{{ number_format($inv->net_amount, 2) }}</td>
                    <td class="text-success">{{ number_format($inv->amount_paid, 2) }}</td>
                    <td class="text-danger font-weight-bold">{{ number_format($inv->balance, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $inv->status === 'unpaid' ? 'danger' : 'warning' }}">{{ ucfirst($inv->status) }}</span>
                    </td>
                    <td>
                        @if($inv->due_date && $inv->due_date < now()->toDateString())
                            <span class="text-danger"><i class="bi bi-alarm mr-1"></i>{{ $inv->due_date }} <small>(Overdue)</small></span>
                        @else
                            {{ $inv->due_date ?? '-' }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('fees.invoice', $inv->id) }}" class="btn btn-primary btn-xs"><i class="bi bi-cash-coin mr-1"></i>Pay</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-4"><i class="bi bi-check-circle text-success mr-2"></i>No pending balances.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
