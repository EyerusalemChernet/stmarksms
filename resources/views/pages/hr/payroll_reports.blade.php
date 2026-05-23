@extends('layouts.master')
@section('page_title', 'Payroll Reports — ' . $month)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-bar-chart-line mr-2"></i>Payroll Reports</h5>
    <a href="{{ route('hr.payroll', ['month' => $month]) }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to Payroll
    </a>
</div>

{{-- Controls --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form action="{{ route('hr.payroll.reports') }}" method="GET" class="form-inline mb-0" style="gap:8px;flex-wrap:wrap;">
            <div class="form-group mb-0">
                <label class="mr-2 font-weight-bold">Month:</label>
                <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
            </div>
            <div class="form-group mb-0">
                <label class="mr-2 font-weight-bold">Report:</label>
                <select name="type" class="form-control form-control-sm">
                    @foreach([
                        'summary'     => 'Financial Summary',
                        'attendance'  => 'Attendance Analysis',
                        'departments' => 'Department Breakdown',
                        'overtime'    => 'Overtime Tracking',
                        'compliance'  => 'Compliance Status',
                        'comparison'  => 'Month Comparison',
                    ] as $val => $lbl)
                    <option value="{{ $val }}" {{ $report_type === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search mr-1"></i>Generate
            </button>
            <a href="{{ route('hr.payroll.reports', ['month' => $month, 'type' => $report_type, 'export' => 'json']) }}"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-braces mr-1"></i>JSON
            </a>
        </form>
    </div>
</div>

{{-- ── SUMMARY REPORT ──────────────────────────────────────────────────────── --}}
@if($report_type === 'summary')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="h3 text-primary mb-0">{{ $report_data['total_employees'] ?? 0 }}</div>
            <small class="text-muted">Employees</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="h3 text-success mb-0">
                ETB {{ number_format($report_data['financial_summary']['total_net_pay'] ?? 0, 0) }}
            </div>
            <small class="text-muted">Total Net Pay</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="h3 text-danger mb-0">
                ETB {{ number_format($report_data['tax_and_deductions']['total_income_tax'] ?? 0, 0) }}
            </div>
            <small class="text-muted">Total Tax</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="h3 text-warning mb-0">
                ETB {{ number_format($report_data['statistics']['average_salary'] ?? 0, 0) }}
            </div>
            <small class="text-muted">Avg Net Pay</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Financial Summary</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Total Base Salary</td><td class="text-right font-weight-bold">ETB {{ number_format($report_data['financial_summary']['total_base_salary'] ?? 0, 2) }}</td></tr>
                    <tr><td>Total Allowances</td><td class="text-right text-success">ETB {{ number_format($report_data['financial_summary']['total_allowances'] ?? 0, 2) }}</td></tr>
                    <tr><td>Total Deductions</td><td class="text-right text-danger">ETB {{ number_format($report_data['financial_summary']['total_deductions'] ?? 0, 2) }}</td></tr>
                    <tr class="table-primary"><td><strong>Total Net Pay</strong></td><td class="text-right font-weight-bold">ETB {{ number_format($report_data['financial_summary']['total_net_pay'] ?? 0, 2) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Tax & Pension</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Total Income Tax</td><td class="text-right text-danger">ETB {{ number_format($report_data['tax_and_deductions']['total_income_tax'] ?? 0, 2) }}</td></tr>
                    <tr><td>Employee Pension (7%)</td><td class="text-right text-danger">ETB {{ number_format($report_data['tax_and_deductions']['total_employee_pension'] ?? 0, 2) }}</td></tr>
                    <tr><td class="text-muted">Employer Pension (11%)</td><td class="text-right text-muted">ETB {{ number_format($report_data['tax_and_deductions']['total_employer_pension'] ?? 0, 2) }}</td></tr>
                </table>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Statistics</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Average Net Pay</td><td class="text-right">ETB {{ number_format($report_data['statistics']['average_salary'] ?? 0, 2) }}</td></tr>
                    <tr><td>Highest Net Pay</td><td class="text-right text-success">ETB {{ number_format($report_data['statistics']['highest_net_pay'] ?? 0, 2) }}</td></tr>
                    <tr><td>Lowest Net Pay</td><td class="text-right text-warning">ETB {{ number_format($report_data['statistics']['lowest_net_pay'] ?? 0, 2) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Status breakdown --}}
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Status Breakdown</h6></div>
    <div class="card-body">
        <div class="row text-center">
            @foreach(['draft' => 'secondary', 'approved' => 'info', 'paid' => 'success'] as $s => $cls)
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="h3 text-{{ $cls }} mb-0">{{ $report_data['status_breakdown'][$s] ?? 0 }}</div>
                    <small>{{ ucfirst($s) }}</small>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── ATTENDANCE REPORT ───────────────────────────────────────────────────── --}}
@elseif($report_type === 'attendance')
<div class="row mb-3">
    @foreach([
        ['Total Present Days', $report_data['total_present_days'] ?? 0, 'success'],
        ['Total Absent Days',  $report_data['total_absent_days']  ?? 0, 'danger'],
        ['Total Leave Days',   $report_data['total_leave_days']   ?? 0, 'info'],
        ['Employees w/ Absence', $report_data['employees_with_absence'] ?? 0, 'warning'],
    ] as [$lbl, $val, $cls])
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="h3 text-{{ $cls }} mb-0">{{ $val }}</div>
            <small class="text-muted">{{ $lbl }}</small>
        </div>
    </div>
    @endforeach
</div>
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Attendance Averages</h6></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tr><td>Average Present Days</td><td class="text-right font-weight-bold text-success">{{ $report_data['average_present_days'] ?? 0 }}</td></tr>
            <tr><td>Average Absent Days</td><td class="text-right font-weight-bold text-danger">{{ $report_data['average_absent_days'] ?? 0 }}</td></tr>
        </table>
    </div>
</div>

{{-- ── DEPARTMENT REPORT ───────────────────────────────────────────────────── --}}
@elseif($report_type === 'departments')
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Department Breakdown — {{ $month }}</h6></div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Department</th>
                    <th class="text-center">Employees</th>
                    <th class="text-right">Total Base Salary</th>
                    <th class="text-right">Total Net Pay</th>
                    <th class="text-right">Avg Net Pay</th>
                    <th class="text-right">Total Deductions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report_data as $dept => $data)
                <tr>
                    <td><strong>{{ $dept }}</strong></td>
                    <td class="text-center"><span class="badge badge-primary">{{ $data['count'] }}</span></td>
                    <td class="text-right">ETB {{ number_format($data['total_base_salary'], 2) }}</td>
                    <td class="text-right font-weight-bold text-success">ETB {{ number_format($data['total_net_pay'], 2) }}</td>
                    <td class="text-right">ETB {{ number_format($data['average_net_pay'], 2) }}</td>
                    <td class="text-right text-danger">ETB {{ number_format($data['total_deductions'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No data for this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── OVERTIME REPORT ─────────────────────────────────────────────────────── --}}
@elseif($report_type === 'overtime')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="h3 text-warning mb-0">{{ $report_data['total_overtime_hours'] ?? 0 }}h</div>
            <small class="text-muted">Total Overtime Hours</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="h3 text-success mb-0">ETB {{ number_format($report_data['total_overtime_pay'] ?? 0, 2) }}</div>
            <small class="text-muted">Total Overtime Pay</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="h3 text-primary mb-0">{{ $report_data['employees_with_overtime'] ?? 0 }}</div>
            <small class="text-muted">Employees with Overtime</small>
        </div>
    </div>
</div>
@if(!empty($report_data['details']))
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Overtime Details</h6></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr><th>Employee</th><th class="text-right">Hours</th><th class="text-right">Pay</th></tr>
            </thead>
            <tbody>
                @foreach($report_data['details'] as $row)
                <tr>
                    <td>{{ $row['employee'] }}</td>
                    <td class="text-right">{{ $row['overtime_hours'] }}h</td>
                    <td class="text-right text-success">ETB {{ number_format($row['overtime_pay'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── COMPLIANCE REPORT ───────────────────────────────────────────────────── --}}
@elseif($report_type === 'compliance')
<div class="row mb-3">
    @php $pct = $report_data['completion_percentage'] ?? 0; @endphp
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="h3 mb-0 text-{{ $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}">{{ $pct }}%</div>
            <small class="text-muted">Completion Rate</small>
            <div class="progress mt-2" style="height:6px;">
                <div class="progress-bar bg-{{ $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}"
                     style="width:{{ $pct }}%"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="h3 text-danger mb-0">{{ $report_data['unprocessed_count'] ?? 0 }}</div>
            <small class="text-muted">Unprocessed</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="h3 text-info mb-0">{{ $report_data['average_approval_time'] ?? '—' }}</div>
            <small class="text-muted">Avg Approval Time</small>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title mb-0">Processing Status</h6></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            @foreach(['draft' => 'secondary', 'approved' => 'info', 'paid' => 'success', 'pending' => 'warning'] as $s => $cls)
            <tr>
                <td><span class="badge badge-{{ $cls }}">{{ ucfirst($s) }}</span></td>
                <td class="text-right font-weight-bold">{{ $report_data['processing_status'][$s] ?? 0 }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>

{{-- ── COMPARISON REPORT ───────────────────────────────────────────────────── --}}
@elseif($report_type === 'comparison')
<div class="card mb-3">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            Month Comparison: {{ $report_data['previous_month'] ?? '—' }} → {{ $report_data['current_month'] ?? $month }}
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr><th>Metric</th><th class="text-right">Change (%)</th><th class="text-right">Change (ETB)</th></tr>
            </thead>
            <tbody>
                @php
                    $salChange = $report_data['salary_change'] ?? ['percentage' => 0, 'absolute' => 0];
                    $netChange = $report_data['net_pay_change'] ?? ['percentage' => 0, 'absolute' => 0];
                @endphp
                <tr>
                    <td>Base Salary</td>
                    <td class="text-right {{ $salChange['percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $salChange['percentage'] >= 0 ? '+' : '' }}{{ $salChange['percentage'] }}%
                    </td>
                    <td class="text-right {{ $salChange['absolute'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $salChange['absolute'] >= 0 ? '+' : '' }}ETB {{ number_format($salChange['absolute'], 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Net Pay</td>
                    <td class="text-right {{ $netChange['percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $netChange['percentage'] >= 0 ? '+' : '' }}{{ $netChange['percentage'] }}%
                    </td>
                    <td class="text-right {{ $netChange['absolute'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $netChange['absolute'] >= 0 ? '+' : '' }}ETB {{ number_format($netChange['absolute'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Payroll list at bottom --}}
@if($payrolls->count())
<div class="card mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Payroll Records — {{ $month }}</h6>
        <span class="badge badge-secondary">{{ $payrolls->count() }} records</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Employee</th>
                    <th class="text-right">Base</th>
                    <th class="text-right">Net Pay</th>
                    <th class="text-right">Tax</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $pr)
                <tr>
                    <td>{{ $pr->employee->full_name }}</td>
                    <td class="text-right">{{ number_format($pr->base_salary, 2) }}</td>
                    <td class="text-right font-weight-bold text-primary">{{ number_format($pr->net_pay, 2) }}</td>
                    <td class="text-right text-danger">{{ number_format($pr->income_tax, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $pr->statusBadgeClass() }}">{{ ucfirst($pr->status) }}</span>
                    </td>
                    <td>
                        <a href="{{ route('hr.payroll.show', $pr->id) }}" class="btn btn-xs btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
