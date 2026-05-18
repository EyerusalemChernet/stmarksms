@extends('layouts.master')
@section('page_title','Add Expense')
@section('content')
<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-plus-circle mr-2"></i>Add Expense</h6>
        <a href="{{ route('expenses.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back</a>
      </div>
      <div class="card-body">
        <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">@csrf
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Category *</label>
              <select name="expense_category_id" class="form-control" required>
                <option value="">-- Select --</option>
                @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Date *</label>
              <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
          </div>
          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Electricity Bill">
          </div>
          <div class="form-group">
            <label>Amount (ETB) *</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Receipt File <small class="text-muted">(PDF, JPG, PNG — max 5MB)</small></label>
            <input type="file" name="receipt_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <div class="custom-control custom-switch mt-4">
                <input type="checkbox" class="custom-control-input" id="recurring" name="recurring" value="1" onchange="document.getElementById('interval').style.display=this.checked?'block':'none'">
                <label class="custom-control-label" for="recurring">Recurring</label>
              </div>
            </div>
            <div class="form-group col-md-8" id="interval" style="display:none">
              <label>Recurrence Interval</label>
              <select name="recurrence_interval" class="form-control">
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="annually">Annually</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle mr-1"></i>Save Expense</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
