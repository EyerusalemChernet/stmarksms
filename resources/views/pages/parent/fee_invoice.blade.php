@extends('layouts.master')
@section('page_title', 'Invoice ' . $invoice->invoice_no)
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb bg-transparent p-0">
    <li class="breadcrumb-item"><a href="{{ route('parent.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parent.fees') }}">School Fees</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_no }}</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoice {{ $invoice->invoice_no }}</h6>
        <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'danger' }} px-3">
          {{ strtoupper($invoice->status) }}
        </span>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-sm-6">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="text-muted" width="130">Student</td><td><strong>{{ $invoice->student->name ?? '-' }}</strong></td></tr>
              <tr><td class="text-muted">Class</td><td>{{ optional($invoice->fee_structure->my_class)->name ?? '-' }}</td></tr>
              <tr><td class="text-muted">Fee type</td><td>{{ optional($invoice->fee_structure->category)->name ?? '-' }}</td></tr>
              <tr><td class="text-muted">Session</td><td>{{ $invoice->session }}</td></tr>
              <tr><td class="text-muted">Due date</td><td>{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</td></tr>
            </table>
          </div>
          <div class="col-sm-6">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="text-muted" width="130">Original fee</td><td>ETB {{ number_format($invoice->original_amount, 2) }}</td></tr>
              <tr>
                <td class="text-muted">Discount</td>
                <td class="text-success">
                  @if($invoice->discount > 0)
                    - ETB {{ number_format($invoice->discount, 2) }}
                    <br><small>{{ $invoice->discount_reason }}</small>
                  @else
                    None
                  @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted">Penalty</td>
                <td class="{{ $invoice->fine > 0 ? 'text-warning' : '' }}">
                  @if($invoice->fine > 0)
                    + ETB {{ number_format($invoice->fine, 2) }}
                    <br><small>{{ $invoice->fine_reason }}</small>
                  @else
                    None
                  @endif
                </td>
              </tr>
              <tr><td class="text-muted">Net amount</td><td><strong>ETB {{ number_format($invoice->net_amount, 2) }}</strong></td></tr>
              <tr><td class="text-muted">Amount paid</td><td class="text-success">ETB {{ number_format($invoice->amount_paid, 2) }}</td></tr>
              <tr style="border-top:2px solid #e2e8f0">
                <td class="text-muted"><strong>Balance due</strong></td>
                <td class="{{ $invoice->balance > 0 ? 'text-danger' : 'text-success' }}">
                  <strong>ETB {{ number_format($invoice->balance, 2) }}</strong>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    @if($invoice->payments->isNotEmpty())
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0">Payment history</h6></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="thead-light"><tr><th>Receipt</th><th>Amount</th><th>Date</th></tr></thead>
          <tbody>
            @foreach($invoice->payments as $pay)
            <tr>
              <td><code>{{ $pay->receipt_no }}</code></td>
              <td class="text-success">ETB {{ number_format($pay->amount, 2) }}</td>
              <td>{{ $pay->paid_at ? $pay->paid_at->format('d M Y') : '-' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif
  </div>

  <div class="col-md-4">
    @if($discountType)
    <div class="card mb-3 border-success">
      <div class="card-header bg-success text-white py-2"><h6 class="mb-0">Automatic discount applied</h6></div>
      <div class="card-body" style="font-size:13px;">
        <p class="mb-1"><strong>{{ \App\Services\DiscountService::discountTypeLabel($discountType) }}</strong></p>
        <p class="mb-0 text-muted">
          This discount was applied automatically based on school rules.
          @if($discountType === 'sibling')
            Your family has {{ $familyInfo['children_count'] }} enrolled children ({{ $familyInfo['sibling_pct'] }}% off).
          @else
            You are registered as school staff / employee parent ({{ $familyInfo['employee_pct'] }}% off).
          @endif
        </p>
      </div>
    </div>
    @endif

    @if($isOverdue && $invoice->fine > 0)
    <div class="card mb-3 border-warning">
      <div class="card-header bg-warning py-2"><h6 class="mb-0">Late payment penalty</h6></div>
      <div class="card-body" style="font-size:13px;">
        <p class="mb-1">This invoice is <strong>overdue</strong>.</p>
        <p class="mb-0">Penalty charged: <strong class="text-warning">ETB {{ number_format($invoice->fine, 2) }}</strong></p>
        @if($penaltyInfo['enabled'])
        <hr class="my-2">
        <small class="text-muted">Policy: {{ $penaltyInfo['grace_days'] }} day grace, then {{ $penaltyInfo['description'] }}.</small>
        @endif
      </div>
    </div>
    @elseif($penaltyInfo['enabled'] && $invoice->balance > 0 && $invoice->due_date)
    <div class="card mb-3 border-light">
      <div class="card-body py-2" style="font-size:12px;">
        <i class="bi bi-info-circle mr-1"></i>
        Pay before <strong>{{ $invoice->due_date ? $invoice->due_date->addDays($penaltyInfo['grace_days'])->format('d M Y') : 'N/A' }}</strong>
        to avoid a late penalty.
      </div>
    </div>
    @endif

    @if($invoice->balance > 0 && $invoice->chapa_status !== 'pending')
    <form action="{{ route('parent.fee.chapa', Qs::hash($invoice->id)) }}" method="POST" class="mb-2">
      @csrf
      <button type="submit" class="btn btn-success btn-block">
        <i class="bi bi-credit-card mr-1"></i> Pay ETB {{ number_format($invoice->balance, 2) }} with Chapa
      </button>
    </form>
    @elseif($invoice->chapa_status === 'pending')
    <div class="alert alert-warning py-2 mb-2" style="font-size:12px;">
      <i class="bi bi-hourglass-split mr-1"></i> Online payment in progress. Refresh after completing payment on Chapa.
    </div>
    @endif
    <a href="{{ route('parent.fees') }}" class="btn btn-light btn-sm btn-block">Back to all fees</a>
  </div>
</div>
@endsection
