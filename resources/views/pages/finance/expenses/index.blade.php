@extends('layouts.master')
@section('page_title','Expenses')
@section('content')

{{-- Summary --}}
<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body py-3 d-flex justify-content-between align-items-center">
      <div><div style="font-size:11px;color:#94a3b8">FILTERED TOTAL</div>
      <div style="font-size:20px;font-weight:700;color:#ef4444">ETB {{ number_format($total,2) }}</div></div>
      <i class="bi bi-cash-stack text-danger" style="font-size:28px;opacity:.4"></i>
    </div></div>
  </div>
</div>

{{-- Filters --}}
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="form-row align-items-end">
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Search</label>
      <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Title...">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Category</label>
      <select name="category_id" class="form-control form-control-sm">
        <option value="">All</option>
        @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">From</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Recurring</label>
      <select name="recurring" class="form-control form-control-sm">
        <option value="">All</option>
        <option value="1" {{ request('recurring')==='1'?'selected':'' }}>Yes</option>
        <option value="0" {{ request('recurring')==='0'?'selected':'' }}>No</option>
      </select>
    </div>
    <div class="col-md-2 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('expenses.index') }}" class="btn btn-light btn-sm">Reset</a>
    </div>
  </form>
</div></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0"><i class="bi bi-receipt-cutoff mr-2"></i>Expenses ({{ $expenses->total() }})</h6>
    <div class="d-flex gap-2">
      <a href="{{ route('expenses.csv') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success btn-sm"><i class="bi bi-download mr-1"></i>CSV</a>
      <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Expense</a>
      <a href="{{ route('expense_cats.index') }}" class="btn btn-light btn-sm"><i class="bi bi-tags mr-1"></i>Categories</a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Date</th>
          <th>Title</th>
          <th class="d-none d-md-table-cell">Category</th>
          <th>Amount</th>
          <th class="d-none d-md-table-cell">Recurring</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($expenses as $exp)
        <tr>
          <td>{{ $exp->expense_date->format('d M Y') }}</td>
          <td>
            <strong>{{ $exp->title }}</strong>
            @if($exp->description)<br><small class="text-muted">{{ Str::limit($exp->description,60) }}</small>@endif
          </td>
          <td class="d-none d-md-table-cell">{{ $exp->category->name ?? '-' }}</td>
          <td class="text-danger font-weight-bold">ETB {{ number_format($exp->amount,2) }}</td>
          <td class="d-none d-md-table-cell">
            @if($exp->recurring)<span class="badge badge-info">{{ ucfirst($exp->recurrence_interval) }}</span>@else<span class="text-muted">-</span>@endif
          </td>
          <td class="text-nowrap">
            <a href="{{ route('expenses.edit',$exp->id) }}" class="btn btn-warning btn-xs"><i class="bi bi-pencil"></i></a>
            <form action="{{ route('expenses.destroy',$exp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?')">@csrf @method('DELETE')<button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button></form>
            @if($exp->receipt_file)
            <a href="{{ asset('storage/'.$exp->receipt_file) }}" target="_blank" class="btn btn-light btn-xs"><i class="bi bi-paperclip"></i></a>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No expenses found.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted">Showing {{ $expenses->firstItem() }}–{{ $expenses->lastItem() }} of {{ $expenses->total() }}</small>
      {{ $expenses->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
