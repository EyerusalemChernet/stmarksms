@extends('layouts.master')
@section('page_title', 'Other Income')
@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h5 class="mb-0"><i class="bi bi-arrow-up-circle mr-2 text-success"></i>Other Income — {{ $year }}</h5>
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
            <a href="{{ route('finance.income.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Income</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th>#</th><th>Title</th><th>Category</th><th>Date</th><th>Amount (ETB)</th><th>Ref No</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($incomes as $i => $inc)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $inc->title }}</td>
                    <td><span class="badge badge-secondary">{{ $inc->category->name ?? '-' }}</span></td>
                    <td>{{ $inc->income_date }}</td>
                    <td class="text-success font-weight-bold">{{ number_format($inc->amount,2) }}</td>
                    <td><code>{{ $inc->reference_no ?? '-' }}</code></td>
                    <td>
                        <a href="{{ route('finance.income.edit', $inc->id) }}" class="btn btn-warning btn-xs"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('finance.income.destroy', $inc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No income records found.</td></tr>
                @endforelse
            </tbody>
            @if($incomes->count())
            <tfoot>
                <tr class="table-success"><td colspan="4" class="text-right font-weight-bold">Total:</td><td class="font-weight-bold">ETB {{ number_format($total,2) }}</td><td colspan="2"></td></tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
