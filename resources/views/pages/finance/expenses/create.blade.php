@extends('layouts.master')
@section('page_title', 'Add Expense')
@section('content')
<div class="card" style="max-width:600px;">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-arrow-down-circle mr-2 text-danger"></i>Add Expense</h5></div>
    <div class="card-body">
        <form action="{{ route('finance.expenses.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Amount (ETB)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0" required value="{{ old('amount') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expense_date" class="form-control" required value="{{ old('expense_date', date('Y-m-d')) }}">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Receipt No</label>
                <input type="text" name="receipt_no" class="form-control" value="{{ old('receipt_no') }}">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="btn btn-danger">Save Expense</button>
            <a href="{{ route('finance.expenses.index') }}" class="btn btn-light ml-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
