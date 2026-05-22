@extends('layouts.master')
@section('page_title', 'Chapa Payment History')
@section('content')

<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-credit-card mr-2"></i>Chapa Payments</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead class="thead-light">
                <tr>
                    <th>Receipt</th>
                    <th>Student</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pay)
                <tr>
                    <td><code>{{ $pay->receipt_no }}</code></td>
                    <td>{{ $pay->student->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('fees.invoice', $pay->invoice_id) }}">{{ $pay->invoice->invoice_no ?? '-' }}</a>
                    </td>
                    <td class="text-success font-weight-bold">ETB {{ number_format($pay->amount, 2) }}</td>
                    <td>{{ $pay->paid_at ? $pay->paid_at->format('d M Y H:i') : '-' }}</td>
                    <td><small>{{ $pay->transaction_ref }}</small></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No Chapa payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="card-footer">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
