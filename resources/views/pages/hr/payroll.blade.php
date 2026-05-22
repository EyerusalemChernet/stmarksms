@extends('layouts.master')
@section('page_title', 'Staff Payroll')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-cash-stack mr-2"></i>Staff Payroll</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to HR
    </a>
</div>

{{-- Controls --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:12px;">
        <form action="{{ route('hr.payroll') }}" method="GET" class="form-inline mb-0">
            <label class="mr-2 font-weight-bold">Month:</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm mr-2">
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="all"      {{ $status === 'all'      ? 'selected' : '' }}>All Statuses</option>
                <option value="draft"    {{ $status === 'draft'    ? 'selected' : '' }}>Draft</option>
                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="paid"     {{ $status === 'paid'     ? 'selected' : '' }}>Paid</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary mr-2">
                <i class="bi bi-search mr-1"></i>View
            </button>
        </form>

        <form action="{{ route('hr.payroll.generate') }}" method="POST" class="mb-0">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="submit" class="btn btn-sm btn-success"
                    onclick="return confirm('Generate payroll for {{ $month }}?\nThis uses attendance data and employment details.')">
                <i class="bi bi-plus-circle mr-1"></i>Generate for {{ $month }}
            </button>
        </form>

        <div class="ml-auto d-flex" style="gap:6px;">
            <a href="{{ route('hr.payroll', array_merge(request()->query(), ['export'=>'pdf'])) }}"
               class="btn btn-sm btn-danger">
                <i class="bi bi-file-pdf mr-1"></i>PDF
            </a>
            <a href="{{ route('hr.payroll', array_merge(request()->query(), ['export'=>'csv'])) }}"
               class="btn btn-sm btn-success">
                <i class="bi bi-file-spreadsheet mr-1"></i>CSV
            </a>
        </div>
    </div>
</div>

{{-- Status summary badges --}}
<div class="row mb-3">
    @foreach(['draft'=>['secondary','Draft'],'approved'=>['info','Approved'],'paid'=>['success','Paid']] as $s=>[$cls,$lbl])
    <div class="col-md-4">
        <div class="card text-center p-2">
            <h4 class="text-{{ $cls }} mb-0">{{ $statusCounts[$s] ?? 0 }}</h4>
            <small>{{ $lbl }}</small>
        </div>
    </div>
    @endforeach
</div>

{{-- Advanced Reports Summary --}}
@if(isset($reports) && isset($reports['summary']) && $reports['summary'] !== null)
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-bar-chart mr-1"></i>Financial Summary</h6></div>
            <div class="card-body small">
                <table class="table table-sm mb-0">
                    <tr><td>Total Base Salary</td><td class="text-right font-weight-bold">{{ number_format($reports['summary']['financial_summary']['total_base_salary'], 2) }}</td></tr>
                    <tr><td>Total Allowances</td><td class="text-right text-success">{{ number_format($reports['summary']['financial_summary']['total_allowances'], 2) }}</td></tr>
                    <tr><td>Total Deductions</td><td class="text-right text-danger">{{ number_format($reports['summary']['financial_summary']['total_deductions'], 2) }}</td></tr>
                    <tr><td><strong>Total Net Pay</strong></td><td class="text-right font-weight-bold">{{ number_format($reports['summary']['financial_summary']['total_net_pay'], 2) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-calculator mr-1"></i>Tax & Pension</h6></div>
            <div class="card-body small">
                <table class="table table-sm mb-0">
                    <tr><td>Total Income Tax</td><td class="text-right">{{ number_format($reports['summary']['tax_and_deductions']['total_income_tax'], 2) }}</td></tr>
                    <tr><td>Employee Pension (7%)</td><td class="text-right">{{ number_format($reports['summary']['tax_and_deductions']['total_employee_pension'], 2) }}</td></tr>
                    <tr><td>Employer Pension (11%)</td><td class="text-right text-muted">{{ number_format($reports['summary']['tax_and_deductions']['total_employer_pension'], 2) }}</td></tr>
                    <tr><td><strong>Employees</strong></td><td class="text-right font-weight-bold">{{ $reports['summary']['total_employees'] }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Attendance Summary --}}
@if(isset($reports['attendance']) && $reports['attendance'] !== null)
<div class="card mb-3">
    <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-calendar3 mr-1"></i>Attendance Summary</h6></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center">
                <h5 class="text-success">{{ $reports['attendance']['total_present_days'] }}</h5>
                <small>Total Present Days</small>
            </div>
            <div class="col-md-3 text-center">
                <h5 class="text-danger">{{ $reports['attendance']['total_absent_days'] }}</h5>
                <small>Total Absent Days</small>
            </div>
            <div class="col-md-3 text-center">
                <h5 class="text-info">{{ $reports['attendance']['total_leave_days'] }}</h5>
                <small>Total Leave Days</small>
            </div>
            <div class="col-md-3 text-center">
                <h5 class="text-warning">{{ $reports['attendance']['employees_with_absence'] }}</h5>
                <small>Employees with Absence</small>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Overtime Summary --}}
@if(isset($reports['overtime']) && $reports['overtime'] !== null)
<div class="card mb-3">
    <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-clock-history mr-1"></i>Overtime Summary</h6></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 text-center">
                <h5 class="text-primary">{{ $reports['overtime']['total_overtime_hours'] }}</h5>
                <small>Total Overtime Hours</small>
            </div>
            <div class="col-md-4 text-center">
                <h5 class="text-success">{{ number_format($reports['overtime']['total_overtime_pay'], 2) }}</h5>
                <small>Total Overtime Pay</small>
            </div>
            <div class="col-md-4 text-center">
                <h5 class="text-info">{{ $reports['overtime']['employees_with_overtime'] }}</h5>
                <small>Employees with Overtime</small>
            </div>
        </div>
    </div>
</div>
@endif
@endif

{{-- Payroll table --}}
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">Payroll — {{ $month }}</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Base Salary</th>
                    <th class="text-center">Present</th>
                    <th class="text-center">Absent</th>
                    <th class="text-success text-center">Earnings</th>
                    <th class="text-danger text-center">Deductions</th>
                    <th class="text-center font-weight-bold">Net Pay</th>
                    <th class="text-center">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                @php
                    $pr = $payrolls->get($emp->id);
                    $ed = $emp->employmentDetails;
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center" style="gap:6px;">
                            <a href="{{ route('hr.show', $emp->id) }}">{{ $emp->full_name }}</a>
                        </div>
                    </td>
                    <td>{{ $ed?->department?->name ?? '—' }}</td>
                    <td>
                        @if($ed && $ed->salary > 0)
                            <span class="font-weight-bold">{{ $ed->currency }} {{ number_format($ed->salary, 2) }}</span>
                        @else
                            <span class="text-danger small">Not set</span>
                        @endif
                    </td>
                    @if($pr)
                    <td class="text-center"><span class="badge badge-success">{{ $pr->present_days }}</span></td>
                    <td class="text-center"><span class="badge badge-danger">{{ $pr->absent_days }}</span></td>
                    <td class="text-center text-success">
                        {{ $pr->currency }} {{ number_format($pr->base_salary + $pr->allowances, 2) }}
                    </td>
                    <td class="text-center text-danger">
                        {{ $pr->currency }} {{ number_format($pr->deductions, 2) }}
                    </td>
                    <td class="text-center font-weight-bold text-primary">
                        {{ $pr->currency }} {{ number_format($pr->net_pay, 2) }}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $pr->statusBadgeClass() }}">{{ ucfirst($pr->status) }}</span>
                    </td>
                    <td>
                        @if($pr)
                            @php
                                $payroll_id = is_numeric($pr->id) ? (int)$pr->id : null;
                            @endphp
                            
                            @if($payroll_id)
                                <!-- View payroll details -->
                                <a href="{{ route('hr.payroll.show', $payroll_id) }}" class="btn btn-xs btn-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                
                                <!-- PDF download -->
                                <a href="{{ route('hr.payroll.pdf', $payroll_id) }}" class="btn btn-xs btn-danger" target="_blank" title="Download PDF">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                                
                                <!-- CSV export -->
                                <a href="{{ route('hr.payroll.export', $payroll_id) }}" class="btn btn-xs btn-success" title="Export CSV">
                                    <i class="bi bi-download"></i>
                                </a>
                                
                                <!-- Edit payroll -->
                                <a href="{{ route('hr.payroll.edit', $payroll_id) }}" class="btn btn-xs btn-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <!-- Workflow actions -->
                                @if($pr->isDraft())
                                <form action="{{ route('hr.payroll.approve', $payroll_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-warning" title="Approve"
                                            onclick="return confirm('Approve this payroll?')">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                                @elseif($pr->isApproved())
                                <form action="{{ route('hr.payroll.paid', $payroll_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-success" title="Mark Paid"
                                            onclick="return confirm('Mark as paid?')">
                                        <i class="bi bi-cash"></i>
                                    </button>
                                </form>
                                @endif
                            @else
                                <span class="text-danger small">⚠ Invalid ID</span>
                            @endif
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    @else
                    <td colspan="7" class="text-center text-muted small">Not generated</td>
                    <td></td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
