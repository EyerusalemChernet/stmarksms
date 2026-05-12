@extends('layouts.master')
@section('page_title','Salary Structures')
@section('content')
<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-badge mr-2"></i>Set Salary Structure</h6></div>
      <div class="card-body">
        <form action="{{ route('salary.store') }}" method="POST">@csrf
          <div class="form-group"><label>Staff Member *</label>
            <select name="user_id" class="form-control" required>
              <option value="">-- Select Staff --</option>
              @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst(str_replace('_',' ',$s->user_type)) }})</option>@endforeach
            </select>
          </div>
          <div class="form-row">
            <div class="form-group col-6"><label>Basic Salary (ETB) *</label><input type="number" name="basic_salary" class="form-control" step="0.01" min="0" required></div>
            <div class="form-group col-6"><label>Housing Allowance</label><input type="number" name="housing_allowance" class="form-control" step="0.01" min="0" value="0"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-6"><label>Transport Allowance</label><input type="number" name="transport_allowance" class="form-control" step="0.01" min="0" value="0"></div>
            <div class="form-group col-6"><label>Other Allowances</label><input type="number" name="other_allowances" class="form-control" step="0.01" min="0" value="0"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-4"><label>Income Tax %</label><input type="number" name="income_tax_pct" class="form-control" step="0.01" min="0" max="100" value="0"></div>
            <div class="form-group col-4"><label>Loan/Month</label><input type="number" name="loan_repayment" class="form-control" step="0.01" min="0" value="0"></div>
            <div class="form-group col-4"><label>Absence/Day</label><input type="number" name="absence_deduction_rate" class="form-control" step="0.01" min="0" value="0"></div>
          </div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-save mr-1"></i>Save Structure</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-list-ul mr-2"></i>Active Salary Structures</h6>
        <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left mr-1"></i>Payroll</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:13px;">
          <thead class="thead-light"><tr><th>Staff</th><th>Basic</th><th>Allowances</th><th>Tax%</th><th>Loan</th><th></th></tr></thead>
          <tbody>
            @forelse($structures as $uid => $s)
            <tr>
              <td><strong>{{ $s->staff->name ?? '-' }}</strong><br><small class="text-muted">{{ ucfirst(str_replace('_',' ',$s->staff->user_type ?? '')) }}</small></td>
              <td>ETB {{ number_format($s->basic_salary,2) }}</td>
              <td>ETB {{ number_format($s->housing_allowance + $s->transport_allowance + $s->other_allowances,2) }}</td>
              <td>{{ $s->income_tax_pct }}%</td>
              <td>ETB {{ number_format($s->loan_repayment,2) }}</td>
              <td class="text-nowrap">
                <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editSalary{{ $s->id }}"><i class="bi bi-pencil"></i></button>
                <form action="{{ route('salary.destroy',$s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this salary structure?')">@csrf @method('DELETE')<button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button></form>
              </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No salary structures yet.</td></tr>
            @endforelse
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
@foreach($structures as $s)
<div class="modal fade" id="editSalary{{ $s->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h6 class="modal-title">Edit: {{ $s->staff->name ?? 'Staff' }}</h6><button type="button" class="close" data-dismiss="modal">&times;</button></div>
  <form action="{{ route('salary.update',$s->id) }}" method="POST">@csrf @method('PUT')
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group col-6"><label>Basic Salary *</label><input type="number" name="basic_salary" class="form-control" step="0.01" min="0" value="{{ $s->basic_salary }}" required></div>
        <div class="form-group col-6"><label>Housing Allowance</label><input type="number" name="housing_allowance" class="form-control" step="0.01" min="0" value="{{ $s->housing_allowance }}"></div>
      </div>
      <div class="form-row">
        <div class="form-group col-6"><label>Transport Allowance</label><input type="number" name="transport_allowance" class="form-control" step="0.01" min="0" value="{{ $s->transport_allowance }}"></div>
        <div class="form-group col-6"><label>Other Allowances</label><input type="number" name="other_allowances" class="form-control" step="0.01" min="0" value="{{ $s->other_allowances }}"></div>
      </div>
      <div class="form-row">
        <div class="form-group col-4"><label>Tax %</label><input type="number" name="income_tax_pct" class="form-control" step="0.01" min="0" max="100" value="{{ $s->income_tax_pct }}"></div>
        <div class="form-group col-4"><label>Loan/Month</label><input type="number" name="loan_repayment" class="form-control" step="0.01" min="0" value="{{ $s->loan_repayment }}"></div>
        <div class="form-group col-4"><label>Absence/Day</label><input type="number" name="absence_deduction_rate" class="form-control" step="0.01" min="0" value="{{ $s->absence_deduction_rate }}"></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Update</button></div>
  </form>
</div></div></div>
@endforeach
@endsection