@extends('layouts.master')
@section('page_title', 'Invoice Detail')
@section('content')
@php
    $inv = $invoice;
    $statusBadge = ['unpaid'=>'danger','partial'=>'warning','paid'=>'success'][$inv->status];
@endphp

<div class="row">
    {{-- Invoice Summary --}}
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoice <code>{{ $inv->invoice_no }}</code></h6>
                <span class="badge badge-{{ $statusBadge }} px-3 py-2">{{ ucfirst($inv->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">Student</td><td><strong>{{ $inv->student->name ?? 'N/A' }}</strong></td></tr>
                            <tr><td class="text-muted">Fee Type</td><td>{{ $inv->fee_structure->category->name ?? '-' }}</td></tr>
                            <tr><td class="text-muted">Class</td><td>{{ $inv->fee_structure->my_class->name ?? '-' }}</td></tr>
                            <tr><td class="text-muted">Session</td><td>{{ $inv->session }}</td></tr>
                            <tr><td class="text-muted">Due Date</td><td>{{ $inv->due_date ?? 'Not set' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">Original Amount</td><td>ETB {{ number_format($inv->original_amount, 2) }}</td></tr>
                            <tr><td class="text-muted">Discount</td><td class="text-success">- ETB {{ number_format($inv->discount, 2) }}
                                @if($inv->discount_reason) <small class="text-muted">({{ $inv->discount_reason }})</small> @endif
                            </td></tr>
                            <tr><td class="text-muted">Fine</td><td class="text-danger">+ ETB {{ number_format($inv->fine, 2) }}
                                @if($inv->fine_reason) <small class="text-muted">({{ $inv->fine_reason }})</small> @endif
                            </td></tr>
                            <tr><td class="text-muted">Net Amount</td><td><strong>ETB {{ number_format($inv->net_amount, 2) }}</strong></td></tr>
                            <tr><td class="text-muted">Amount Paid</td><td class="text-success"><strong>ETB {{ number_format($inv->amount_paid, 2) }}</strong></td></tr>
                            <tr><td class="text-muted">Balance</td><td class="text-danger"><strong>ETB {{ number_format($inv->balance, 2) }}</strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment History --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history mr-2"></i>Payment History</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>#</th><th>Receipt No</th><th>Amount</th><th>Method</th><th>Installment</th><th>Collected By</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($inv->payments as $i => $pmt)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td><code>{{ $pmt->receipt_no }}</code></td>
                            <td class="text-success font-weight-bold">ETB {{ number_format($pmt->amount, 2) }}</td>
                            <td>{{ ucwords(str_replace('_',' ',$pmt->payment_method)) }}</td>
                            <td>Installment {{ $pmt->installment_no }}</td>
                            <td>{{ $pmt->collector->name ?? '-' }}</td>
                            <td>{{ $pmt->paid_at ? $pmt->paid_at->format('d M Y H:i') : '-' }}</td>
                            <td><a href="{{ route('fees.receipt', $pmt->id) }}" class="btn btn-xs btn-secondary" target="_blank"><i class="bi bi-printer"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Actions Panel --}}
    <div class="col-md-4">
        {{-- Record Payment --}}
        @if($inv->status !== 'paid')
        <div class="card mb-3 border-success">
            <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="bi bi-cash-coin mr-2"></i>Record Payment (Installment {{ $installment_no }})</h6></div>
            <div class="card-body">
                <form action="{{ route('fees.pay', $inv->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Amount (ETB) <small class="text-muted">Balance: {{ number_format($inv->balance,2) }}</small></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" max="{{ $inv->balance }}" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="chapa">Chapa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transaction Ref <small class="text-muted">(optional)</small></label>
                        <input type="text" name="transaction_ref" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-success btn-block">Record Payment</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Apply Discount --}}
        <div class="card mb-3 border-info">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-percent mr-2"></i>Discount / Scholarship</h6></div>
            <div class="card-body">
                <form action="{{ route('fees.discount', $inv->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Discount Amount (ETB)</label>
                        <input type="number" name="discount" class="form-control" step="0.01" min="0" value="{{ $inv->discount }}">
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <input type="text" name="discount_reason" class="form-control" value="{{ $inv->discount_reason }}" placeholder="e.g. Scholarship">
                    </div>
                    <button class="btn btn-info btn-sm btn-block">Apply Discount</button>
                </form>
            </div>
        </div>

        {{-- Apply Fine --}}
        <div class="card border-danger">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-exclamation-triangle mr-2"></i>Late Fine</h6></div>
            <div class="card-body">
                <form action="{{ route('fees.fine', $inv->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Fine Amount (ETB)</label>
                        <input type="number" name="fine" class="form-control" step="0.01" min="0" value="{{ $inv->fine }}">
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <input type="text" name="fine_reason" class="form-control" value="{{ $inv->fine_reason }}" placeholder="e.g. Late payment">
                    </div>
                    <button class="btn btn-danger btn-sm btn-block">Apply Fine</button>
                </form>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('fees.invoices') }}" class="btn btn-light"><i class="bi bi-arrow-left mr-1"></i>Back to Invoices</a>
@endsection
