@extends('layouts.master')
@section('page_title', 'Payslip — ' . $payroll->month)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-receipt mr-2"></i>Payslip — {{ $payroll->month }}
        <span class="badge badge-{{ $payroll->statusBadgeClass() }} ml-1">{{ ucfirst($payroll->status) }}</span>
    </h5>
    <div style="gap:6px;" class="d-flex">
        <a href="{{ route('my.payslips') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>All Payslips
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer mr-1"></i>Print
        </button>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">

        {{-- Payslip card --}}
        <div class="card" id="payslip-print">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">St. Mark School</h5>
                    <small>Payslip for {{ $payroll->month }}</small>
                </div>
                <div class="text-right">
                    <div class="font-weight-bold">{{ $employee->full_name }}</div>
                    <small>{{ $employee->employee_code }}</small>
                </div>
            </div>
            <div class="card-body">

                {{-- Employee + period info --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="font-weight-bold text-muted" style="width:40%">Department</td>
                                <td>{{ $employee->employmentDetails?->department?->name ?? '—' }}</td></tr>
                            <tr><td class="font-weight-bold text-muted">Position</td>
                                <td>{{ $employee->employmentDetails?->position?->name ?? '—' }}</td></tr>
                            <tr><td class="font-weight-bold text-muted">Bank</td>
                                <td>{{ $employee->employmentDetails?->bank_name ?? '—' }}</td></tr>
                            <tr><td class="font-weight-bold text-muted">Account No.</td>
                                <td>{{ $employee->employmentDetails?->bank_account_no ?? '—' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="font-weight-bold text-muted" style="width:40%">Period</td>
                                <td>{{ $payroll->period_start?->format('d M') ?? '—' }} – {{ $payroll->period_end?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="font-weight-bold text-muted">Working Days</td>
                                <td>{{ $payroll->working_days }}</td></tr>
                            <tr><td class="font-weight-bold text-muted">Present</td>
                                <td><span class="text-success font-weight-bold">{{ $payroll->present_days }}</span></td></tr>
                            <tr><td class="font-weight-bold text-muted">Absent</td>
                                <td><span class="text-danger font-weight-bold">{{ $payroll->absent_days }}</span></td></tr>
                            @if($payroll->overtime_hours > 0)
                            <tr><td class="font-weight-bold text-muted">Overtime</td>
                                <td><span class="text-primary">{{ $payroll->overtime_hours }}h</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>

                <hr>

                {{-- Earnings & Deductions --}}
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold text-success mb-2">Earnings</h6>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Base Salary</td>
                                <td class="text-right">{{ $payroll->currency }} {{ number_format($payroll->base_salary, 2) }}</td>
                            </tr>
                            @foreach($payroll->items->where('type','earning') as $item)
                            <tr>
                                <td>{{ $item->label }}</td>
                                <td class="text-right text-success">+ {{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="table-success font-weight-bold">
                                <td>Total Earnings</td>
                                <td class="text-right">{{ $payroll->currency }} {{ number_format($payroll->base_salary + $payroll->allowances, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold text-danger mb-2">Deductions</h6>
                        <table class="table table-sm mb-0">
                            @if($payroll->income_tax > 0)
                            <tr>
                                <td>Income Tax</td>
                                <td class="text-right text-danger">- {{ number_format($payroll->income_tax, 2) }}</td>
                            </tr>
                            @endif
                            @if($payroll->employee_pension > 0)
                            <tr>
                                <td>Employee Pension (7%)</td>
                                <td class="text-right text-danger">- {{ number_format($payroll->employee_pension, 2) }}</td>
                            </tr>
                            @endif
                            @foreach($payroll->items->where('type','deduction') as $item)
                            <tr>
                                <td>{{ $item->label }}</td>
                                <td class="text-right text-danger">- {{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="table-danger font-weight-bold">
                                <td>Total Deductions</td>
                                <td class="text-right">{{ $payroll->currency }} {{ number_format($payroll->deductions + $payroll->income_tax + $payroll->employee_pension, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                {{-- Net pay --}}
                <div class="d-flex justify-content-between align-items-center py-2">
                    <h5 class="mb-0 font-weight-bold">Net Pay</h5>
                    <h4 class="mb-0 text-primary font-weight-bold">
                        {{ $payroll->currency }} {{ number_format($payroll->net_pay, 2) }}
                    </h4>
                </div>

                @if($payroll->employer_pension > 0)
                <div class="alert alert-light border py-2 mt-2 small">
                    <i class="bi bi-info-circle mr-1"></i>
                    Employer pension contribution (11%): {{ $payroll->currency }} {{ number_format($payroll->employer_pension, 2) }}
                    — paid by the school, not deducted from your salary.
                </div>
                @endif

                @if($payroll->notes)
                <div class="mt-2 text-muted small">
                    <i class="bi bi-chat-left-text mr-1"></i>{{ $payroll->notes }}
                </div>
                @endif

                @if($payroll->isPaid())
                <div class="alert alert-success py-2 mt-3">
                    <i class="bi bi-check-circle mr-1"></i>
                    Paid on {{ $payroll->paid_at?->format('d M Y') }}
                    @if($payroll->approvedBy) — Approved by {{ $payroll->approvedBy->name }} @endif
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
@media print {
    .sidebar, .navbar, .btn, .breadcrumb, h5.mb-0 + div { display: none !important; }
    #payslip-print { border: none !important; box-shadow: none !important; }
}
</style>
@endpush
@endsection
