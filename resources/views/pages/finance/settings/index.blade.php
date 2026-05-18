@extends('layouts.master')
@section('page_title', 'Finance Settings')
@section('content')
<div class="row">
    {{-- Expense Categories --}}
    <div class="col-md-6">
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
                                <form action="{{ route('finance.settings.expense_cat_del', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
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

    {{-- Income Categories --}}
    <div class="col-md-6">
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
@endsection
