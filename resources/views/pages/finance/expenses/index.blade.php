@extends('layouts.master')
@section('page_title', 'Expenses')
@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h5 class="mb-0"><i class="bi bi-arrow-down-circle mr-2 text-danger"></i>Expenses — {{ $year }}</h5>
            <form method="GET" class="ml-3 d-flex gap-2">
                <select name="category_id" class="form-control form-control-sm" style="width:160px">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-secondary ml-1">Filter</button>
            </form>
        </div>
        <div>
            <a href="{{ route('finance.expenses.create') }}" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Expense</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th>#</th><th>Title</th><th>Category</th><th>Date</th><th>Amount (ETB)</th><th>Receipt No</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($expenses as $i => $exp)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $exp->title }}</td>
                    <td><span class="badge badge-secondary">{{ $exp->category->name ?? '-' }}</span></td>
                    <td>{{ $exp->expense_date }}</td>
                    <td class="text-danger font-weight-bold">{{ number_format($exp->amount,2) }}</td>
                    <td>{{ $exp->receipt_no ?? '-' }}</td>
                    <td>
                        <a href="{{ route('finance.expenses.edit', $exp->id) }}" class="btn btn-warning btn-xs"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('finance.expenses.destroy', $exp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No expenses recorded.</td></tr>
                @endforelse
            </tbody>
            @if($expenses->count())
            <tfoot>
                <tr class="table-danger"><td colspan="4" class="text-right font-weight-bold">Total:</td><td class="font-weight-bold">ETB {{ number_format($total,2) }}</td><td colspan="2"></td></tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
