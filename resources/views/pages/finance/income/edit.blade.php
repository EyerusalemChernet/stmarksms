@extends('layouts.master')
@section('page_title', 'Edit Income')
@section('content')
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-pencil mr-2"></i>Edit Income Record</h6></div>
            <div class="card-body">
                <form action="{{ route('finance.income.update', $income->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ $income->title }}" required>
                    </div>
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-control" required>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ $income->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (ETB) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0" value="{{ $income->amount }}" required>
                    </div>
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="income_date" class="form-control" value="{{ $income->income_date }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $income->description }}</textarea>
                    </div>
                    <button class="btn btn-primary btn-block">Update Record</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
