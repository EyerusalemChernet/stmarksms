@extends('layouts.master')
@section('page_title', 'Leave Policies')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-shield-check mr-2"></i>Leave Policies</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Back to HR
    </a>
</div>

<div class="row">
    {{-- Add / Edit Policy --}}
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Add / Update Policy</h6></div>
            <div class="card-body">
                <form action="{{ route('hr.leave.policies.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type" class="form-control" required>
                                @foreach(['annual'=>'Annual','sick'=>'Sick','maternity'=>'Maternity','paternity'=>'Paternity','unpaid'=>'Unpaid','other'=>'Other'] as $v=>$l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" class="form-control"
                                   value="{{ $year }}" min="2020" max="2099" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Days Entitled</label>
                            <input type="number" name="days_entitled" class="form-control"
                                   min="0" max="365" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Paid?</label>
                            <select name="is_paid" class="form-control">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Carry Forward?</label>
                            <select name="carry_forward" class="form-control">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Policy</button>
                </form>
            </div>
        </div>

        {{-- Initialise Balances --}}
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="bi bi-arrow-repeat mr-1"></i>Initialise Balances</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    Creates leave balance records for all active employees based on the policies above.
                    Safe to run multiple times — won't overwrite existing balances.
                </p>
                <form action="{{ route('hr.leave.init_balances') }}" method="POST" class="form-inline">
                    @csrf
                    <input type="number" name="year" value="{{ $year }}" min="2020" max="2099"
                           class="form-control form-control-sm mr-2" style="width:90px;">
                    <button type="submit" class="btn btn-sm btn-success"
                            onclick="return confirm('Initialise leave balances for all active employees?')">
                        <i class="bi bi-play-circle mr-1"></i>Initialise
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Policy List --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Policies for {{ $year }}</h6>
                <form action="{{ route('hr.leave.policies') }}" method="GET" class="form-inline mb-0">
                    <input type="number" name="year" value="{{ $year }}" min="2020" max="2099"
                           class="form-control form-control-sm mr-1" style="width:80px;">
                    <button type="submit" class="btn btn-sm btn-outline-primary">View</button>
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Leave Type</th><th>Days</th><th>Paid</th><th>Carry Fwd</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $p)
                        <tr>
                            <td class="font-weight-bold">{{ $p->leaveTypeLabel() }}</td>
                            <td><span class="badge badge-primary">{{ $p->days_entitled }}</span></td>
                            <td>
                                @if($p->is_paid)
                                    <span class="badge badge-success">Paid</span>
                                @else
                                    <span class="badge badge-secondary">Unpaid</span>
                                @endif
                            </td>
                            <td>
                                @if($p->carry_forward)
                                    <span class="badge badge-info">Yes</span>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('hr.leave.policies.destroy', $p->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete this policy?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No policies for {{ $year }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
