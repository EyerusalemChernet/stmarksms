<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt - {{ $payment->receipt_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .school-name { font-size: 24px; font-weight: bold; color: #333; }
        .receipt-title { font-size: 18px; color: #666; margin-top: 10px; }
        .receipt-no { font-size: 16px; color: #999; }
        .info-section { margin-bottom: 25px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .label { font-weight: bold; }
        .amount-section { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .total-amount { font-size: 20px; font-weight: bold; color: #28a745; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { text-align: center; width: 200px; }
        .signature-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="header">
        <div class="school-name">{{ $settings['system_name'] ?? 'St. Mark School Management System' }}</div>
        <div class="receipt-title">OFFICIAL PAYMENT RECEIPT</div>
        <div class="receipt-no">Receipt No: {{ $payment->receipt_no }}</div>
    </div>

    <div class="info-section">
        <h3>Student Information</h3>
        <div class="info-row">
            <span class="label">Student Name:</span>
            <span>{{ $payment->student->name }}</span>
        </div>
        <div class="info-row">
            <span class="label">Admission Number:</span>
            <span>{{ optional($payment->student->studentRecord)->adm_no ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="label">Class:</span>
            <span>{{ optional($payment->invoice->fee_structure->my_class)->name ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="info-section">
        <h3>Payment Details</h3>
        <div class="info-row">
            <span class="label">Fee Category:</span>
            <span>{{ optional($payment->invoice->fee_structure->category)->name ?? 'School Fee' }}</span>
        </div>
        <div class="info-row">
            <span class="label">Invoice Number:</span>
            <span>{{ $payment->invoice->invoice_no }}</span>
        </div>
        <div class="info-row">
            <span class="label">Payment Date:</span>
            <span>{{ $payment->paid_at->format('F d, Y') }}</span>
        </div>
        <div class="info-row">
            <span class="label">Payment Method:</span>
            <span>{{ ucfirst($payment->payment_method) }}</span>
        </div>
        @if($payment->transaction_ref)
        <div class="info-row">
            <span class="label">Transaction Reference:</span>
            <span>{{ $payment->transaction_ref }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="label">Installment:</span>
            <span>{{ $payment->installment_no }}</span>
        </div>
    </div>

    <div class="amount-section">
        <div class="info-row">
            <span class="label">Amount Paid:</span>
            <span class="total-amount">ETB {{ number_format($payment->amount, 2) }}</span>
        </div>
    </div>

    @if($payment->notes)
    <div class="info-section">
        <h3>Notes</h3>
        <p>{{ $payment->notes }}</p>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">Received By</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Parent/Guardian Signature</div>
        </div>
    </div>

    <div class="footer">
        <p>This is an official receipt generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        <p>{{ $settings['system_name'] ?? 'St. Mark School Management System' }} - Finance Department</p>
        @if($payment->payment_method === 'chapa')
        <p><strong>Payment processed securely through Chapa Payment Gateway</strong></p>
        @endif
    </div>
</body>
</html>