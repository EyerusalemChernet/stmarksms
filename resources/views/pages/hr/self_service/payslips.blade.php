@extends('layouts.master')
@section('page_title', 'My Payslips')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-cash-stack mr-2"></i>My Payslips</h5>
    <a href="{{ route('my.profile') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>My Profile
    </a>
</div>

{{-- Year filter --}}
<div class="d-flex align-items-center mb-3" style="gap:8px;">
    <form action="{{ route('my.payslips') }}" method="GET" class="form-inline mb-0">
        <label class="font-weight-bold mr-2">Year:</label>
        <select name="year" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
            @if($years->isEmpty())
                <option value="{{ now()->year }}">{{ now()->year }}</option>
            @endif
        </select>
    </form>
    <span class="text-muted small">{{ $payrolls->count() }} payslip(s)</span>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Month</th>
                    <th>Base Salary</th>
                    <th class="text-success">Earnings</th>
                    <th class="text-danger">Deductions</th>
                    <th class="text-primary font-weight-bold">Net Pay</th>
                    <th class="text-center">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $pr)
                <tr>
                    <td class="font-weight-bold">{{ $pr->month }}</td>
                    <td>{{ $pr->currency }} {{ number_format($pr->base_salary, 2) }}</td>
                    <td class="text-success">
                        {{ $pr->currency }} {{ number_format($pr->base_salary + $pr->allowances, 2) }}
                    </td>
                    <td class="text-danger">
                        {{ $pr->currency }} {{ number_format($pr->deductions + $pr->income_tax + $pr->employee_pension, 2) }}
                    </td>
                    <td class="text-primary font-weight-bold">
                        {{ $pr->currency }} {{ number_format($pr->net_pay, 2) }}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $pr->statusBadgeClass() }}">{{ ucfirst($pr->status) }}</span>
                    </td>
                    <td>
                        <a href="{{ route('my.payslip', $pr->id) }}"
                           class="btn btn-xs btn-primary">
                            <i class="bi bi-eye mr-1"></i>View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No payslips found for {{ $year }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
