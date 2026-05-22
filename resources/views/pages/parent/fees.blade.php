@extends('layouts.master')
@section('page_title', 'School Fees')
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb bg-transparent p-0">
    <li class="breadcrumb-item"><a href="{{ route('parent.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">School Fees</li>
  </ol>
</nav>

{{-- Family discount info (automatic — no request needed) --}}
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card border-info">
      <div class="card-header bg-info text-white py-2">
        <h6 class="mb-0"><i class="bi bi-info-circle mr-2"></i>Your Family &amp; Automatic Discounts</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-2">
            <strong>Children enrolled:</strong> {{ $familyInfo['children_count'] }}
            @if($familyInfo['children_count'] > 0)
              <ul class="mb-0 pl-3 small text-muted">
                @foreach($familyInfo['children'] as $ch)
                  <li>{{ $ch->user->name ?? '-' }} ({{ $ch->my_class->name ?? '-' }})</li>
                @endforeach
              </ul>
            @endif
          </div>
          <div class="col-md-4 mb-2">
            <strong>Sibling discount</strong> (2+ children, same parent):
            @if($familyInfo['sibling_eligible'])
              <span class="badge badge-primary">{{ $familyInfo['sibling_pct'] }}%</span>
              <span class="text-success small d-block">Applied automatically on invoices</span>
            @else
              <span class="text-muted small">Not applicable (need 2+ children)</span>
            @endif
          </div>
          <div class="col-md-4 mb-2">
            <strong>Employee child discount</strong> (parent is school staff):
            @if($familyInfo['employee_eligible'])
              <span class="badge badge-success">{{ $familyInfo['employee_pct'] }}%</span>
              <span class="text-success small d-block">Applied automatically on invoices</span>
            @else
              <span class="text-muted small">Not applicable</span>
            @endif
          </div>
        </div>
        @if($familyInfo['active_rule'])
        <div class="alert alert-success mb-0 mt-2 py-2" style="font-size:13px;">
          <i class="bi bi-check-circle mr-1"></i>
          Active rule for your family: <strong>{{ \App\Services\DiscountService::discountTypeLabel($familyInfo['active_rule']) }}</strong>
          — school fees are calculated with this discount automatically. No request is required.
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Penalty policy --}}
@if($penaltyInfo['enabled'])
<div class="alert alert-warning mb-4" style="font-size:13px;">
  <i class="bi bi-exclamation-triangle mr-2"></i>
  <strong>Late payment penalty:</strong> {{ $penaltyInfo['grace_days'] }} day(s) after the due date, then
  {{ $penaltyInfo['description'] }}.
  Overdue invoices show the penalty amount below.
</div>
@endif

{{-- Summary --}}
<div class="row mb-3">
  <div class="col-md-2 col-6 mb-2"><div class="card text-center py-2"><small class="text-muted">Original</small><strong>ETB {{ number_format($totals['original'], 2) }}</strong></div></div>
  <div class="col-md-2 col-6 mb-2"><div class="card text-center py-2 border-success"><small class="text-muted">Discounts</small><strong class="text-success">- ETB {{ number_format($totals['discount'], 2) }}</strong></div></div>
  <div class="col-md-2 col-6 mb-2"><div class="card text-center py-2 border-warning"><small class="text-muted">Penalties</small><strong class="text-warning">+ ETB {{ number_format($totals['fine'], 2) }}</strong></div></div>
  <div class="col-md-2 col-6 mb-2"><div class="card text-center py-2"><small class="text-muted">Net</small><strong>ETB {{ number_format($totals['net'], 2) }}</strong></div></div>
  <div class="col-md-2 col-6 mb-2"><div class="card text-center py-2 border-success"><small class="text-muted">Paid</small><strong class="text-success">ETB {{ number_format($totals['paid'], 2) }}</strong></div></div>
  <div class="col-md-2 col-6 mb-2"><div class="card text-center py-2 border-danger"><small class="text-muted">Balance</small><strong class="text-danger">ETB {{ number_format($totals['balance'], 2) }}</strong></div></div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Fee Invoices</h6>
    @if($totals['unpaid'] > 0)
      <span class="badge badge-danger">{{ $totals['unpaid'] }} unpaid</span>
    @endif
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:13px;">
        <thead class="thead-light">
          <tr>
            <th>Invoice</th>
            <th>Student</th>
            <th>Fee</th>
            <th>Original</th>
            <th>Discount</th>
            <th>Penalty</th>
            <th>Balance</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
          @php $overdue = \App\Services\PenaltyService::isOverdue($inv); @endphp
          <tr>
            <td><code style="font-size:11px">{{ $inv->invoice_no }}</code></td>
            <td>{{ $inv->student->name ?? '-' }}</td>
            <td>{{ optional($inv->fee_structure->category)->name ?? '-' }}</td>
            <td>ETB {{ number_format($inv->original_amount, 2) }}</td>
            <td class="text-success">
              @if($inv->discount > 0)
                - ETB {{ number_format($inv->discount, 2) }}
                <br><small>{{ $inv->discount_reason }}</small>
              @else
                —
              @endif
            </td>
            <td class="{{ $inv->fine > 0 ? 'text-warning' : '' }}">
              @if($inv->fine > 0)
                + ETB {{ number_format($inv->fine, 2) }}
                @if($overdue)<br><small class="text-danger">Overdue</small>@endif
              @elseif($overdue)
                <small class="text-muted">Due soon</small>
              @else
                —
              @endif
            </td>
            <td class="{{ $inv->balance > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
              ETB {{ number_format($inv->balance, 2) }}
            </td>
            <td>
              @if($inv->status === 'paid')<span class="badge badge-success">Paid</span>
              @elseif($inv->status === 'partial')<span class="badge badge-warning">Partial</span>
              @else<span class="badge badge-danger">Unpaid</span>@endif
            </td>
            <td>
              <a href="{{ route('parent.fee', Qs::hash($inv->id)) }}" class="btn btn-info btn-xs">Details</a>
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center text-muted py-4">No fee invoices yet for your children.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
