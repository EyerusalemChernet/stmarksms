@extends('layouts.master')
@section('page_title', 'Payment Receipt')
@section('content')
<div class="d-flex justify-content-end mb-3">
    <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="bi bi-printer mr-1"></i>Print Receipt</button>
    <a href="{{ route('fees.invoice', $payment->invoice_id) }}" class="btn btn-light btn-sm ml-2"><i class="bi bi-arrow-left mr-1"></i>Back</a>
</div>

<div class="card mx-auto" style="max-width:600px;" id="receipt-area">
    <div class="card-body p-4">
        {{-- Header --}}
        <div class="text-center mb-4">
            <h4 class="font-weight-bold mb-0">{{ $settings['system_name'] ?? config('app.name') }}</h4>
            <div class="text-muted" style="font-size:12px;">{{ $settings['address'] ?? '' }}</div>
            <div class="text-muted" style="font-size:12px;">{{ $settings['phone'] ?? '' }} | {{ $settings['system_email'] ?? '' }}</div>
            <hr>
            <h5 class="font-weight-bold text-uppercase" style="letter-spacing:2px;">Payment Receipt</h5>
        </div>

        {{-- Receipt Info --}}
        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted d-block">Receipt No</small>
                <strong><code>{{ $payment->receipt_no }}</code></strong>
            </div>
            <div class="col-6 text-right">
                <small class="text-muted d-block">Date</small>
                <strong>{{ $payment->paid_at ? $payment->paid_at->format('d M Y, H:i') : now()->format('d M Y') }}</strong>
            </div>
        </div>

        <hr>

        {{-- Student Info --}}
        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted d-block">Student Name</small>
                <strong>{{ $payment->student->name ?? 'N/A' }}</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Class</small>
                <strong>{{ $payment->invoice->fee_structure->my_class->name ?? '-' }}</strong>
            </div>
        </div>

        {{-- Fee Info --}}
        <table class="table table-sm table-bordered mb-3">
            <thead class="thead-light">
                <tr><th>Description</th><th class="text-right">Amount (ETB)</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payment->invoice->fee_structure->category->name ?? 'Fee' }} — Session {{ $payment->invoice->session }}</td>
                    <td class="text-right">{{ number_format($payment->invoice->original_amount, 2) }}</td>
                </tr>
                @if($payment->invoice->discount > 0)
                <tr class="text-success">
                    <td>Discount ({{ $payment->invoice->discount_reason }})</td>
                    <td class="text-right">- {{ number_format($payment->invoice->discount, 2) }}</td>
                </tr>
                @endif
                @if($payment->invoice->fine > 0)
                <tr class="text-danger">
                    <td>Fine ({{ $payment->invoice->fine_reason }})</td>
                    <td class="text-right">+ {{ number_format($payment->invoice->fine, 2) }}</td>
                </tr>
                @endif
                <tr class="table-light">
                    <td><strong>Net Payable</strong></td>
                    <td class="text-right"><strong>{{ number_format($payment->invoice->net_amount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- Payment Summary --}}
        <div class="row">
            <div class="col-6">
                <small class="text-muted d-block">Payment Method</small>
                <strong>{{ ucwords(str_replace('_',' ', $payment->payment_method)) }}</strong>
                @if($payment->transaction_ref)
                <div style="font-size:11px;color:#94a3b8;">Ref: {{ $payment->transaction_ref }}</div>
                @endif
            </div>
            <div class="col-6 text-right">
                <small class="text-muted d-block">Installment</small>
                <strong>{{ $payment->installment_no }} of {{ $payment->invoice->fee_structure->installments }}</strong>
            </div>
        </div>

        <div class="mt-3 p-3 text-center" style="background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;">
            <div style="font-size:12px;color:#16a34a;">AMOUNT PAID THIS TRANSACTION</div>
            <div style="font-size:28px;font-weight:800;color:#16a34a;">ETB {{ number_format($payment->amount, 2) }}</div>
            <div style="font-size:12px;color:#94a3b8;">Remaining Balance: ETB {{ number_format($payment->invoice->balance, 2) }}</div>
        </div>

        <hr>
        <div class="row mt-3">
            <div class="col-6">
                <small class="text-muted d-block">Collected By</small>
                {{ $payment->collector->name ?? '-' }}
            </div>
            <div class="col-6 text-right">
                <small class="text-muted d-block">Signature</small>
                <div style="border-bottom:1px solid #ccc;width:120px;display:inline-block;margin-top:20px;"></div>
            </div>
        </div>

        <div class="text-center mt-4" style="font-size:11px;color:#94a3b8;">
            This is a computer-generated receipt. No signature required if printed.
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar-main, .navbar, .content > *:not(#receipt-area), .btn, a.btn { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
    #receipt-area { box-shadow: none !important; border: none !important; max-width: 100% !important; }
}
</style>
@endsection
