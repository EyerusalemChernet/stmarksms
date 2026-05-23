@extends('layouts.master')
@section('page_title', 'Payslip — ' . $payroll->employee->full_name . ' — ' . $payroll->month)
@section('content')

@php $emp = $payroll->employee; $ed = $emp->employmentDetails; @endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-file-earmark-text mr-2"></i>Payslip
        <span class="badge badge-{{ $payroll->statusBadgeClass() }} ml-1">{{ ucfirst($payroll->status) }}</span>
    </h5>
    <div style="gap:6px;" class="d-flex">
        @if($payroll->isDraft())
        <a href="{{ route('hr.payroll.edit', $payroll->id) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil mr-1"></i>Edit
        </a>
        @endif
        <a href="{{ route('hr.payroll.pdf', $payroll->id) }}" class="btn btn-sm btn-danger" target="_blank">
            <i class="bi bi-file-pdf mr-1"></i>PDF
        </a>
        <a href="{{ route('hr.payroll.export', $payroll->id) }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-spreadsheet mr-1"></i>CSV
        </a>
        <a href="{{ route('hr.payroll', ['month' => $payroll->month]) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>Back
        </a>
    </div>
</div>

<div class="row">

    {{-- ── LEFT COLUMN ─────────────────────────────────────────────────────── --}}
    <div class="col-md-4">

        {{-- Employee card --}}
        <div class="card text-center p-3 mb-3">
            <img src="{{ $emp->photo_url }}" width="80" height="80"
                 class="rounded-circle mx-auto mb-2" style="object-fit:cover;border:3px solid #e0e0e0;">
            <h6 class="mb-0">{{ $emp->full_name }}</h6>
            <small class="text-muted">{{ $emp->employee_code }}</small>
            @if($ed?->department)
                <div class="mt-1"><span class="badge badge-info">{{ $ed->department->name }}</span></div>
            @endif
            @if($ed?->position)
                <div class="mt-1"><span class="badge badge-secondary">{{ $ed->position->name }}</span></div>
            @endif
            <hr>
            <div class="text-left small">
                <p class="mb-1"><i class="bi bi-calendar3 mr-1 text-muted"></i><strong>Month:</strong> {{ $payroll->month }}</p>
                <p class="mb-1"><i class="bi bi-calendar-range mr-1 text-muted"></i><strong>Period:</strong>
                    {{ $payroll->period_start?->format('d M') ?? '—' }} –
                    {{ $payroll->period_end?->format('d M Y') ?? '—' }}
                </p>
                <p class="mb-1"><i class="bi bi-currency-exchange mr-1 text-muted"></i><strong>Currency:</strong> {{ $payroll->currency }}</p>
                @if($payroll->approved_at)
                <p class="mb-1"><i class="bi bi-check-circle mr-1 text-success"></i><strong>Approved:</strong> {{ $payroll->approved_at->format('d M Y') }}</p>
                @endif
                @if($payroll->paid_at)
                <p class="mb-1"><i class="bi bi-cash mr-1 text-success"></i><strong>Paid:</strong> {{ $payroll->paid_at->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        {{-- Attendance snapshot --}}
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="bi bi-calendar3 mr-1"></i>Attendance Snapshot</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Working Days</td>
                        <td class="text-right font-weight-bold">{{ $payroll->working_days }}</td>
                    </tr>
                    <tr>
                        <td>Present</td>
                        <td class="text-right font-weight-bold text-success">{{ $payroll->present_days }}</td>
                    </tr>
                    <tr>
                        <td>Absent</td>
                        <td class="text-right font-weight-bold text-danger">{{ $payroll->absent_days }}</td>
                    </tr>
                    <tr>
                        <td>Leave</td>
                        <td class="text-right font-weight-bold text-info">{{ $payroll->leave_days }}</td>
                    </tr>
                    <tr>
                        <td>Overtime</td>
                        <td class="text-right font-weight-bold text-warning">{{ $payroll->overtime_hours }}h</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Workflow actions --}}
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-arrow-repeat mr-1"></i>Workflow</h6></div>
            <div class="card-body">
                {{-- Progress bar --}}
                @php
                    $steps = ['draft' => 1, 'approved' => 2, 'paid' => 3];
                    $step  = $steps[$payroll->status] ?? 1;
                    $pct   = round(($step / 3) * 100);
                @endphp
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span class="{{ $step >= 1 ? 'font-weight-bold text-dark' : '' }}">Draft</span>
                    <span class="{{ $step >= 2 ? 'font-weight-bold text-dark' : '' }}">Approved</span>
                    <span class="{{ $step >= 3 ? 'font-weight-bold text-dark' : '' }}">Paid</span>
                </div>
                <div class="progress mb-3" style="height:6px;">
                    <div class="progress-bar bg-{{ $payroll->statusBadgeClass() }}" style="width:{{ $pct }}%"></div>
                </div>

                @if($payroll->isDraft())
                <form action="{{ route('hr.payroll.approve', $payroll->id) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-info btn-block btn-sm"
                            onclick="return confirm('Approve this payroll?')">
                        <i class="bi bi-check-circle mr-1"></i>Approve
                    </button>
                </form>
                @elseif($payroll->isApproved())
                <form action="{{ route('hr.payroll.paid', $payroll->id) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success btn-block btn-sm"
                            onclick="return confirm('Mark as paid?')">
                        <i class="bi bi-cash mr-1"></i>Mark as Paid
                    </button>
                </form>
                <form action="{{ route('hr.payroll.draft', $payroll->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-block btn-sm"
                            onclick="return confirm('Revert to draft?')">
                        <i class="bi bi-arrow-counterclockwise mr-1"></i>Revert to Draft
                    </button>
                </form>
                @elseif($payroll->isPaid())
                <div class="alert alert-success py-2 mb-0 small">
                    <i class="bi bi-check-circle mr-1"></i>
                    Paid on {{ $payroll->paid_at?->format('d M Y H:i') }}
                </div>
                @endif

                @if($payroll->approvedBy)
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-person-check mr-1"></i>
                    Approved by {{ $payroll->approvedBy->name }}
                </p>
                @endif
            </div>
        </div>

    </div>{{-- /col-md-4 --}}

    {{-- ── RIGHT COLUMN ────────────────────────────────────────────────────── --}}
    <div class="col-md-8">

        {{-- Summary totals --}}
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-2">
                <div class="card text-center p-2 h-100">
                    <div class="h5 mb-0">{{ number_format($payroll->base_salary, 2) }}</div>
                    <small class="text-muted">Base Salary</small>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="card text-center p-2 h-100">
                    <div class="h5 text-success mb-0">+{{ number_format($payroll->allowances, 2) }}</div>
                    <small class="text-muted">Earnings</small>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="card text-center p-2 h-100">
                    <div class="h5 text-danger mb-0">-{{ number_format($payroll->deductions, 2) }}</div>
                    <small class="text-muted">Deductions</small>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="card text-center p-2 h-100 border-primary">
                    <div class="h5 text-primary font-weight-bold mb-0">{{ number_format($payroll->net_pay, 2) }}</div>
                    <small class="text-muted">Net Pay ({{ $payroll->currency }})</small>
                </div>
            </div>
        </div>

        {{-- Formula --}}
        <div class="alert alert-light border py-2 mb-3 small">
            <i class="bi bi-calculator mr-1"></i>
            <strong>Net Pay</strong> = Base Salary
            @if($payroll->allowances > 0) + Earnings @endif
            − Tax − Pension
            @php $otherDed = $payroll->deductions - $payroll->income_tax - $payroll->employee_pension; @endphp
            @if($otherDed > 0) − Other Deductions @endif
            = <strong>{{ $payroll->currency }} {{ number_format($payroll->net_pay, 2) }}</strong>
        </div>

        {{-- Statutory deductions --}}
        @if($payroll->income_tax > 0 || $payroll->employee_pension > 0)
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="bi bi-bank mr-1"></i>Statutory Deductions</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @if($payroll->income_tax > 0)
                    <tr>
                        <td>Income Tax (Ethiopian progressive)</td>
                        <td class="text-right text-danger font-weight-bold">
                            {{ $payroll->currency }} {{ number_format($payroll->income_tax, 2) }}
                        </td>
                    </tr>
                    @endif
                    @if($payroll->employee_pension > 0)
                    <tr>
                        <td>Employee Pension (7%)</td>
                        <td class="text-right text-danger font-weight-bold">
                            {{ $payroll->currency }} {{ number_format($payroll->employee_pension, 2) }}
                        </td>
                    </tr>
                    @endif
                    @if($payroll->employer_pension > 0)
                    <tr class="table-light">
                        <td class="text-muted">Employer Pension (11%) — not deducted from employee</td>
                        <td class="text-right text-muted">
                            {{ $payroll->currency }} {{ number_format($payroll->employer_pension, 2) }}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        @endif

        {{-- Manual pay items --}}
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-ul mr-1"></i>Pay Items
                    <small class="text-muted font-weight-normal">(bonuses, allowances, penalties)</small>
                </h6>
            </div>
            <div class="card-body p-0">
                @php
                    $manualItems = $payroll->items->filter(fn($i) =>
                        !in_array($i->label, ['Basic Salary','Income Tax','Employee Pension (7%)','Employer Pension (11%)'])
                    );
                @endphp
                @if($manualItems->count())
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Note</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($manualItems as $item)
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
                @else
                <p class="text-muted text-center py-3 mb-0 small">No manual items.</p>
                @endif
            </div>
        </div>

        {{-- Notes --}}
        @if($payroll->notes)
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-sticky mr-1"></i>Notes</h6></div>
            <div class="card-body py-2 small">{{ $payroll->notes }}</div>
        </div>
        @endif

    </div>{{-- /col-md-8 --}}
</div>
@endsection
