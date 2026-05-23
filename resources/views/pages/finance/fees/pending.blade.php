@extends('layouts.master')
@section('page_title','Pending Balances')
@section('content')
<div class="card mb-3 border-danger"><div class="card-body py-2 d-flex justify-content-between align-items-center">
  <div><span style="font-size:13px;color:#64748b">Total Pending:</span><span style="font-size:22px;font-weight:700;color:#ef4444;margin-left:10px">ETB {{ number_format($total_pending,2) }}</span></div>
  <form method="GET" class="d-flex gap-2">
    <select name="class_id" class="form-control form-control-sm" style="width:140px"><option value="">All Classes</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select>
    <input type="text" name="session_filter" class="form-control form-control-sm" value="{{ $session }}" style="width:110px">
    <button class="btn btn-secondary btn-sm">Filter</button>
  </form>
</div></div>
<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-exclamation-circle mr-2 text-danger"></i>Pending / Partial ({{ $invoices->count() }})</h6></div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light"><tr><th>Student</th><th>Class</th><th>Fee Type</th><th>Net</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th><th></th></tr></thead>
      <tbody>
        @forelse($invoices as $inv)
        <tr>
          <td><strong>{{ $inv->student->name ?? '-' }}</strong></td>
          <td>{{ $inv->fee_structure->my_class->name ?? '-' }}</td>
          <td>{{ $inv->fee_structure->category->name ?? '-' }}</td>
          <td>{{ number_format($inv->net_amount,2) }}</td>
          <td class="text-success">{{ number_format($inv->amount_paid,2) }}</td>
          <td class="text-danger font-weight-bold">{{ number_format($inv->balance,2) }}</td>
          <td><span class="badge badge-danger">Unpaid</span></td>
          <td>@if($inv->due_date && $inv->due_date < now()->toDateString())<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> {{ $inv->due_date }}</span>@else{{ $inv->due_date ?? '-' }}@endif</td>
          <td><a href="{{ route('fees.invoice', Qs::hash($inv->id)) }}" class="btn btn-success btn-xs"><i class="bi bi-cash-coin"></i> Pay</a></td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-success py-4"><i class="bi bi-check-circle mr-2"></i>All fees cleared!</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
