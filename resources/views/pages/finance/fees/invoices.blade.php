@extends('layouts.master')
@section('page_title','Fee Invoices')
@section('content')

@if($canManageFees && !\App\Helpers\Qs::userIsSuperAdmin())
<div class="alert alert-info mb-3 py-2" style="font-size:13px;">
  <i class="bi bi-info-circle mr-1"></i> When the Super Admin creates or edits a <strong>fee category</strong>, <strong>fee structure</strong>, or <strong>invoice</strong>, it appears in the <strong>Super Admin activity</strong> column with name, date, and time.
</div>
@endif

@if($canManageFees)
<div class="row mb-3">
  <div class="col-md-12">
    <div class="card border-primary">
      <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="bi bi-lightning-charge mr-2"></i>Generate Invoices</h6>
      </div>
      <div class="card-body py-3">
        @if($feeStructures->isEmpty())
          <p class="text-muted mb-2">No fee structures found. Create a fee structure first.</p>
          <a href="{{ route('fees.structures') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-diagram-3 mr-1"></i>Go to Fee Structures
          </a>
        @else
          <form action="{{ route('fees.invoices.generate') }}" method="POST" class="form-row align-items-end">
            @csrf
            <input type="hidden" name="redirect_to" value="invoices">
            <div class="col-md-5 mb-2">
              <label style="font-size:11px;color:#64748b;display:block">Fee Structure *</label>
              <select name="fee_structure_id" class="form-control form-control-sm" required>
                <option value="">-- Select fee structure --</option>
                @foreach($feeStructures as $s)
                  <option value="{{ $s->id }}">
                    {{ $s->category->name ?? 'Fee' }} &mdash; {{ $s->my_class->name ?? 'Class' }}
                    ({{ $s->session }}) &mdash; ETB {{ number_format($s->amount, 2) }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 mb-2">
              <label style="font-size:11px;color:#64748b;display:block">Class (optional)</label>
              <select name="my_class_id" class="form-control form-control-sm">
                <option value="">Use structure's class</option>
                @foreach($classes as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
              <small class="text-muted">Creates one invoice per active student in the class.</small>
            </div>
            <div class="col-md-4 mb-2">
              <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-receipt mr-1"></i>Generate Invoices for Class
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
@elseif($canEditInvoices)
<div class="alert alert-info mb-3 py-2" style="font-size:13px;">
  <i class="bi bi-pencil-square mr-1"></i>Super Admin: view and edit invoices below. Use <strong>Generate Invoices</strong> on the accountant account, or edit amount and due date on unpaid invoices.
</div>
@endif

<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" action="{{ route('fees.invoices') }}" class="form-row align-items-end">
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Search</label>
      <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Student / Invoice#">
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Class</label>
      <select name="class_id" class="form-control form-control-sm">
        <option value="">All Classes</option>
        @foreach($classes as $c)<option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Status</label>
      <select name="status" class="form-control form-control-sm">
        <option value="">All</option>
        <option value="unpaid" {{ request('status')==='unpaid'?'selected':'' }}>Unpaid</option>
        <option value="paid" {{ request('status')==='paid'?'selected':'' }}>Paid</option>
      </select>
    </div>
    <div class="col-md-2 col-6 mb-2">
      <label style="font-size:11px;color:#64748b;display:block">Session</label>
      <select name="session_filter" class="form-control form-control-sm">
        <option value="" {{ !$sessionFilter ? 'selected' : '' }}>All Sessions</option>
        @foreach($sessions as $s)
          <option value="{{ $s }}" {{ $sessionFilter === $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3 col-12 mb-2 d-flex align-items-end gap-1">
      <button class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('fees.invoices') }}" class="btn btn-light btn-sm">Reset</a>
    </div>
  </form>
</div></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoices ({{ $invoices->total() }})</h6>
    <a href="{{ route('fees.structures') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-diagram-3 mr-1"></i>Fee Structures</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px;">
      <thead class="thead-light">
        <tr>
          <th>Invoice</th>
          <th>Student</th>
          <th class="d-none d-md-table-cell">Class</th>
          <th class="d-none d-md-table-cell">Fee</th>
          <th>Session</th>
          <th>Net</th>
          <th class="d-none d-md-table-cell">Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th class="d-none d-md-table-cell">Due</th>
          <th class="d-none d-lg-table-cell">Super Admin activity</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $inv)
        <tr>
          <td><code style="font-size:11px">{{ $inv->invoice_no }}</code></td>
          <td>{{ $inv->student->name ?? '-' }}</td>
          <td class="d-none d-md-table-cell">{{ optional($inv->fee_structure->my_class)->name ?? '-' }}</td>
          <td class="d-none d-md-table-cell">{{ optional($inv->fee_structure->category)->name ?? '-' }}</td>
          <td>{{ $inv->session }}</td>
          <td>{{ number_format($inv->net_amount,2) }}</td>
          <td class="text-success d-none d-md-table-cell">{{ number_format($inv->amount_paid,2) }}</td>
          <td class="{{ $inv->balance>0?'text-danger':'text-success' }}">{{ number_format($inv->balance,2) }}</td>
          <td>
            @if($inv->status==='paid')<span class="badge badge-success">Paid</span>
            @else<span class="badge badge-danger">Unpaid</span>@endif
          </td>
          <td class="d-none d-md-table-cell">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : '-' }}</td>
          <td class="d-none d-lg-table-cell" style="font-size:11px;max-width:220px;">
            @include('pages.finance.fees._invoice_admin_activities', ['inv' => $inv, 'compact' => true])
          </td>
          <td class="text-nowrap">
            <a href="{{ route('fees.invoice', Qs::hash($inv->id)) }}" class="btn btn-info btn-xs" title="View"><i class="bi bi-eye"></i></a>
            @if($canRecordFeePayments && $inv->balance > 0)
            <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#payInv{{ $inv->id }}" title="Record cash payment">
              <i class="bi bi-cash"></i>
            </button>
            @endif
            @if($canEditInvoices && $inv->status !== 'paid' && $inv->amount_paid <= 0 && !$inv->admin_updated_at)
            <button type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editInv{{ $inv->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="12" class="text-center text-muted py-4">
            No invoices yet. Use <strong>Generate Invoices</strong> above to create invoices for students in a class.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted">
        @if($invoices->total() > 0)Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }}@endif
      </small>
      {{ $invoices->withQueryString()->links() }}
    </div>
  </div>
</div>

@if($canEditInvoices)
@foreach($invoices as $inv)
@if($inv->status !== 'paid' && $inv->amount_paid <= 0 && !$inv->admin_updated_at)
<div class="modal fade" id="editInv{{ $inv->id }}" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Edit Invoice {{ $inv->invoice_no }}</h6><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <form action="{{ route('fees.invoice.update', Qs::hash($inv->id)) }}" method="POST">
      @csrf @method('PUT')
      <div class="modal-body">
        <p class="text-muted small">Student: <strong>{{ $inv->student->name ?? '-' }}</strong></p>
        <div class="form-group">
          <label>Amount (ETB) *</label>
          <input type="number" name="original_amount" class="form-control" step="0.01" min="0" value="{{ $inv->original_amount }}" required>
        </div>
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" name="due_date" class="form-control" value="{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('Y-m-d') : '' }}">
        </div>
        <div class="form-group mb-0">
          <label>Reason for change *</label>
          <textarea name="update_note" class="form-control" rows="2" required placeholder="Accountants will see this note with date and time"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
      </div>
    </form>
  </div></div>
</div>
@endif
@endforeach
@endif

@if($canRecordFeePayments)
@foreach($invoices as $inv)
@if($inv->balance > 0)
<div class="modal fade" id="payInv{{ $inv->id }}" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-success text-white">
      <h6 class="modal-title"><i class="bi bi-cash mr-2"></i>Record Cash Payment</h6>
      <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
    </div>
    <form action="{{ route('fees.pay', Qs::hash($inv->id)) }}" method="POST">
      @csrf
      <div class="modal-body">
        <p class="text-muted small mb-2">
          <strong>{{ $inv->student->name ?? '-' }}</strong> &mdash; <code>{{ $inv->invoice_no }}</code>
        </p>
        <div class="alert alert-light border text-center py-2 mb-3">
          <small class="text-muted d-block">Full cash payment</small>
          <strong class="text-success" style="font-size:20px;">ETB {{ number_format($inv->balance, 2) }}</strong>
        </div>
        <span class="badge badge-success mb-3"><i class="bi bi-cash mr-1"></i>Cash</span>
        <div class="form-group">
          <label>Transaction Ref <small class="text-muted">(optional)</small></label>
          <input type="text" name="transaction_ref" class="form-control form-control-sm" placeholder="Receipt / voucher no.">
        </div>
        <div class="form-group mb-0">
          <label>Notes <small class="text-muted">(optional)</small></label>
          <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle mr-1"></i>Record Cash Payment</button>
      </div>
    </form>
  </div></div>
</div>
@endif
@endforeach
@endif
@endsection
