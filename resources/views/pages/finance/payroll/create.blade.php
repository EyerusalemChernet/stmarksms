@extends('layouts.master')
@section('page_title', 'Add Payroll Record')
@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-wallet2 mr-2"></i>Add Payroll Record</h5></div>
    <div class="card-body">
        <form action="{{ route('finance.payroll.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Staff Member</label>
                <select name="staff_id" class="form-control" required>
                    <option value="">-- Select Staff --</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}">{{ $s->name }} ({{ ucwords(str_replace('_',' ',$s->user_type)) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Month</label>
                <input type="month" name="month" class="form-control" required value="{{ date('Y-m') }}">
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Basic Salary (ETB)</label>
                        <input type="number" name="basic_salary" class="form-control" step="0.01" min="0" required value="{{ old('basic_salary') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Allowances (ETB)</label>
                        <input type="number" name="allowances" class="form-control" step="0.01" min="0" value="{{ old('allowances', 0) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Deductions (ETB)</label>
                        <input type="number" name="deductions" class="form-control" step="0.01" min="0" value="{{ old('deductions', 0) }}">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" class="form-control">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="mobile_money">Mobile Money</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('finance.payroll.index') }}" class="btn btn-light ml-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
