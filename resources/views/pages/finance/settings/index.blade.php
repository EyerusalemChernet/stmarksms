@extends('layouts.master')
@section('page_title', 'Finance Settings')
@section('content')
<div class="row">
    {{-- Expense Categories --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags mr-2 text-danger"></i>Expense Categories</h6></div>
            <div class="card-body">
                <form action="{{ route('finance.settings.expense_cat') }}" method="POST" class="d-flex mb-3">
                    @csrf
                    <input type="text" name="name" class="form-control form-control-sm mr-2" placeholder="Category name" required>
                    <button class="btn btn-danger btn-sm">Add</button>
                </form>
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Name</th><th>Expenses</th><th></th></tr></thead>
                    <tbody>
                        @forelse($expense_categories as $cat)
                        <tr>
                            <td>{{ $cat->name }}</td>
                            <td>{{ $cat->expenses_count }}</td>
                            <td>
                                @if(\App\Services\FinancePermission::canDeleteExpenseCategories())
                                <form action="{{ route('finance.settings.expense_cat_del', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-xs" title="Super Admin only"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center">No categories yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Income Categories --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags mr-2 text-success"></i>Income Categories</h6></div>
            <div class="card-body">
                <form action="{{ route('finance.settings.income_cat') }}" method="POST" class="d-flex mb-3">
                    @csrf
                    <input type="text" name="name" class="form-control form-control-sm mr-2" placeholder="Category name" required>
                    <button class="btn btn-success btn-sm">Add</button>
                </form>
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Name</th><th>Records</th><th></th></tr></thead>
                    <tbody>
                        @forelse($income_categories as $cat)
                        <tr>
                            <td>{{ $cat->name }}</td>
                            <td>{{ $cat->incomes_count }}</td>
                            <td>
                                <form action="{{ route('finance.settings.income_cat_del', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center">No categories yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Late Fee Rules --}}
    <div class="col-md-12">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history mr-2 text-warning"></i>Late Fee Rules</h6></div>
            <div class="card-body">
                <form action="{{ route('finance.settings.late_fee') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Late Fee Percentage (%)</label>
                                <input type="number" name="late_fee_percentage" class="form-control" step="0.1" min="0" max="100" value="{{ $late_fee_percentage }}" required>
                                <small class="text-muted">Percentage of invoice amount charged as late fee</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Fixed Late Fee Amount (ETB)</label>
                                <input type="number" name="late_fee_fixed_amount" class="form-control" step="0.01" min="0" value="{{ $late_fee_fixed_amount }}" required>
                                <small class="text-muted">Fixed amount charged per late payment</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Grace Period (Days)</label>
                                <input type="number" name="grace_period_days" class="form-control" min="0" value="{{ $grace_period_days }}" required>
                                <small class="text-muted">Days after due date before late fee applies</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save mr-1"></i>Update Late Fee Rules</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
