@extends('layouts.master')
@section('page_title','Process Payroll')
@section('content')
<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-calculator mr-2"></i>Process Monthly Payroll</h6>
        <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back</a>
      </div>
      <div class="card-body">
        <form action="{{ route('payroll.store') }}" method="POST">@csrf
          <div class="form-group">
            <label>Staff Member *</label>
            <select name="user_id" class="form-control" required id="staffSelect" onchange="loadStructure(this.value)">
              <option value="">-- Select Staff --</option>
              @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Month *</label>
              <select name="month" class="form-control" required>
                @foreach(['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $m)
                  @if($i > 0)<option value="{{ $i }}" {{ now()->month==$i?'selected':'' }}>{{ $m }}</option>@endif
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Year *</label>
              <input type="number" name="year" class="form-control" value="{{ now()->year }}" min="2000" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Bonus (ETB)</label>
              <input type="number" name="bonus" class="form-control" step="0.01" min="0" value="0">
            </div>
            <div class="form-group col-md-6">
              <label>Absence Days</label>
              <input type="number" name="absence_days" class="form-control" min="0" max="31" value="0">
            </div>
          </div>

          {{-- Salary preview --}}
          <div id="salaryPreview" class="alert alert-info" style="display:none;font-size:13px;">
            <strong>Salary Structure Preview</strong>
            <div id="previewContent"></div>
          </div>

          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle mr-1"></i>Process Payroll</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
@section('scripts')
<script>
var structures = @json($structures);
function loadStructure(uid) {
    var s = structures[uid];
    if (!s) { document.getElementById('salaryPreview').style.display='none'; return; }
    document.getElementById('salaryPreview').style.display='block';
    document.getElementById('previewContent').innerHTML =
        '<div class="row mt-2">' +
        '<div class="col-6">Basic: <strong>ETB ' + parseFloat(s.basic_salary).toFixed(2) + '</strong></div>' +
        '<div class="col-6">Housing: <strong>ETB ' + parseFloat(s.housing_allowance).toFixed(2) + '</strong></div>' +
        '<div class="col-6">Transport: <strong>ETB ' + parseFloat(s.transport_allowance).toFixed(2) + '</strong></div>' +
        '<div class="col-6">Tax: <strong>' + s.income_tax_pct + '%</strong></div>' +
        '<div class="col-6">Loan/mo: <strong>ETB ' + parseFloat(s.loan_repayment).toFixed(2) + '</strong></div>' +
        '</div>';
}
</script>
@endsection
