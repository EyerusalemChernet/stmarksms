@extends('layouts.master')
@section('page_title', 'Discount Rate Setup')
@section('content')

<div class="row justify-content-center">
  <div class="col-md-7">

    {{-- Info card --}}
    <div class="alert alert-info d-flex align-items-start mb-4">
      <i class="bi bi-info-circle mr-3 mt-1" style="font-size:20px;"></i>
      <div>
        <strong>Discount Rate Configuration</strong><br>
        <small>
          Set the default discount percentages for <strong>Sibling</strong> and <strong>Employee Child</strong> discounts.
          These rates are used as defaults when accountants submit discount requests.
          Changes take effect immediately.
        </small>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-percent mr-2 text-info"></i>Default Discount Rates</h6>
        <a href="{{ route('discount_rules.index') }}" class="btn btn-light btn-sm">
          <i class="bi bi-sliders mr-1"></i>Discount Rules
        </a>
      </div>
      <div class="card-body">
        <form action="{{ route('fees.discounts_setup.store') }}" method="POST">
          @csrf

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">
                  <i class="bi bi-people mr-1 text-primary"></i>Sibling Discount (%)
                </label>
                <div class="input-group">
                  <input type="number" name="sibling_discount_pct"
                         class="form-control @error('sibling_discount_pct') is-invalid @enderror"
                         step="0.5" min="0" max="100"
                         value="{{ old('sibling_discount_pct', $sibling_pct) }}" required>
                  <div class="input-group-append"><span class="input-group-text">%</span></div>
                </div>
                <small class="text-muted">Applied when a student has a sibling enrolled in the school.</small>
                @error('sibling_discount_pct')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">
                  <i class="bi bi-person-badge mr-1 text-success"></i>Employee Child Discount (%)
                </label>
                <div class="input-group">
                  <input type="number" name="employee_discount_pct"
                         class="form-control @error('employee_discount_pct') is-invalid @enderror"
                         step="0.5" min="0" max="100"
                         value="{{ old('employee_discount_pct', $employee_pct) }}" required>
                  <div class="input-group-append"><span class="input-group-text">%</span></div>
                </div>
                <small class="text-muted">Applied when a student's parent/guardian is a school employee.</small>
                @error('employee_discount_pct')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <hr>

          {{-- Preview --}}
          <div class="row mb-3" id="previewRow">
            <div class="col-md-6">
              <div class="card border-primary">
                <div class="card-body py-2 text-center">
                  <div style="font-size:11px;color:#94a3b8">SIBLING DISCOUNT</div>
                  <div style="font-size:24px;font-weight:700;color:#3b82f6" id="siblingPreview">{{ $sibling_pct }}%</div>
                  <small class="text-muted">of invoice amount</small>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card border-success">
                <div class="card-body py-2 text-center">
                  <div style="font-size:11px;color:#94a3b8">EMPLOYEE CHILD DISCOUNT</div>
                  <div style="font-size:24px;font-weight:700;color:#22c55e" id="employeePreview">{{ $employee_pct }}%</div>
                  <small class="text-muted">of invoice amount</small>
                </div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <i class="bi bi-save mr-1"></i>Save Discount Rates
          </button>
        </form>
      </div>
    </div>

    {{-- How to use --}}
    <div class="card mt-4 border-0 bg-light">
      <div class="card-body py-3">
        <h6 class="mb-2"><i class="bi bi-question-circle mr-2 text-muted"></i>How Discount Requests Work</h6>
        <ol class="mb-0 pl-3" style="font-size:13px;color:#64748b;">
          <li>Accountant opens an invoice → clicks <strong>"Request Discount"</strong></li>
          <li>Selects type (Sibling / Employee Child / Scholarship / etc.) and enters amount</li>
          <li>Request goes to Admin with <strong>Pending</strong> status</li>
          <li>Admin reviews → <strong>Approves</strong> (discount applied instantly) or <strong>Rejects</strong></li>
          <li>Accountant is notified of the decision</li>
        </ol>
      </div>
    </div>

  </div>
</div>

@endsection
@section('scripts')
<script>
document.querySelector('[name="sibling_discount_pct"]').addEventListener('input', function() {
    document.getElementById('siblingPreview').textContent = this.value + '%';
});
document.querySelector('[name="employee_discount_pct"]').addEventListener('input', function() {
    document.getElementById('employeePreview').textContent = this.value + '%';
});
</script>
@endsection


