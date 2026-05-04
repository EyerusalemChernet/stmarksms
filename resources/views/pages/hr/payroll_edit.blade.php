@extends('layouts.master')
@section('page_title', 'Payroll — ' . $payroll->employee->full_name)
@section('content')

@php $emp = $payroll->employee; @endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-cash-stack mr-2"></i>Payroll Detail
        <span class="badge badge-{{ $payroll->statusBadgeClass() }} ml-1">{{ ucfirst($payroll->status) }}</span>
    </h5>
    <div>
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

        {{-- Workflow actions --}}
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Workflow</h6></div>
            <div class="card-body">
                @if($payroll->isDraft())
                <form action="{{ route('hr.payroll.approve', $payroll->id) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-info btn-block"
                            onclick="return confirm('Approve this payroll?')">
                        <i class="bi bi-check-circle mr-1"></i>Approve Payroll
                    </button>
                </form>
                @elseif($payroll->isApproved())
                <form action="{{ route('hr.payroll.paid', $payroll->id) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success btn-block"
                            onclick="return confirm('Mark as paid?')">
                        <i class="bi bi-cash mr-1"></i>Mark as Paid
                    </button>
                </form>
                <form action="{{ route('hr.payroll.draft', $payroll->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-block btn-sm"
                            onclick="return confirm('Revert to draft for editing?')">
                        <i class="bi bi-arrow-counterclockwise mr-1"></i>Revert to Draft
                    </button>
                </form>
                @elseif($payroll->isPaid())
                <div class="alert alert-success py-2 mb-0">
                    <i class="bi bi-check-circle mr-1"></i>
                    Paid on {{ $payroll->paid_at?->format('d M Y H:i') }}
                </div>
                @endif

                @if($payroll->approved_at)
                <p class="text-muted small mt-2 mb-0">
                    Approved: {{ $payroll->approved_at->format('d M Y H:i') }}
                </p>
                @endif
            </div>
        </div>
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

        {{-- Line items --}}
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-list-ul mr-1"></i>Pay Items</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Type</th><th>Description</th><th>Note</th><th class="text-right">Amount</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($payroll->items as $item)
                        <tr class="{{ $item->isEarning() ? 'table-success' : 'table-danger' }}" style="--bs-table-bg:transparent;">
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
                            <td>
                                @if($payroll->isDraft())
                                <form action="{{ route('hr.payroll.item.remove', $payroll->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                    <button type="submit" class="btn btn-xs btn-outline-danger"
                                            onclick="return confirm('Remove this item?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-2">No items yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Add item form (draft only) --}}
            @if($payroll->isDraft())
            <div class="card-footer bg-white">
                <form action="{{ route('hr.payroll.item.add', $payroll->id) }}" method="POST" class="form-inline">
                    @csrf
                    <select name="type" class="form-control form-control-sm mr-2" style="width:110px;" required>
                        <option value="earning">Earning</option>
                        <option value="deduction">Deduction</option>
                    </select>
                    <input type="text" name="label" class="form-control form-control-sm mr-2"
                           placeholder="Description" style="width:160px;" required>
                    <input type="number" name="amount" step="0.01" min="0"
                           class="form-control form-control-sm mr-2"
                           placeholder="Amount" style="width:110px;" required>
                    <input type="text" name="note" class="form-control form-control-sm mr-2"
                           placeholder="Note (optional)" style="width:130px;">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus mr-1"></i>Add
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Edit base salary (draft only) --}}
        @if($payroll->isDraft())
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Edit Base Salary</h6></div>
            <div class="card-body">
                <form action="{{ route('hr.payroll.update', $payroll->id) }}" method="POST" class="form-inline">
                    @csrf @method('PUT')
                    <div class="form-group mr-2">
                        <label class="mr-1 small font-weight-bold">Base Salary</label>
                        <input type="number" name="base_salary" step="0.01" min="0"
                               value="{{ $payroll->base_salary }}"
                               class="form-control form-control-sm" style="width:140px;" required>
                    </div>
                    <div class="form-group mr-2">
                        <label class="mr-1 small font-weight-bold">Notes</label>
                        <input type="text" name="notes" value="{{ $payroll->notes }}"
                               class="form-control form-control-sm" style="width:200px;">
                    </div>
                    <button type="submit" class="btn btn-sm btn-success">Save</button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
