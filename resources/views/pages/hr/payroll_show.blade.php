@extends('layouts.master')
@section('page_title', 'Payroll — ' . $payroll->employee->full_name)
@section('content')

@php $emp = $payroll->employee; @endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-cash-stack mr-2"></i>Payroll Detail (View Only)
        <span class="badge badge-{{ $payroll->statusBadgeClass() }} ml-1">{{ ucfirst($payroll->status) }}</span>
    </h5>
    <div>
        <a href="{{ route('hr.payroll.pdf', $payroll->id) }}" class="btn btn-sm btn-danger" target="_blank">
            <i class="bi bi-file-pdf mr-1"></i>PDF
        </a>
        <a href="{{ route('hr.payroll.export', $payroll->id) }}" class="btn btn-sm btn-success">
            <i class="bi bi-download mr-1"></i>CSV
        </a>
        <a href="{{ route('hr.payroll') }}?month={{ $payroll->month }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>Back to Payroll
        </a>
    </div>
</div>

<div class="row">

    {{-- ── Left: Employee + Attendance Snapshot ──────────────────────────── --}}
    <div class="col-md-4">
        <div class="card text-center p-3 mb-3">
            <img src="{{ $emp->photo_url }}" width="80" height="80"
                 class="rounded-circle mx-auto mb-2" style="object-fit:cover;">
            <h6 class="mb-0">{{ $emp->full_name }}</h6>
            <small class="text-muted">{{ $emp->employee_code }}</small>
            <hr>
            <div class="text-left small">
                <p class="mb-1"><strong>Month:</strong> {{ $payroll->month }}</p>
                <p class="mb-1"><strong>Period:</strong>
                    {{ $payroll->period_start?->format('d M') ?? '—' }} –
                    {{ $payroll->period_end?->format('d M Y') ?? '—' }}
                </p>
                <p class="mb-1"><strong>Currency:</strong> {{ $payroll->currency }}</p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge badge-{{ $payroll->statusBadgeClass() }}">{{ ucfirst($payroll->status) }}</span></p>
            </div>
        </div>

        {{-- Attendance snapshot --}}
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-calendar3 mr-1"></i>Attendance Snapshot</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Working Days</td><td class="text-right font-weight-bold">{{ $payroll->working_days }}</td></tr>
                    <tr><td>Present</td><td class="text-right text-success font-weight-bold">{{ $payroll->present_days }}</td></tr>
                    <tr><td>Absent</td><td class="text-right text-danger font-weight-bold">{{ $payroll->absent_days }}</td></tr>
                    <tr><td>Leave</td><td class="text-right text-info font-weight-bold">{{ $payroll->leave_days }}</td></tr>
                    <tr><td>Overtime</td><td class="text-right text-primary font-weight-bold">{{ $payroll->overtime_hours }}h</td></tr>
                </table>
            </div>
        </div>

        {{-- Approval Info --}}
        @if($payroll->approved_at || $payroll->paid_at)
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Status Timeline</h6></div>
            <div class="card-body small">
                @if($payroll->approved_at)
                <p class="mb-2"><i class="bi bi-check-circle text-success mr-1"></i>
                    <strong>Approved:</strong> {{ $payroll->approved_at->format('d M Y H:i') }}
                    @if($payroll->approvedBy)
                    by {{ $payroll->approvedBy->name }}
                    @endif
                </p>
                @endif
                @if($payroll->paid_at)
                <p class="mb-0"><i class="bi bi-cash text-info mr-1"></i>
                    <strong>Paid:</strong> {{ $payroll->paid_at->format('d M Y H:i') }}
                </p>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- ── Right: Pay Breakdown ────────────────────────────────────────────── --}}
    <div class="col-md-8">

        {{-- Summary totals --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-center p-2">
                    <h5 class="mb-0">{{ number_format($payroll->base_salary, 2) }}</h5>
                    <small class="text-muted">Base Salary</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-2">
                    <h5 class="text-success mb-0">+{{ number_format($payroll->allowances, 2) }}</h5>
                    <small class="text-muted">Allowances</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-2">
                    <h5 class="text-danger mb-0">-{{ number_format($payroll->deductions, 2) }}</h5>
                    <small class="text-muted">Deductions</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-2 border-primary">
                    <h5 class="text-primary font-weight-bold mb-0">{{ number_format($payroll->net_pay, 2) }}</h5>
                    <small class="text-muted">Net Pay ({{ $payroll->currency }})</small>
                </div>
            </div>
        </div>

        {{-- Advanced Analytics --}}
        @if(isset($earnings) && isset($deductions))
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white"><h6 class="card-title mb-0">Gross Pay</h6></div>
                    <div class="card-body text-center">
                        <h4 class="text-success">{{ number_format($payroll->base_salary + $payroll->allowances, 2) }}</h4>
                        <small class="text-muted">Before deductions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white"><h6 class="card-title mb-0">Effective Tax Rate</h6></div>
                    <div class="card-body text-center">
                        <h4 class="text-danger">{{ $tax_rate }}%</h4>
                        <small class="text-muted">On gross pay</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white"><h6 class="card-title mb-0">Processing Time</h6></div>
                    <div class="card-body text-center">
                        <h4 class="text-info">{{ $processing_time ?? 'Pending' }}</h4>
                        <small class="text-muted">To approval</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Earnings Breakdown --}}
        @if(count($earnings) > 0)
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-plus-circle text-success mr-1"></i>Earnings Breakdown</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($earnings as $label => $amount)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                        <td class="text-right text-success font-weight-bold">{{ number_format($amount, 2) }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
        @endif

        {{-- Deductions Breakdown --}}
        @if(count($deductions) > 0)
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-dash-circle text-danger mr-1"></i>Deductions Breakdown</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($deductions as $label => $amount)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $label)) }}</td>
                        <td class="text-right text-danger font-weight-bold">{{ number_format($amount, 2) }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
        @endif
        @endif

        <div class="alert alert-light border py-2 mb-3 small">
            <i class="bi bi-calculator mr-1"></i>
            <strong>Formula:</strong>
            Net Pay = (Base Salary {{ $payroll->allowances > 0 ? '+ Allowances' : '' }})
            − (Deductions)
            = <strong>{{ $payroll->currency }} {{ number_format($payroll->net_pay, 2) }}</strong>
        </div>

        {{-- Statutory deductions breakdown --}}
        @if($payroll->income_tax > 0 || $payroll->employee_pension > 0)
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-bank mr-1"></i>Statutory Deductions</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Income Tax (Ethiopian progressive)</td>
                        <td class="text-right text-danger">{{ $payroll->currency }} {{ number_format($payroll->income_tax, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Employee Pension (7%)</td>
                        <td class="text-right text-danger">{{ $payroll->currency }} {{ number_format($payroll->employee_pension, 2) }}</td>
                    </tr>
                    <tr class="table-light">
                        <td><strong>Employer Pension (11%) — not deducted from employee</strong></td>
                        <td class="text-right text-muted">{{ $payroll->currency }} {{ number_format($payroll->employer_pension, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        {{-- Line items (read-only) --}}
        @if($payroll->items->count() > 0)
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="bi bi-list-ul mr-1"></i>Pay Items</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Type</th><th>Description</th><th>Note</th><th class="text-right">Amount</th></tr>
                    </thead>
                    <tbody>
                        @foreach($payroll->items as $item)
                        <tr>
                            <td>
                                <span class="badge badge-{{ $item->isEarning() ? 'success' : 'danger' }}">
                                    {{ ucfirst($item->type) }}
                                </span>
                            </td>
                            <td>{{ $item->label }}</td>
                            <td class="text-muted small">{{ $item->note ?? '—' }}</td>
                            <td class="text-right font-weight-bold {{ $item->isEarning() ? 'text-success' : 'text-danger' }}">
                                {{ $item->isDeduction() ? '-' : '+' }}{{ number_format($item->amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>

<div class="mt-4 text-center">
    <a href="{{ route('hr.payroll') }}?month={{ $payroll->month }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to Payroll List
    </a>
    @if($payroll->isDraft())
    <a href="{{ route('hr.payroll.edit', $payroll->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil mr-1"></i>Edit This Payroll
    </a>
    @endif
</div>

@endsection
