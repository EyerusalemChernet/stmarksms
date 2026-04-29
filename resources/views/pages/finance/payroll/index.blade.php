@extends('layouts.master')
@section('page_title', 'Payroll')
@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-primary text-white"><div class="card-body py-3"><div class="d-flex justify-content-between"><div><div style="font-size:12px;opacity:.8">Total Net Salary</div><div style="font-size:22px;font-weight:700">ETB {{ number_format($total_net,2) }}</div></div><i class="bi bi-cash-stack" style="font-size:32px;opacity:.4"></i></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white"><div class="card-body py-3"><div class="d-flex justify-content-between"><div><div style="font-size:12px;opacity:.8">Total Paid</div><div style="font-size:22px;font-weight:700">ETB {{ number_format($total_paid,2) }}</div></div><i class="bi bi-check-circle" style="font-size:32px;opacity:.4"></i></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white"><div class="card-body py-3"><div class="d-flex justify-content-between"><div><div style="font-size:12px;opacity:.8">Pending</div><div style="font-size:22px;font-weight:700">ETB {{ number_format($total_net - $total_paid,2) }}</div></div><i class="bi bi-hourglass-split" style="font-size:32px;opacity:.4"></i></div></div></div>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0"><i class="bi bi-wallet2 mr-2"></i>Payroll</h5>
            <form method="GET" class="ml-3 d-flex">
                <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width:160px">
                <button class="btn btn-sm btn-secondary ml-2">Filter</button>
            </form>
        </div>
        <a href="{{ route('finance.payroll.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Record</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th>#</th><th>Staff</th><th>Month</th><th>Basic</th><th>Allowances</th><th>Deductions</th><th>Net</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($records as $i => $rec)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $rec->staff->name ?? 'N/A' }}</td>
                    <td>{{ $rec->month }}</td>
                    <td>{{ number_format($rec->basic_salary,2) }}</td>
                    <td>{{ number_format($rec->allowances,2) }}</td>
                    <td>{{ number_format($rec->deductions,2) }}</td>
                    <td><strong>{{ number_format($rec->net_salary,2) }}</strong></td>
                    <td>
                        @if($rec->status === 'paid')
                            <span class="badge badge-success">Paid</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($rec->status === 'pending')
                        <form action="{{ route('finance.payroll.mark_paid', $rec->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-success btn-xs" title="Mark Paid"><i class="bi bi-check-lg"></i></button>
                        </form>
                        @endif
                        <a href="{{ route('finance.payroll.edit', $rec->id) }}" class="btn btn-warning btn-xs" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a href="{{ route('finance.payroll.payslip', $rec->id) }}" class="btn btn-info btn-xs" title="Payslip"><i class="bi bi-file-earmark-text"></i></a>
                        <form action="{{ route('finance.payroll.destroy', $rec->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No payroll records for {{ $month }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
