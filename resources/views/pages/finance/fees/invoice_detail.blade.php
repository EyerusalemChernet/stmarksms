@extends('layouts.master')
@section('page_title','Invoice Detail')
@section('content')
@php $inv = $invoice; @endphp
<div class="row">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoice: <code>{{ $inv->invoice_no }}</code></h6>
        <span class="badge badge-{{ $inv->status==='paid'?'success':($inv->status==='partial'?'warning':'danger') }} px-3 py-2" style="font-size:13px;">{{ strtoupper($inv->status) }}</span>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-sm-6">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="text-muted" style="width:130px">Student</td><td><strong>{{ $inv->student->name ?? '-' }}</strong></td></tr>
              <tr><td class="text-muted">Class</td><td>{{ $inv->fee_structure->my_class->name ?? '-' }}</td></tr>
              <tr><td class="text-muted">Fee Type</td><td>{{ $inv->fee_structure->category->name ?? '-' }}</td></tr>
              <tr><td class="text-muted">Session</td><td>{{ $inv->session }}</td></tr>
              <tr><td class="text-muted">Due Date</td><td>{{ $inv->due_date ?? 'Not set' }}</td></tr>
            </table>
          </div>
          <div class="col-sm-6">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="text-muted" style="width:130px">Original</td><td>ETB {{ number_format($inv->original_amount,2) }}</td></tr>
              <tr><td class="text-muted">Discount</td><td class="text-success">- ETB {{ number_format($inv->discount,2) }} @if($inv->discount_reason)<small>({{ $inv->discount_reason }})</small>@endif</td></tr>
              <tr><td class="text-muted">Fine</td><td class="text-danger">+ ETB {{ number_format($inv->fine,2) }} @if($inv->fine_reason)<small>({{ $inv->fine_reason }})</small>@endif</td></tr>
              <tr><td class="text-muted">Net Amount</td><td><strong>ETB {{ number_format($inv->net_amount,2) }}</strong></td></tr>
              <tr><td class="text-muted">Paid</td><td class="text-success"><strong>ETB {{ number_format($inv->amount_paid,2) }}</strong></td></tr>
              <tr style="border-top:2px solid #e2e8f0"><td class="text-muted">Balance</td><td class="{{ $inv->balance>0?'text-danger':'text-success' }}"><strong>ETB {{ number_format($inv->balance,2) }}</strong></td></tr>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history mr-2"></i>Payment History ({{ $inv->payments->count() }} installments)</h6></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="thead-light"><tr><th>#</th><th>Receipt</th><th>Amount</th><th>Method</th><th>Ref</th><th>By</th><th>Date</th><th></th></tr></thead>
          <tbody>
            @forelse($inv->payments as $pay)
            <tr>
              <td>{{ $pay->installment_no }}</td>
              <td><code>{{ $pay->receipt_no }}</code></td>
              <td class="text-success font-weight-bold">ETB {{ number_format($pay->amount,2) }}</td>
              <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$pay->payment_method)) }}</span></td>
              <td>{{ $pay->transaction_ref ?? '-' }}</td>
              <td>{{ $pay->collector->name ?? '-' }}</td>
              <td>{{ $pay->paid_at ? $pay->paid_at->format('d M Y H:i') : '-' }}</td>
              <td><a href="{{ route('fees.receipt', Qs::hash($pay->id)) }}" class="btn btn-light btn-xs" target="_blank"><i class="bi bi-printer"></i></a></td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-3">No payments yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <a href="{{ route('fees.invoices') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back to Invoices</a>
    @if($inv->balance > 0)
    <a href="{{ route('discount_requests.create', Qs::hash($inv->id)) }}" class="btn btn-outline-info btn-sm ml-1">
      <i class="bi bi-percent mr-1"></i>Request Discount
    </a>
    @endif
  </div>
  <div class="col-md-4">
    @if($inv->status !== 'paid')
    <div class="card mb-3 border-success">
      <div class="card-header bg-success text-white py-2"><h6 class="mb-0"><i class="bi bi-cash-coin mr-2"></i>Record Payment (Installment #{{ $installment_no }})</h6></div>
      <div class="card-body">
        <form action="{{ route('fees.pay', Qs::hash($inv->id)) }}" method="POST">@csrf
          <div class="form-group">
            <label>Amount (ETB) *</label>
            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" step="0.01" min="0.01" max="{{ $inv->balance }}" value="{{ old('amount') }}" required placeholder="Max: {{ number_format($inv->balance, 2) }}">
            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Payment Method *</label>
            <select name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
              <option value="cash" {{ old('payment_method','cash')==='cash'?'selected':'' }}>Cash</option>
              <option value="bank_transfer" {{ old('payment_method')==='bank_transfer'?'selected':'' }}>Bank Transfer</option>
              <option value="mobile_money" {{ old('payment_method')==='mobile_money'?'selected':'' }}>Mobile Money</option>
              <option value="chapa" {{ old('payment_method')==='chapa'?'selected':'' }}>Chapa</option>
            </select>
            @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group"><label>Transaction Ref</label><input type="text" name="transaction_ref" class="form-control" placeholder="Optional"></div>
          <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
          <button type="submit" class="btn btn-success btn-block"><i class="bi bi-check-circle mr-1"></i>Record Payment</button>
        </form>
      </div>
    </div>
    @endif
    <div class="card mb-3 border-info">
      <div class="card-header py-2"><h6 class="mb-0"><i class="bi bi-percent mr-2 text-info"></i>Discount / Scholarship</h6></div>
      <div class="card-body">
        <form action="{{ route('fees.discount', Qs::hash($inv->id)) }}" method="POST">@csrf
          <div class="form-group">
            <label>Discount (ETB) *</label>
            <input type="number" name="discount" class="form-control @error('discount') is-invalid @enderror" step="0.01" min="0" max="{{ $inv->original_amount }}" value="{{ old('discount', $inv->discount) }}" required>
            @error('discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Reason *</label>
            <input type="text" name="discount_reason" class="form-control @error('discount_reason') is-invalid @enderror" value="{{ old('discount_reason', $inv->discount_reason) }}" placeholder="e.g. Scholarship" required>
            @error('discount_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <button type="submit" class="btn btn-info btn-sm btn-block">Apply Discount</button>
        </form>
      </div>
    </div>
    <div class="card border-warning">
      <div class="card-header py-2"><h6 class="mb-0"><i class="bi bi-exclamation-triangle mr-2 text-warning"></i>Late Fine</h6></div>
      <div class="card-body">
        <form action="{{ route('fees.fine', Qs::hash($inv->id)) }}" method="POST">@csrf
          <div class="form-group">
            <label>Fine (ETB) *</label>
            <input type="number" name="fine" class="form-control @error('fine') is-invalid @enderror" step="0.01" min="0" value="{{ old('fine', $inv->fine) }}" required>
            @error('fine')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Reason *</label>
            <input type="text" name="fine_reason" class="form-control @error('fine_reason') is-invalid @enderror" value="{{ old('fine_reason', $inv->fine_reason) }}" placeholder="e.g. Late payment" required>
            @error('fine_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <button type="submit" class="btn btn-warning btn-sm btn-block">Apply Fine</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
