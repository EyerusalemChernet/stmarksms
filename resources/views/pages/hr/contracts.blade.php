@extends('layouts.master')
@section('page_title', 'Contract Management')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-file-earmark-text mr-2"></i>Contract Management</h5>
    <div style="gap:6px;" class="d-flex">
        <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i>Dashboard
        </a>
        <a href="{{ route('hr.contracts', array_merge(request()->query(), ['export'=>'pdf'])) }}"
           class="btn btn-sm btn-danger"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
        <a href="{{ route('hr.contracts', array_merge(request()->query(), ['export'=>'csv'])) }}"
           class="btn btn-sm btn-success"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
    </div>
</div>

{{-- Summary cards --}}
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.contracts', ['filter'=>'expired']) }}" class="text-decoration-none">
            <div class="card text-center p-3 border-danger">
                <h3 class="text-danger mb-0">{{ $expiredCount }}</h3>
                <small class="text-muted">Expired</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.contracts', ['filter'=>'expiring','days'=>60]) }}" class="text-decoration-none">
            <div class="card text-center p-3 border-warning">
                <h3 class="text-warning mb-0">{{ $expiringCount }}</h3>
                <small class="text-muted">Expiring (60 days)</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.contracts', ['filter'=>'permanent']) }}" class="text-decoration-none">
            <div class="card text-center p-3">
                <h3 class="text-success mb-0">{{ $permanentCount }}</h3>
                <small class="text-muted">Permanent</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('hr.contracts', ['filter'=>'all']) }}" class="text-decoration-none">
            <div class="card text-center p-3">
                <h3 class="text-primary mb-0">{{ $expiredCount + $expiringCount }}</h3>
                <small class="text-muted">Need Attention</small>
            </div>
        </a>
    </div>
</div>

{{-- Filter bar --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form action="{{ route('hr.contracts') }}" method="GET" class="form-inline mb-0" style="gap:8px;">
            <label class="font-weight-bold">Show:</label>
            <select name="filter" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="expiring" {{ $filter === 'expiring' ? 'selected' : '' }}>Expiring Soon</option>
                <option value="expired"  {{ $filter === 'expired'  ? 'selected' : '' }}>Expired</option>
                <option value="permanent"{{ $filter === 'permanent'? 'selected' : '' }}>Permanent</option>
                <option value="all"      {{ $filter === 'all'      ? 'selected' : '' }}>All Contracts</option>
            </select>
            @if($filter === 'expiring')
            <label class="font-weight-bold ml-2">Within:</label>
            <select name="days" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="30"  {{ $days == 30  ? 'selected' : '' }}>30 days</option>
                <option value="60"  {{ $days == 60  ? 'selected' : '' }}>60 days</option>
                <option value="90"  {{ $days == 90  ? 'selected' : '' }}>90 days</option>
            </select>
            @endif
        </form>
    </div>
</div>

{{-- Contracts table --}}
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            @if($filter === 'expiring') Contracts Expiring Within {{ $days }} Days
            @elseif($filter === 'expired') Expired Contracts
            @elseif($filter === 'permanent') Permanent Employees
            @else All Contracts
            @endif
            <span class="badge badge-secondary ml-1">{{ $contracts->count() }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>Hire Date</th>
                    <th>Contract End</th>
                    <th class="text-center">Days Left</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contracts as $ed)
                @php $emp = $ed->employee; @endphp
                @if(!$emp) @continue @endif
                <tr class="{{ $ed->isContractExpired() ? 'table-danger' : ($ed->isContractExpiringSoon(30) ? 'table-warning' : '') }}"
                    style="--bs-table-bg:transparent;">
                    <td>
                        <div class="d-flex align-items-center" style="gap:6px;">
                            <img src="{{ $emp->photo_url }}" width="28" height="28"
                                 class="rounded-circle" style="object-fit:cover;">
                            <div>
                                <a href="{{ route('hr.show', $emp->id) }}" class="font-weight-bold">
                                    {{ $emp->full_name }}
                                </a>
                                <br><small class="text-muted">{{ $emp->employee_code }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $ed->department?->name ?? '—' }}</td>
                    <td><span class="badge badge-light border">{{ $ed->employmentTypeLabel() }}</span></td>
                    <td class="text-muted small">{{ $ed->hire_date?->format('d M Y') ?? '—' }}</td>
                    <td class="font-weight-bold">
                        {{ $ed->contract_end_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="text-center">
                        @if($ed->contract_end_date)
                            @php $daysLeft = $ed->daysUntilExpiry(); @endphp
                            <span class="font-weight-bold {{ $daysLeft < 0 ? 'text-danger' : ($daysLeft <= 30 ? 'text-warning' : 'text-success') }}">
                                {{ $daysLeft < 0 ? abs($daysLeft).' ago' : $daysLeft.'d' }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $ed->contractStatusBadgeClass() }}">
                            {{ $ed->contractStatusLabel() }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('hr.show', $emp->id) }}"
                           class="btn btn-xs btn-primary" title="View Profile">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if($ed->contract_end_date)
                        <button type="button" class="btn btn-xs btn-success renew-btn"
                                data-id="{{ $emp->id }}"
                                data-name="{{ $emp->full_name }}"
                                data-current="{{ $ed->contract_end_date->format('Y-m-d') }}"
                                title="Renew Contract">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        @if($filter === 'expiring') No contracts expiring within {{ $days }} days.
                        @elseif($filter === 'expired') No expired contracts.
                        @else No contracts found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Renew Contract Modal --}}
<div class="modal fade" id="renewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="renew-form" method="POST" onsubmit="return confirmRenewal(event)">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-repeat mr-1"></i>Renew Contract</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Renewing contract for: <strong id="renew-name"></strong></p>
                    <div class="form-group">
                        <label class="font-weight-bold">Current End Date</label>
                        <div class="input-group">
                            <input type="text" id="renew-current" class="form-control bg-light" readonly>
                            <div class="input-group-append">
                                <span class="input-group-text small text-muted" id="renew-current-readable"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">New Contract End Date <span class="text-danger">*</span></label>
                        <input type="date" name="contract_end_date" id="new-contract-date" class="form-control" required>
                        <small class="text-muted">Must be a future date (max 10 years from now).</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Notes</label>
                        <input type="text" name="notes" class="form-control"
                               placeholder="e.g. Renewed for academic year 2025/26">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle mr-1"></i>Renew Contract
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Format date from ISO format to readable format
function formatDate(dateStr) {
    var date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Open renew modal and populate fields
$(document).on('click', '.renew-btn', function() {
    var id      = $(this).data('id');
    var name    = $(this).data('name');
    var current = $(this).data('current');
    
    $('#renew-form').attr('action', '/hr/employees/' + id + '/renew-contract');
    $('#renew-name').text(name);
    $('#renew-current').val(current);
    $('#renew-current-readable').text(formatDate(current));
    $('#new-contract-date').val('');
    $('#renewModal').modal('show');
});

// Confirmation dialog before renewal
function confirmRenewal(event) {
    var name = $('#renew-name').text();
    var newDate = $('#new-contract-date').val();
    var readableDate = formatDate(newDate);
    
    if (!newDate) {
        alert('Please select a new contract end date.');
        return false;
    }
    
    return confirm('Renew contract for ' + name + ' until ' + readableDate + '?');
}
</script>
@endsection
