@extends('layouts.master')
@section('page_title', 'Request Discount')
@section('content')

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoice <code>{{ $invoice->invoice_no }}</code></h6>
        <a href="{{ route('fees.invoice', Qs::hash($invoice->id)) }}" class="btn btn-light btn-sm">
          <i class="bi bi-arrow-left mr-1"></i>Back to Invoice
        </a>
      </div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr><td class="text-muted" style="width:130px">Student</td><td><strong>{{ $invoice->student->name ?? '-' }}</strong></td></tr>
          <tr><td class="text-muted">Fee</td><td>{{ optional($invoice->fee_structure->category)->name ?? '-' }}</td></tr>
          <tr><td class="text-muted">Net Amount</td><td>ETB {{ number_format($invoice->net_amount, 2) }}</td></tr>
          <tr><td class="text-muted">Balance</td><td class="text-danger font-weight-bold">ETB {{ number_format($invoice->balance, 2) }}</td></tr>
        </table>
      </div>
    </div>

    @if($existing)
    <div class="alert alert-warning">
      <i class="bi bi-hourglass-split mr-1"></i>
      A pending discount request already exists for this invoice (ETB {{ number_format($existing->requested_amount, 2) }}).
      <a href="{{ route('discount_requests.index') }}?status=pending" class="alert-link">View requests</a>
    </div>
    @else
    <div class="card border-info">
      <div class="card-header bg-info text-white py-2">
        <h6 class="mb-0"><i class="bi bi-percent mr-2"></i>Submit Discount Request</h6>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Requests are reviewed by an administrator before the discount is applied to the invoice.
        </p>
        <form action="{{ route('discount_requests.store', Qs::hash($invoice->id)) }}" method="POST">
          @csrf
          <div class="form-group">
            <label>Discount Type *</label>
            <select name="discount_type" class="form-control @error('discount_type') is-invalid @enderror" required>
              <option value="">-- Select type --</option>
              @foreach($types as $value => $label)
                <option value="{{ $value }}" {{ old('discount_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            @error('discount_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Requested Amount (ETB) *</label>
            <input type="number" name="requested_amount" class="form-control @error('requested_amount') is-invalid @enderror"
                   step="0.01" min="1" max="{{ $invoice->balance }}" value="{{ old('requested_amount') }}" required
                   placeholder="Max: {{ number_format($invoice->balance, 2) }}">
            @error('requested_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Reason *</label>
            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="4" required
                      minlength="10" placeholder="Explain why this discount should be granted (min. 10 characters)">{{ old('reason') }}</textarea>
            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Supporting Information <small class="text-muted">(optional)</small></label>
            <textarea name="supporting_info" class="form-control" rows="2" placeholder="Reference numbers, documents, etc.">{{ old('supporting_info') }}</textarea>
          </div>
          <button type="submit" class="btn btn-info">
            <i class="bi bi-send mr-1"></i>Submit Request
          </button>
        </form>
      </div>
    </div>
    @endif
  </div>
</div>

@endsection
