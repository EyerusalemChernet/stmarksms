@extends('layouts.master')

@section('page_title', $canManage ? 'Manage Penalty Rules' : 'Penalty Rules (View Only)')

@section('content')



@if(!$canManage)

<div class="alert alert-secondary mb-4">

  <i class="bi bi-eye mr-2"></i>

  <strong>View only.</strong> Late payment penalty rules are defined by the Super Admin.

</div>

@else

<div class="alert alert-warning mb-4">

  <i class="bi bi-shield-check mr-2"></i>

  <strong>Super Admin configuration.</strong> Set due dates, penalties, and automatic parent reminders for unpaid fees.

</div>

@endif



<div class="row justify-content-center">

  <div class="col-md-9">

    <div class="card shadow-sm">

      <div class="card-header d-flex justify-content-between align-items-center">

        <h6 class="mb-0"><i class="bi bi-exclamation-triangle mr-2 text-warning"></i>Late Payment Penalties</h6>

        @if($rules['enabled'])

          <span class="badge badge-success">Active</span>

        @else

          <span class="badge badge-secondary">Disabled</span>

        @endif

      </div>

      <div class="card-body">

        @if($canManage)

        <form action="{{ route('penalties.update') }}" method="POST">

          @csrf

          <div class="form-group">

            <div class="custom-control custom-switch">

              <input type="checkbox" class="custom-control-input" id="late_fee_enabled" name="late_fee_enabled" value="1"

                     {{ old('late_fee_enabled', $rules['enabled']) ? 'checked' : '' }}>

              <label class="custom-control-label" for="late_fee_enabled">Enable automatic late payment penalties</label>

            </div>

          </div>



          <div class="row">

            <div class="col-md-6">

              <div class="form-group">

                <label>Default invoice due date (days from creation)</label>

                <input type="number" name="late_fee_default_due_days" class="form-control @error('late_fee_default_due_days') is-invalid @enderror"

                       min="1" max="365" value="{{ old('late_fee_default_due_days', $rules['default_due_days']) }}" required>

                <small class="text-muted">New student invoices get a due date this many days after they are created.</small>

                @error('late_fee_default_due_days')<div class="invalid-feedback">{{ $message }}</div>@enderror

              </div>

            </div>

            <div class="col-md-6">

              <div class="form-group">

                <label>Notify parents after (days past due date)</label>

                <input type="number" name="late_fee_notify_after_days" class="form-control @error('late_fee_notify_after_days') is-invalid @enderror"

                       min="0" max="365" value="{{ old('late_fee_notify_after_days', $rules['notify_after_days']) }}" required>

                <small class="text-muted">Parents receive a message when fees are still unpaid this many days after the due date.</small>

                @error('late_fee_notify_after_days')<div class="invalid-feedback">{{ $message }}</div>@enderror

              </div>

            </div>

          </div>



          <div class="form-group">

            <label>Parent reminder frequency</label>

            <select name="late_fee_penalty_frequency" class="form-control @error('late_fee_penalty_frequency') is-invalid @enderror" required>

              <option value="once" {{ old('late_fee_penalty_frequency', $rules['frequency']) === 'once' ? 'selected' : '' }}>Once per invoice</option>

              <option value="daily" {{ old('late_fee_penalty_frequency', $rules['frequency']) === 'daily' ? 'selected' : '' }}>Daily (if still unpaid)</option>

              <option value="weekly" {{ old('late_fee_penalty_frequency', $rules['frequency']) === 'weekly' ? 'selected' : '' }}>Weekly (if still unpaid)</option>

            </select>

            <small class="text-muted">How often to remind parents about the same overdue invoice (also runs daily via system schedule).</small>

            @error('late_fee_penalty_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror

          </div>



          <div class="form-group">

            <label>Grace period (days after due date before penalty)</label>

            <input type="number" name="late_fee_grace_days" class="form-control @error('late_fee_grace_days') is-invalid @enderror"

                   min="0" max="365" value="{{ old('late_fee_grace_days', $rules['grace_days']) }}" required>

            <small class="text-muted">No penalty until this many days after the invoice due date.</small>

            @error('late_fee_grace_days')<div class="invalid-feedback">{{ $message }}</div>@enderror

          </div>

          <div class="form-group">

            <label>Penalty type</label>

            <select name="late_fee_type" class="form-control @error('late_fee_type') is-invalid @enderror" required>

              <option value="percent" {{ old('late_fee_type', $rules['type']) === 'percent' ? 'selected' : '' }}>Percentage of balance</option>

              <option value="fixed" {{ old('late_fee_type', $rules['type']) === 'fixed' ? 'selected' : '' }}>Fixed amount (ETB)</option>

            </select>

            @error('late_fee_type')<div class="invalid-feedback">{{ $message }}</div>@enderror

          </div>

          <div class="form-group">

            <label>Penalty amount</label>

            <input type="number" name="late_fee_amount" class="form-control @error('late_fee_amount') is-invalid @enderror"

                   step="0.01" min="0" value="{{ old('late_fee_amount', $rules['amount']) }}" required>

            <small class="text-muted">Use % when type is percentage, or ETB when fixed.</small>

            @error('late_fee_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror

          </div>

          <button type="submit" class="btn btn-primary"><i class="bi bi-save mr-1"></i>Save Penalty Rules</button>

        </form>

        <hr>

        <form action="{{ route('penalties.apply_now') }}" method="POST" class="d-inline"

              onsubmit="return confirm('Apply penalties to all overdue invoices now?')">

          @csrf

          <button type="submit" class="btn btn-warning btn-sm">

            <i class="bi bi-lightning mr-1"></i>Apply Penalties to Overdue Invoices Now

          </button>

        </form>

        @else

        <table class="table table-sm table-borderless mb-0">

          <tr><td class="text-muted" style="width:220px">Status</td><td>{{ $rules['enabled'] ? 'Enabled' : 'Disabled' }}</td></tr>

          <tr><td class="text-muted">Default due date</td><td>{{ $rules['default_due_days'] }} day(s) after invoice creation</td></tr>

          <tr><td class="text-muted">Parent notify after</td><td>{{ $rules['notify_after_days'] }} day(s) past due date</td></tr>

          <tr><td class="text-muted">Reminder frequency</td><td>{{ $rules['frequency_label'] }}</td></tr>

          <tr><td class="text-muted">Grace period</td><td>{{ $rules['grace_days'] }} day(s) after due date</td></tr>

          <tr><td class="text-muted">Penalty type</td><td>{{ $rules['type'] === 'percent' ? 'Percentage' : 'Fixed amount' }}</td></tr>

          <tr><td class="text-muted">Penalty amount</td><td>

            @if($rules['type'] === 'percent')

              {{ $rules['amount'] }}% of balance

            @else

              ETB {{ number_format($rules['amount'], 2) }}

            @endif

          </td></tr>

        </table>

        @endif

      </div>

    </div>

  </div>

</div>

@endsection


