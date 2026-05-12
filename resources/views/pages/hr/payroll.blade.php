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
                            <img src="{{ $emp->photo_url }}" width="26" height="26"
                                 class="rounded-circle" style="object-fit:cover;">
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
                        <a href="{{ route('hr.payroll.edit', $pr->id) }}" class="btn btn-xs btn-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($pr->isDraft())
                        <form action="{{ route('hr.payroll.approve', $pr->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-info" title="Approve"
                                    onclick="return confirm('Approve this payroll?')">
                                <i class="bi bi-check"></i>
                            </button>
                        </form>
                        @elseif($pr->isApproved())
                        <form action="{{ route('hr.payroll.paid', $pr->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-success" title="Mark Paid"
                                    onclick="return confirm('Mark as paid?')">
                                <i class="bi bi-cash"></i>
                            </button>
                        </form>
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
