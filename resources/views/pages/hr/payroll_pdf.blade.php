<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll — {{ $payroll->employee->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        .employee-info { margin-bottom: 20px; }
        .info-row { display: flex; margin-bottom: 5px; }
        .info-label { width: 150px; font-weight: bold; }
        .info-value { flex: 1; }
        .section { margin-bottom: 15px; page-break-inside: avoid; }
        .section-title { background: #f0f0f0; padding: 5px 10px; font-weight: bold; border-left: 3px solid #333; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; padding: 8px; text-align: left; border-bottom: 1px solid #999; font-weight: bold; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .text-right { text-align: right; }
        .total-row { background: #f9f9f9; font-weight: bold; border-top: 2px solid #333; border-bottom: 2px solid #333; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .summary-box { display: inline-block; width: 23%; margin-right: 2%; vertical-align: top; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
        .summary-box h3 { font-size: 12px; margin-bottom: 5px; }
        .summary-box .amount { font-size: 14px; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 9px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PAYROLL STATEMENT</h1>
            <p>{{ now()->format('d F Y') }}</p>
        </div>

        <div class="employee-info">
            <div class="info-row">
                <div class="info-label">Employee Name:</div>
                <div class="info-value">{{ $payroll->employee->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Employee Code:</div>
                <div class="info-value">{{ $payroll->employee->employee_code }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Department:</div>
                <div class="info-value">{{ $payroll->employee->employmentDetails?->department?->name ?? '—' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $payroll->month }} ({{ $payroll->period_start?->format('d M Y') ?? '—' }} to {{ $payroll->period_end?->format('d M Y') ?? '—' }})</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($payroll->status) }}</div>
            </div>
        </div>

        <!-- Summary Boxes -->
        <div>
            <div class="summary-box">
                <h3>Base Salary</h3>
                <div class="amount">{{ number_format($payroll->base_salary, 2) }}</div>
                <div style="font-size: 9px; color: #666;">{{ $payroll->currency }}</div>
            </div>
            <div class="summary-box">
                <h3>Gross Pay</h3>
                <div class="amount text-success">{{ number_format($payroll->base_salary + $payroll->allowances, 2) }}</div>
                <div style="font-size: 9px; color: #666;">Before deductions</div>
            </div>
            <div class="summary-box">
                <h3>Total Deductions</h3>
                <div class="amount text-danger">{{ number_format($payroll->deductions, 2) }}</div>
                <div style="font-size: 9px; color: #666;">Tax + Pension + Other</div>
            </div>
            <div class="summary-box">
                <h3>Net Pay</h3>
                <div class="amount" style="color: #2c5aa0;">{{ number_format($payroll->net_pay, 2) }}</div>
                <div style="font-size: 9px; color: #666;">Final payment</div>
            </div>
        </div>

        <!-- Earnings -->
        @if(isset($earnings) && count($earnings) > 0)
        <div class="section">
            <div class="section-title">EARNINGS</div>
            <table>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
                @foreach($earnings as $label => $amount)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                    <td class="text-right text-success">{{ number_format($amount, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total Earnings</td>
                    <td class="text-right text-success">{{ number_format($payroll->base_salary + $payroll->allowances, 2) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Deductions -->
        @if(isset($deductions) && count($deductions) > 0)
        <div class="section">
            <div class="section-title">DEDUCTIONS</div>
            <table>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
                <tr>
                    <td>Income Tax (Ethiopian Progressive)</td>
                    <td class="text-right">{{ number_format($payroll->income_tax, 2) }}</td>
                </tr>
                <tr>
                    <td>Employee Pension (7%)</td>
                    <td class="text-right">{{ number_format($payroll->employee_pension, 2) }}</td>
                </tr>
                @if($payroll->deductions - $payroll->income_tax - $payroll->employee_pension > 0)
                <tr>
                    <td>Other Deductions</td>
                    <td class="text-right">{{ number_format($payroll->deductions - $payroll->income_tax - $payroll->employee_pension, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total Deductions</td>
                    <td class="text-right text-danger">{{ number_format($payroll->deductions, 2) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Attendance -->
        <div class="section">
            <div class="section-title">ATTENDANCE & WORKING HOURS</div>
            <table>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Value</th>
                </tr>
                <tr>
                    <td>Working Days</td>
                    <td class="text-right">{{ $payroll->working_days }}</td>
                </tr>
                <tr>
                    <td>Present Days</td>
                    <td class="text-right text-success">{{ $payroll->present_days }}</td>
                </tr>
                <tr>
                    <td>Absent Days</td>
                    <td class="text-right text-danger">{{ $payroll->absent_days }}</td>
                </tr>
                <tr>
                    <td>Leave Days</td>
                    <td class="text-right">{{ $payroll->leave_days }}</td>
                </tr>
                <tr>
                    <td>Overtime Hours</td>
                    <td class="text-right">{{ number_format($payroll->overtime_hours, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Summary -->
        <div class="section">
            <div class="section-title">PAYMENT SUMMARY</div>
            <table>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Amount</th>
                </tr>
                <tr>
                    <td>Gross Pay</td>
                    <td class="text-right">{{ number_format($payroll->base_salary + $payroll->allowances, 2) }}</td>
                </tr>
                <tr>
                    <td>Income Tax</td>
                    <td class="text-right">-{{ number_format($payroll->income_tax, 2) }}</td>
                </tr>
                <tr>
                    <td>Employee Pension</td>
                    <td class="text-right">-{{ number_format($payroll->employee_pension, 2) }}</td>
                </tr>
                @if($payroll->deductions - $payroll->income_tax - $payroll->employee_pension > 0)
                <tr>
                    <td>Other Deductions</td>
                    <td class="text-right">-{{ number_format($payroll->deductions - $payroll->income_tax - $payroll->employee_pension, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>NET PAY (Due to Employee)</strong></td>
                    <td class="text-right" style="color: #2c5aa0;">{{ number_format($payroll->net_pay, 2) }} {{ $payroll->currency }}</td>
                </tr>
            </table>
        </div>

        <!-- Employer Info -->
        @if($payroll->employer_pension > 0)
        <div class="section">
            <div class="section-title">EMPLOYER COSTS (Not Deducted from Employee)</div>
            <table>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
                <tr>
                    <td>Employer Pension Contribution (11%)</td>
                    <td class="text-right">{{ number_format($payroll->employer_pension, 2) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <div class="footer">
            <p>This is a computer-generated payroll statement. No signature required.</p>
            <p>For queries, contact the HR department.</p>
            <p>Generated on {{ now()->format('d M Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
