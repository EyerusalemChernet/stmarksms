@extends('layouts.master')
@section('page_title', $canManage ? 'Manage Discount Rules' : 'Discount Rules (View Only)')
@section('content')

@if(!$canManage)
<div class="alert alert-secondary mb-4">
  <i class="bi bi-eye mr-2"></i>
  <strong>View only.</strong> Discount percentages are defined by the Super Admin.
  You can use these rules when requesting discounts on individual invoices.
</div>
@else
<div class="alert alert-info mb-4">
  <i class="bi bi-shield-check mr-2"></i>
  <strong>Super Admin configuration.</strong> Set global discount rates for the two automatic rule types below.
  Changes apply to all eligible open invoices immediately when saved.
</div>
@endif

<div class="row mb-4">
  <div class="col-md-6 mb-3">
    <div class="card border-primary h-100">
      <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="bi bi-people mr-2"></i>Sibling Discount</h6>
      </div>
      <div class="card-body">
        <p class="text-muted mb-2" style="font-size:13px;">
          Applies when <strong>one parent/guardian has two or more children</strong> enrolled in the school (active students).
        </p>
        <div class="text-center py-2">
          <div style="font-size:36px;font-weight:700;color:#3b82f6">{{ $currentSibling }}%</div>
          <small class="text-muted">of invoice original amount</small>
        </div>
        <p class="mb-0 small text-muted">
          <strong>{{ $eligible['sibling_count'] }}</strong> student(s) in <strong>{{ $eligible['sibling_families']->count() }}</strong> eligible famil{{ $eligible['sibling_families']->count() === 1 ? 'y' : 'ies' }}.
        </p>
      </div>
    </div>
  </div>
  <div class="col-md-6 mb-3">
    <div class="card border-success h-100">
      <div class="card-header bg-success text-white py-2">
        <h6 class="mb-0"><i class="bi bi-person-badge mr-2"></i>Employee Child Discount</h6>
      </div>
      <div class="card-body">
        <p class="text-muted mb-2" style="font-size:13px;">
          Applies when the student's <strong>parent/guardian is school staff</strong> (teacher, HR, admin, etc.).
        </p>
        <div class="text-center py-2">
          <div style="font-size:36px;font-weight:700;color:#22c55e">{{ $currentEmployee }}%</div>
          <small class="text-muted">of invoice original amount</small>
        </div>
        <p class="mb-0 small text-muted">
          <strong>{{ $eligible['employee_count'] }}</strong> eligible student(s) linked to staff parents.
        </p>
      </div>
    </div>
  </div>
</div>

@if($canManage)
<div class="card mb-4 shadow-sm">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-pencil-square mr-2"></i>Edit Discount Rates</h6></div>
  <div class="card-body">
    <form action="{{ route('discount_rules.store') }}" method="POST">
      @csrf
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="font-weight-bold">Sibling Discount (%)</label>
            <div class="input-group">
              <input type="number" name="sibling_discount_pct" class="form-control @error('sibling_discount_pct') is-invalid @enderror"
                     step="0.5" min="0" max="100" value="{{ old('sibling_discount_pct', $currentSibling) }}" required>
              <div class="input-group-append"><span class="input-group-text">%</span></div>
            </div>
            @error('sibling_discount_pct')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="font-weight-bold">Employee Child Discount (%)</label>
            <div class="input-group">
              <input type="number" name="employee_discount_pct" class="form-control @error('employee_discount_pct') is-invalid @enderror"
                     step="0.5" min="0" max="100" value="{{ old('employee_discount_pct', $currentEmployee) }}" required>
              <div class="input-group-append"><span class="input-group-text">%</span></div>
            </div>
            @error('employee_discount_pct')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save mr-1"></i>Save Discount Rules</button>
    </form>
  </div>
</div>
@endif

<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card h-100">
      <div class="card-header py-2"><h6 class="mb-0">Sibling Families (2+ children)</h6></div>
      <div class="card-body p-0" style="max-height:320px;overflow-y:auto;">
        <table class="table table-sm mb-0" style="font-size:12px;">
          <thead class="thead-light"><tr><th>Parent</th><th>Children</th><th>Students</th></tr></thead>
          <tbody>
            @forelse($eligible['sibling_families'] as $fam)
            <tr>
              <td>{{ $fam['parent_name'] }}</td>
              <td>
                @foreach($fam['children'] as $ch)
                  <div>{{ $ch['name'] }} <small class="text-muted">({{ $ch['class'] }})</small></div>
                @endforeach
              </td>
              <td><span class="badge badge-primary">{{ $fam['count'] }}</span></td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center text-muted py-3">No sibling families found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6 mb-3">
    <div class="card h-100">
      <div class="card-header py-2"><h6 class="mb-0">Employee Children</h6></div>
      <div class="card-body p-0" style="max-height:320px;overflow-y:auto;">
        <table class="table table-sm mb-0" style="font-size:12px;">
          <thead class="thead-light"><tr><th>Student</th><th>Class</th><th>Staff Parent</th></tr></thead>
          <tbody>
            @forelse($eligible['employee_children'] as $row)
            <tr>
              <td>{{ $row['student_name'] }}</td>
              <td>{{ $row['class'] }}</td>
              <td>{{ $row['parent_name'] }} <small class="text-muted">({{ $row['parent_role'] }})</small></td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center text-muted py-3">No employee-child students found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="mt-2">
  <a href="{{ route('discount_requests.index') }}" class="btn btn-light btn-sm"><i class="bi bi-percent mr-1"></i>Discount Requests</a>
</div>
@endsection
