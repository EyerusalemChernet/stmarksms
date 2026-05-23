<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 0; padding: 20px; }
    .header { border-bottom: 3px solid #2c3e50; padding-bottom: 10px; margin-bottom: 14px; }
    .header h2 { font-size: 16px; margin: 0 0 2px; color: #2c3e50; }
    .header p  { margin: 0; font-size: 9px; color: #666; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
    .badge-draft    { background: #6c757d; color: #fff; }
    .badge-approved { background: #17a2b8; color: #fff; }
    .badge-paid     { background: #28a745; color: #fff; }
    .two-col { display: table; width: 100%; margin-bottom: 14px; }
    .col-left  { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
    .col-right { display: table-cell; width: 50%; vertical-align: top; padding-left: 10px; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 3px 0; font-size: 9px; }
    .info-table td:first-child { color: #666; width: 45%; }
    .info-table td:last-child { font-weight: bold; }
    .section-title { font-size: 10px; font-weight: bold; color: #2c3e50; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin: 12px 0 6px; }
    table.pay { width: 100%; border-collapse: collapse; }
    table.pay th { background: #2c3e50; color: #fff; padding: 5px 8px; text-align: left; font-size: 9px; }
    table.pay td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 9px; }
    table.pay tr:nth-child(even) td { background: #f8f9fa; }
    table.pay tfoot td { font-weight: bold; background: #eaf0fb; border-top: 2px solid #2c3e50; font-size: 10px; }
    .text-right { text-align: right; }
    .text-success { color: #28a745; }
    .text-danger  { color: #dc3545; }
    .text-muted   { color: #888; }
    .net-box { background: #2c3e50; color: #fff; padding: 10px 14px; border-radius: 4px; margin-top: 12px; }
    .net-box .label { font-size: 9px; opacity: .8; }
    .net-box .amount { font-size: 20px; font-weight: bold; }
    .footer { margin-top: 16px; font-size: 8px; color: #aaa; border-top: 1px solid #eee; padding-top: 6px; }
    .att-grid { display: table; width: 100%; }
    .att-cell { display: table-cell; text-align: center; padding: 6px; border: 1px solid #ddd; }
    .att-cell .num { font-size: 14px; font-weight: bold; }
    .att-cell .lbl { font-size: 8px; color: #666; }
</style>
</head>
<body>

@php $emp = $payroll->employee; $ed = $emp->employmentDetails; @endphp

<div class="header">
    <h2>St. Mark School — Payslip</h2>
    <p>
        Month: <strong>{{ $payroll->month }}</strong>
        &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
        &nbsp;|&nbsp; <span class="badge badge-{{ $payroll->status }}">{{ ucfirst($payroll->status) }}</span>
    </p>
</div>

<div class="two-col">
    <div class="col-left">
        <div class="section-title">Employee Information</div>
        <table class="info-table">
            <tr><td>Name</td><td>{{ $emp->full_name }}</td></tr>
            <tr><td>Code</td><td>{{ $emp->employee_code }}</td></tr>
            <tr><td>Department</td><td>{{ $ed?->department?->name ?? '—' }}</td></tr>
            <tr><td>Position</td><td>{{ $ed?->position?->name ?? '—' }}</td></tr>
            <tr><td>Employment Type</td><td>{{ $ed ? ucwords(str_replace('_',' ',$ed->employment_type ?? '')) : '—' }}</td></tr>
        </table>
    </div>
    <div class="col-right">
        <div class="section-title">Pay Period</div>
        <table class="info-table">
            <tr><td>Month</td><td>{{ $payroll->month }}</td></tr>
            <tr><td>Period</td><td>{{ $payroll->period_start?->format('d M Y') ?? '—' }} – {{ $payroll->period_end?->format('d M Y') ?? '—' }}</td></tr>
            <tr><td>Currency</td><td>{{ $payroll->currency }}</td></tr>
            @if($payroll->approved_at)
            <tr><td>Approved</td><td>{{ $payroll->approved_at->format('d M Y') }}</td></tr>
            @endif
            @if($payroll->paid_at)
            <tr><td>Paid On</td><td>{{ $payroll->paid_at->format('d M Y') }}</td></tr>
            @endif
        </table>
    </div>
</div>

{{-- Attendance --}}
<div class="section-title">Attendance Summary</div>
<div class="att-grid">
    <div class="att-cell">
        <div class="num" style="color:#28a745;">{{ $payroll->present_days }}</div>
        <div class="lbl">Present</div>
    </div>
    <div class="att-cell">
        <div class="num" style="color:#dc3545;">{{ $payroll->absent_days }}</div>
        <div class="lbl">Absent</div>
    </div>
    <div class="att-cell">
        <div class="num" style="color:#17a2b8;">{{ $payroll->leave_days }}</div>
        <div class="lbl">Leave</div>
    </div>
    <div class="att-cell">
        <div class="num" style="color:#fd7e14;">{{ $payroll->overtime_hours }}h</div>
        <div class="lbl">Overtime</div>
    </div>
    <div class="att-cell">
        <div class="num">{{ $payroll->working_days }}</div>
        <div class="lbl">Working Days</div>
    </div>
</div>

{{-- Earnings --}}
<div class="section-title">Earnings</div>
<table class="pay">
    <thead>
        <tr><th>Description</th><th class="text-right">Amount ({{ $payroll->currency }})</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>Base Salary</td>
            <td class="text-right text-success">{{ number_format($payroll->base_salary, 2) }}</td>
        </tr>
        @foreach($payroll->items->where('type','earning') as $item)
        <tr>
            <td>{{ $item->label }}@if($item->note) <span class="text-muted">({{ $item->note }})</span>@endif</td>
            <td class="text-right text-success">{{ number_format($item->amount, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>Total Earnings</td>
            <td class="text-right">{{ number_format($payroll->base_salary + $payroll->allowances, 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- Deductions --}}
<div class="section-title">Deductions</div>
<table class="pay">
    <thead>
        <tr><th>Description</th><th class="text-right">Amount ({{ $payroll->currency }})</th></tr>
    </thead>
    <tbody>
        @if($payroll->income_tax > 0)
        <tr>
            <td>Income Tax (Ethiopian progressive)</td>
            <td class="text-right text-danger">{{ number_format($payroll->income_tax, 2) }}</td>
        </tr>
        @endif
        @if($payroll->employee_pension > 0)
        <tr>
            <td>Employee Pension (7%)</td>
            <td class="text-right text-danger">{{ number_format($payroll->employee_pension, 2) }}</td>
        </tr>
        @endif
        @foreach($payroll->items->where('type','deduction') as $item)
        <tr>
            <td>{{ $item->label }}@if($item->note) <span class="text-muted">({{ $item->note }})</span>@endif</td>
            <td class="text-right text-danger">{{ number_format($item->amount, 2) }}</td>
        </tr>
        @endforeach
        @if($payroll->employer_pension > 0)
        <tr>
            <td class="text-muted">Employer Pension (11%) — not deducted from employee</td>
            <td class="text-right text-muted">{{ number_format($payroll->employer_pension, 2) }}</td>
        </tr>
        @endif
    </tbody>
    <tfoot>
        <tr>
            <td>Total Deductions</td>
            <td class="text-right">{{ number_format($payroll->deductions, 2) }}</td>
        </tr>
    </tfoot>
</table>

<div class="net-box">
    <div class="label">NET PAY</div>
    <div class="amount">{{ $payroll->currency }} {{ number_format($payroll->net_pay, 2) }}</div>
</div>

<div class="footer">
    Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}
    &nbsp;|&nbsp; This is a computer-generated payslip.
</div>
</body>
</html>
