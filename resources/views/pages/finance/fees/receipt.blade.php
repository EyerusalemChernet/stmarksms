@extends('layouts.master')
@section('page_title', 'Payment Receipt')
@section('content')
<div class="d-flex justify-content-end mb-3 d-print-none">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer mr-1"></i>Print</button>
    <a href="{{ route('fees.invoice', Qs::hash($payment->invoice_id)) }}" class="btn btn-light btn-sm ml-2"><i class="bi bi-arrow-left mr-1"></i>Back</a>
</div>

<div class="card mx-auto" style="max-width:600px;" id="receipt-area">
    <div class="card-body p-4">
        {{-- Header --}}
        <div class="text-center mb-4">
            <h4 style="font-weight:800;color:#1e293b;">{{ $settings['system_name'] ?? config('app.name') }}</h4>
            <div style="font-size:12px;color:#64748b;">{{ $settings['address'] ?? '' }}</div>
            <div style="font-size:12px;color:#64748b;">{{ $settings['phone'] ?? '' }} | {{ $settings['system_email'] ?? '' }}</div>
            <div style="margin:12px 0;border-top:2px solid #1e293b;border-bottom:2px solid #1e293b;padding:4px 0;">
                <strong style="font-size:16px;letter-spacing:2px;">PAYMENT RECEIPT</strong>
            </div>
        </div>

        {{-- Receipt Info --}}
        <div class="row mb-3">
            <div class="col-6">
                <table style="font-size:13px;width:100%">
                    <tr><td style="color:#64748b;padding:2px 0">Receipt No</td><td><strong>{{ $payment->receipt_no }}</strong></td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Date</td><td>{{ $payment->paid_at ? $payment->paid_at->format('d M Y H:i') : '-' }}</td></tr>
                </table>
            </div>
            <div class="col-6">
                <table style="font-size:13px;width:100%">
                    <tr><td style="color:#64748b;padding:2px 0">Student</td><td><strong>{{ $payment->student->name ?? '-' }}</strong></td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Class</td><td>{{ $payment->invoice->fee_structure->my_class->name ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        <table class="table table-sm" style="font-size:13px;">
            <thead style="background:#f1f5f9;"><tr><th>Description</th><th class="text-right">Amount (ETB)</th></tr></thead>
            <tbody>
                <tr><td>{{ $payment->invoice->fee_structure->category->name ?? 'Fee' }} — Session {{ $payment->invoice->session }}</td><td class="text-right">{{ number_format($payment->invoice->original_amount,2) }}</td></tr>
                @if($payment->invoice->discount > 0)
                <tr><td class="text-success">Discount ({{ $payment->invoice->discount_reason }})</td><td class="text-right text-success">- {{ number_format($payment->invoice->discount,2) }}</td></tr>
                @endif
                @if($payment->invoice->fine > 0)
                <tr><td class="text-danger">Fine ({{ $payment->invoice->fine_reason }})</td><td class="text-right text-danger">+ {{ number_format($payment->invoice->fine,2) }}</td></tr>
                @endif
                <tr style="border-top:1px solid #cbd5e1"><td><strong>Net Payable</strong></td><td class="text-right"><strong>{{ number_format($payment->invoice->net_amount,2) }}</strong></td></tr>
            </tbody>
        </table>

        <div style="background:#f0fdf4;border-radius:8px;padding:16px;margin-top:8px;border:1px solid #bbf7d0;" class="text-center">
            <div style="font-size:12px;color:#16a34a;text-transform:uppercase;">Amount Paid (This Transaction)</div>
            <div style="font-size:28px;font-weight:800;color:#16a34a;">ETB {{ number_format($payment->amount,2) }}</div>
            <div class="d-flex justify-content-between mt-2 px-3">
                <span style="color:#64748b;font-size:12px;">Payment Method: <strong>{{ ucwords(str_replace('_',' ',$payment->payment_method)) }}</strong></span>
                <span style="color:#64748b;font-size:12px;">Remaining Balance: <strong style="color:{{ $payment->invoice->balance > 0 ? '#ef4444' : '#22c55e' }};">ETB {{ number_format($payment->invoice->balance,2) }}</strong></span>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-6">
                <small style="color:#64748b;display:block;">Collected By</small>
                <div style="font-size:13px;">{{ $payment->collector->name ?? '-' }}</div>
            </div>
            <div class="col-6 text-right">
                <small style="color:#64748b;display:block;">Signature</small>
                <div style="border-bottom:1px solid #cbd5e1;width:120px;display:inline-block;margin-top:15px;"></div>
            </div>
        </div>

        <div class="text-center mt-4 pt-3" style="border-top:1px dashed #cbd5e1;font-size:11px;color:#94a3b8;">
            Computer-generated receipt. No signature required if printed.<br>
            {{ $settings['system_name'] ?? config('app.name') }} — {{ now()->format('d M Y') }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
@media print {
    .d-print-none, .sidebar, .navbar { display:none !important; }
    #receipt-area { box-shadow:none !important; border:none !important; max-width:100% !important; }
    body { background:white !important; }
}
</style>
@endsection
