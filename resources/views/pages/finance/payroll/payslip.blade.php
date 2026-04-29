@extends('layouts.master')
@section('page_title', 'Salary Slip')
@section('content')
<div class="card border-0 shadow-sm" style="max-width:800px;margin:20px auto;border-radius:15px;overflow:hidden;">
    <div class="card-body p-5">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 class="mb-1 font-weight-bold" style="color:#1e293b;">SALARY SLIP</h4>
                <p class="text-muted mb-0">Month: <span class="text-dark font-weight-semibold">{{ date('F Y', strtotime($record->month)) }}</span></p>
                <p class="text-muted mb-0">Payment Status: <span class="badge {{ $record->status == 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($record->status) }}</span></p>
            </div>
            <div class="text-right">
                <h5 class="mb-1 font-weight-bold">{{ $settings['system_name'] ?? 'ST. MARKS SCHOOL' }}</h5>
                <p class="text-muted small mb-0">{{ $settings['address'] ?? 'School Address' }}</p>
                <p class="text-muted small mb-0">Phone: {{ $settings['phone'] ?? '+251 XXX XXX XXXX' }}</p>
            </div>
        </div>

        <hr style="border-top:1px solid #e2e8f0;margin:24px 0;">

        <div class="row mb-4">
            <div class="col-6">
                <div style="font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Employee Details</div>
                <h6 class="mb-1 font-weight-bold" style="color:#334155;">{{ $record->staff->name }}</h6>
                <p class="text-muted small mb-1">ID: STAFF/{{ str_pad($record->staff->id, 4, '0', STR_PAD_LEFT) }}</p>
                <p class="text-muted small mb-0">Role: {{ ucwords(str_replace('_',' ',$record->staff->user_type)) }}</p>
            </div>
            <div class="col-6 text-right">
                <div style="font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Payment Details</div>
                <p class="text-muted mb-1 small">Payment ID: <code style="color:#475569;">PAY-{{ strtoupper(uniqid()) }}</code></p>
                <p class="text-muted mb-1 small">Payment Method: {{ ucwords(str_replace('_',' ',$record->payment_method)) }}</p>
                <p class="text-muted small">Date: {{ $record->updated_at->format('d M Y') }}</p>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered" style="border-color:#f1f5f9;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="color:#64748b;font-weight:600;font-size:13px;border-color:#f1f5f9;">Description</th>
                        <th class="text-right" style="color:#64748b;font-weight:600;font-size:13px;border-color:#f1f5f9;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="color:#475569;border-color:#f1f5f9;">Basic Salary</td>
                        <td class="text-right" style="color:#0f172a;border-color:#f1f5f9;">ETB {{ number_format($record->basic_salary, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color:#475569;border-color:#f1f5f9;">Allowances</td>
                        <td class="text-right text-success" style="border-color:#f1f5f9;">+ ETB {{ number_format($record->allowances, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color:#475569;border-color:#f1f5f9;">Deductions</td>
                        <td class="text-right text-danger" style="border-color:#f1f5f9;">- ETB {{ number_format($record->deductions, 2) }}</td>
                    </tr>
                    <tr style="background:#f8fafc;font-weight:700;">
                        <td style="color:#1e293b;border-color:#f1f5f9;">NET SALARY</td>
                        <td class="text-right" style="color:#1e293b;border-color:#f1f5f9;font-size:16px;">ETB {{ number_format($record->net_salary, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($record->notes)
        <div class="bg-light p-3 border-left border-primary" style="font-size:13px;border-radius:6px;border-left-width:4px !important;">
            <strong class="d-block mb-1">Notes:</strong>
            {{ $record->notes }}
        </div>
        @endif

        <div class="mt-5 pt-4 d-flex justify-content-between align-items-end">
            <div class="text-center" style="width:180px;">
                <div style="border-bottom:1px solid #cbd5e1;padding-bottom:5px;margin-bottom:8px;"></div>
                <div class="small text-muted">Employee Signature</div>
            </div>
            <div class="text-center" style="width:180px;">
                <div style="border-bottom:1px solid #cbd5e1;padding-bottom:5px;margin-bottom:8px;"></div>
                <div class="small text-muted">Authorized By</div>
            </div>
        </div>

        <div class="mt-5 text-center d-print-none">
            <button onclick="window.print()" class="btn btn-primary" style="border-radius:10px;padding:10px 24px;">
                <i class="bi bi-printer mr-2"></i> Print Payslip
            </button>
            <a href="{{ route('finance.payroll.index') }}" class="btn btn-link text-muted">Back to Payroll</a>
        </div>
    </div>
</div>
@endsection
