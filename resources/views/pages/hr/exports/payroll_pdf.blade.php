<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; margin: 0; padding: 16px; }
    h2   { font-size: 14px; margin: 0 0 2px; }
    p    { margin: 0 0 10px; font-size: 9px; color: #666; }
    .summary { display: table; width: 60%; margin-bottom: 12px; }
    .sum-box { display: table-cell; text-align: center; padding: 6px; border: 1px solid #ddd; }
    .sum-box .num { font-size: 16px; font-weight: bold; }
    .sum-box .lbl { font-size: 8px; color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th   { background: #2c3e50; color: #fff; padding: 5px 6px; text-align: left; font-size: 8px; }
    td   { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; font-size: 8px; vertical-align: middle; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
    .badge-draft    { background: #6c757d; color: #fff; }
    .badge-approved { background: #17a2b8; color: #fff; }
    .badge-paid     { background: #28a745; color: #fff; }
    .text-right { text-align: right; }
    tfoot td { font-weight: bold; background: #f0f0f0; border-top: 2px solid #2c3e50; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Payroll Report</h2>
<p>
    Month: <strong>{{ $month }}</strong>
    @if($status !== 'all') &nbsp;|&nbsp; Status: <strong>{{ ucfirst($status) }}</strong> @endif
    &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>

{{-- Status summary --}}
<div class="summary">
    <div class="sum-box">
        <div class="num" style="color:#6c757d;">{{ $statusCounts['draft'] ?? 0 }}</div>
        <div class="lbl">Draft</div>
    </div>
    <div class="sum-box">
        <div class="num" style="color:#17a2b8;">{{ $statusCounts['approved'] ?? 0 }}</div>
        <div class="lbl">Approved</div>
    </div>
    <div class="sum-box">
        <div class="num" style="color:#28a745;">{{ $statusCounts['paid'] ?? 0 }}</div>
        <div class="lbl">Paid</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Code</th>
            <th>Department</th>
            <th>Base Salary</th>
            <th class="text-right">Present</th>
            <th class="text-right">Absent</th>
            <th class="text-right">Earnings</th>
            <th class="text-right">Deductions</th>
            <th class="text-right">Net Pay</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @php $totalNet = 0; $rowNum = 0; @endphp
        @foreach($employees as $emp)
        @php
            $pr = $payrolls->get($emp->id);
            $ed = $emp->employmentDetails;
            if (!$pr) continue;
            $rowNum++;
            $totalNet += $pr->net_pay;
        @endphp
        <tr>
            <td>{{ $rowNum }}</td>
            <td><strong>{{ $emp->full_name }}</strong></td>
            <td>{{ $emp->employee_code }}</td>
            <td>{{ $ed?->department?->name ?? '—' }}</td>
            <td>{{ $pr->currency }} {{ number_format($pr->base_salary, 2) }}</td>
            <td class="text-right">{{ $pr->present_days }}</td>
            <td class="text-right">{{ $pr->absent_days }}</td>
            <td class="text-right">{{ $pr->currency }} {{ number_format($pr->base_salary + $pr->allowances, 2) }}</td>
            <td class="text-right">{{ $pr->currency }} {{ number_format($pr->deductions, 2) }}</td>
            <td class="text-right"><strong>{{ $pr->currency }} {{ number_format($pr->net_pay, 2) }}</strong></td>
            <td><span class="badge badge-{{ $pr->status }}">{{ ucfirst($pr->status) }}</span></td>
        </tr>
        @endforeach
    </tbody>
    @if($rowNum > 0)
    <tfoot>
        <tr>
            <td colspan="9" class="text-right">Total Net Pay ({{ $rowNum }} employees):</td>
            <td class="text-right">ETB {{ number_format($totalNet, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
