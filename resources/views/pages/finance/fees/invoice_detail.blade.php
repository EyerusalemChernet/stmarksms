@extends('layouts.master')
@section('page_title','Invoice Detail')
@section('content')
@php $inv = $invoice; @endphp

@if($canManageFees && !\App\Helpers\Qs::userIsSuperAdmin())
  @php $saEvents = \App\Services\AdminFeeAudit::eventsForInvoice($inv); @endphp
  @if($saEvents->isNotEmpty())
  <div class="alert alert-info mb-3">
    <h6 class="alert-heading mb-2" style="font-size:14px;"><i class="bi bi-shield-check mr-1"></i>Super Admin activity (fee setup &amp; invoice)</h6>
    <ul class="mb-0 pl-3" style="font-size:13px;">
      @foreach($saEvents as $event)
      <li class="mb-1">
        <strong>{{ $event['action'] === 'created' ? 'Created' : 'Updated' }}</strong> by Super Admin —
        {{ $event['label'] }} —
        <strong>{{ $event['user'] }}</strong> —
        <strong>{{ \App\Services\AdminFeeAudit::formatAt($event['at']) }}</strong>
      </li>
      @endforeach
    </ul>
  </div>
  @endif
@endif

<div class="row">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoice: <code>{{ $inv->invoice_no }}</code></h6>
        <span class="badge badge-{{ $inv->status==='paid'?'success':'danger' }} px-3 py-2" style="font-size:13px;">{{ strtoupper($inv->status) }}</span>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-sm-6">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="text-muted" style="width:130px">Student</td><td><strong>{{ $inv->student->name ?? '-' }}</strong></td></tr>
              <tr><td class="text-muted">Class</td><td>{{ $inv->fee_structure->my_class->name ?? '-' }}</td></tr>
              <tr><td class="text-muted">Fee Type</td><td>{{ $inv->fee_structure->category->name ?? '-' }}</td></tr>
              <tr><td class="text-muted">Session</td><td>{{ $inv->session }}</td></tr>
              <tr><td class="text-muted">Due Date</td><td>{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : 'Not set' }}</td></tr>
            </table>
          </div>
          <div class="col-sm-6">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="text-muted" style="width:130px">Amount</td><td><strong>ETB {{ number_format($inv->net_amount,2) }}</strong></td></tr>
              @if($inv->status === 'paid')
              <tr><td class="text-muted">Paid</td><td class="text-success"><strong>ETB {{ number_format($inv->amount_paid,2) }}</strong></td></tr>
              @else
              <tr style="border-top:2px solid #e2e8f0"><td class="text-muted">Amount Due</td><td class="text-danger"><strong>ETB {{ number_format($inv->balance,2) }}</strong></td></tr>
              @endif
            </table>
          </div>
        </div>
        @if($canManageFees && \App\Services\AdminFeeAudit::hasEventsForInvoice($inv))
        <div class="mt-3 pt-2 border-top" style="font-size:12px;">
          <strong class="text-info"><i class="bi bi-shield-check mr-1"></i>Super Admin activity</strong>
          <div class="mt-2">
            @include('pages.finance.fees._invoice_admin_activities', ['inv' => $inv])
          </div>
        </div>
        @endif
      </div>
    </div>
    @if($inv->payments->isNotEmpty())
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history mr-2"></i>Payment Record</h6></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="thead-light"><tr><th>Receipt</th><th>Amount</th><th>Method</th><th>Ref</th><th>By</th><th>Date</th><th></th></tr></thead>
          <tbody>
            @foreach($inv->payments as $pay)
            <tr>
              <td><code>{{ $pay->receipt_no }}</code></td>
              <td class="text-success font-weight-bold">ETB {{ number_format($pay->amount,2) }}</td>
              <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$pay->payment_method)) }}</span></td>
              <td>{{ $pay->transaction_ref ?? '-' }}</td>
              <td>{{ $pay->collector->name ?? '-' }}</td>
              <td>{{ $pay->paid_at ? $pay->paid_at->format('d M Y H:i') : '-' }}</td>
              <td><a href="{{ route('fees.receipt', Qs::hash($pay->id)) }}" class="btn btn-light btn-xs" target="_blank"><i class="bi bi-printer"></i></a></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif
    <a href="{{ route('fees.invoices') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back to Invoices</a>
  </div>
  <div class="col-md-4">
    @if($canEditInvoices && $inv->status !== 'paid' && $inv->amount_paid <= 0 && !$inv->admin_updated_at)
    <div class="card mb-3 border-primary">
      <div class="card-header bg-primary text-white py-2"><h6 class="mb-0"><i class="bi bi-pencil-square mr-2"></i>Edit Invoice</h6></div>
      <div class="card-body">
        <form action="{{ route('fees.invoice.update', Qs::hash($inv->id)) }}" method="POST">@csrf @method('PUT')
          <div class="form-group">
            <label>Amount (ETB) *</label>
            <input type="number" name="original_amount" class="form-control" step="0.01" min="0" value="{{ $inv->original_amount }}" required>
          </div>
          <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" value="{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('Y-m-d') : '' }}">
          </div>
          <div class="form-group">
            <label>Reason for change *</label>
            <textarea name="update_note" class="form-control" rows="2" required placeholder="Visible to accountants with date & time"></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-sm">Save changes</button>
        </form>
      </div>
    </div>
    @endif

    @if($inv->balance > 0 && $canRecordFeePayments)
    <div class="card mb-3 border-success">
      <div class="card-header bg-success text-white py-2"><h6 class="mb-0"><i class="bi bi-cash-coin mr-2"></i>Record Cash Payment</h6></div>
      <div class="card-body">
        <p class="text-muted small mb-3">Full amount recorded as <strong>cash</strong> only.</p>
        <div class="alert alert-light border mb-3 py-2 text-center">
          <small class="text-muted d-block">Amount to pay</small>
          <strong class="text-success" style="font-size:22px;">ETB {{ number_format($inv->balance, 2) }}</strong>
        </div>
        <form action="{{ route('fees.pay', Qs::hash($inv->id)) }}" method="POST">@csrf
          <div class="form-group mb-3">
            <label class="text-muted d-block mb-1" style="font-size:12px;">Payment method</label>
            <span class="badge badge-success px-3 py-2" style="font-size:13px;"><i class="bi bi-cash mr-1"></i>Cash</span>
          </div>
          <div class="form-group"><label>Transaction Ref</label><input type="text" name="transaction_ref" class="form-control" placeholder="Optional"></div>
          <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
          <button type="submit" class="btn btn-success btn-block"><i class="bi bi-check-circle mr-1"></i>Record Cash Payment</button>
        </form>
      </div>
    </div>
    @elseif($inv->balance > 0 && !$canRecordFeePayments)
    <div class="alert alert-secondary small">
      <i class="bi bi-info-circle mr-1"></i>Cash payments are recorded by the <strong>accountant</strong> only.
      @if($canEditInvoices)
      You can edit this invoice above.
      @endif
    </div>
    @endif
  </div>
</div>
@endsection
