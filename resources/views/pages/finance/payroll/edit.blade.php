@extends('layouts.master')
@section('page_title', 'Edit Payroll Record')
@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-pencil mr-2"></i>Edit Payroll — {{ $record->staff->name ?? '' }} ({{ $record->month }})</h5></div>
    <div class="card-body">
        <form action="{{ route('finance.payroll.update', $record->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Basic Salary (ETB)</label>
                        <input type="number" name="basic_salary" class="form-control" step="0.01" min="0" required value="{{ $record->basic_salary }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Allowances</label>
                        <input type="number" name="allowances" class="form-control" step="0.01" min="0" value="{{ $record->allowances }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Deductions</label>
                        <input type="number" name="deductions" class="form-control" step="0.01" min="0" value="{{ $record->deductions }}">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="pending" {{ $record->status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ $record->status === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" class="form-control">
                    <option value="bank_transfer" {{ $record->payment_method === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cash" {{ $record->payment_method === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="mobile_money" {{ $record->payment_method === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ $record->notes }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('finance.payroll.index') }}" class="btn btn-light ml-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
