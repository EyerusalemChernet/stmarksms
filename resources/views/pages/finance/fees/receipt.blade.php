@extends('layouts.master')
@section('page_title', 'Payment Receipt')
@section('content')
<div class="d-flex justify-content-end mb-3 d-print-none">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer mr-1"></i>Print</button>
    <a href="{{ route('fees.invoice', $payment->invoice_id) }}" class="btn btn-light btn-sm ml-2"><i class="bi bi-arrow-left mr-1"></i>Back</a>
</div>
<div class="card mx-auto" style="max-width:600px;" id="receipt-card">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <h4 style="font-weight:800;color:#1e293b;">{{ $settings['system_name'] ?? config('app.name') }}</h4>
            <div style="font-size:12px;color:#64748b;">{{ $settings['address'] ?? '' }}</div>
            <div style="font-size:12px;color:#64748b;">{{ $settings['phone'] ?? '' }}</div>
            <div style="margin:12px 0;border-top:2px solid #1e293b;border-bottom:2px solid #1e293b;padding:4px 0;">
                <strong style="font-size:16px;letter-spacing:2px;">PAYMENT RECEIPT</strong>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-6">
                <table style="font-size:13px;width:100%">
                    <tr><td style="color:#64748b;padding:2px 0">Receipt No</td><td><strong>{{ $payment->receipt_no }}</strong></td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Date</td><td>{{ $payment->paid_at ? $payment->paid_at->format('d M Y H:i') : '-' }}</td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Installment</td><td>#{{ $payment->installment_no }}</td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Method</td><td>{{ ucfirst(str_replace('_',' ',$payment->payment_method)) }}</td></tr>
                    @if($payment->transaction_ref)
                    <tr><td style="color:#64748b;padding:2px 0">Ref</td><td>{{ $payment->transaction_ref }}</td></tr>
                    @endif
                </table>
            </div>
            <div class="col-6">
                <table style="font-size:13px;width:100%">
                    <tr><td style="color:#64748b;padding:2px 0">Student</td><td><strong>{{ $payment->student->name ?? '-' }}</strong></td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Class</td><td>{{ $payment->invoice->fee_structure->my_class->name ?? '-' }}</td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Session</td><td>{{ $payment->invoice->session }}</td></tr>
                    <tr><td style="color:#64748b;padding:2px 0">Collected By</td><td>{{ $payment->collector->name ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
        <table class="table table-sm" style="font-size:13px;">
            <thead style="background:#f1f5f9;"><tr><th>Description</th><th class="text-right">Amount (ETB)</th></tr></thead>
            <tbody>
                <tr><td>{{ $payment->invoice->fee_structure->category->name ?? 'Fee' }}</td><td class="text-right">{{ number_format($payment->invoice->original_amount,2) }}</td></tr>
                @if($payment->invoice->discount > 0)
                <tr><td class="text-success">Discount ({{ $payment->invoice->discount_reason }})</td><td class="text-right text-success">- {{ number_format($payment->invoice->discount,2) }}</td></tr>
                @endif
                @if($payment->invoice->fine > 0)
                <tr><td class="text-danger">Fine ({{ $payment->invoice->fine_reason }})</td><td class="text-right text-danger">+ {{ number_format($payment->invoice->fine,2) }}</td></tr>
                @endif
                <tr style="border-top:1px solid #cbd5e1"><td><strong>Net Amount</strong></td><td class="text-right"><strong>{{ number_format($payment->invoice->net_amount,2) }}</strong></td></tr>
            </tbody>
        </table>
        <div style="background:#f8fafc;border-radius:8px;padding:16px;margin-top:8px;">
            <div class="d-flex justify-content-between mb-1">
                <span style="color:#64748b;font-size:13px;">Amount Paid (This Receipt)</span>
                <span style="font-size:18px;font-weight:700;color:#22c55e;">ETB {{ number_format($payment->amount,2) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span style="color:#64748b;font-size:13px;">Total Paid to Date</span>
                <span style="font-size:13px;">ETB {{ number_format($payment->invoice->amount_paid,2) }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span style="color:#64748b;font-size:13px;">Remaining Balance</span>
                <span style="font-size:13px;color:{{ $payment->invoice->balance > 0 ? '#ef4444' : '#22c55e' }};font-weight:600;">ETB {{ number_format($payment->invoice->balance,2) }}</span>
            </div>
        </div>
        @if($payment->notes)
        <div class="mt-3" style="font-size:12px;color:#64748b;"><strong>Notes:</strong> {{ $payment->notes }}</div>
        @endif
        <div class="text-center mt-4 pt-3" style="border-top:1px dashed #cbd5e1;font-size:11px;color:#94a3b8;">
            Computer-generated receipt. No signature required.<br>
            {{ $settings['system_name'] ?? config('app.name') }} — {{ now()->format('d M Y') }}
        </div>
    </div>
</div>
@endsection
@section('scripts')
<style>
@media print {
    .d-print-none { display:none !important; }
    .sidebar-main, .navbar { display:none !important; }
    #receipt-card { box-shadow:none !important; border:none !important; max-width:100% !important; }
    body { background:white !important; }
}
</style>
@endsection
