@extends('layouts.master')
@section('page_title', 'HR Audit Log')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-journal-text mr-2"></i>HR Module Audit Log</h5>
    <div>
        <a href="{{ route('audit.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>All Logs</a>
        <a href="{{ route('audit.hr.export', request()->query()) }}" class="btn btn-sm btn-success"><i class="bi bi-download mr-1"></i>Export CSV</a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-3">Filter Audit Logs</h6>
        <form method="GET" action="{{ route('audit.hr') }}" class="form-inline">
            <div class="form-group mr-2 mb-2">
                <label for="module" class="mr-2">Module:</label>
                <select name="module" id="module" class="form-control form-control-sm">
                    <option value="">All Modules</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>
                            {{ ucfirst($mod) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mr-2 mb-2">
                <label for="action" class="mr-2">Action:</label>
                <select name="action" id="action" class="form-control form-control-sm">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>
                            {{ ucfirst($act) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mr-2 mb-2">
                <label for="date_from" class="mr-2">From:</label>
                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>

            <div class="form-group mr-2 mb-2">
                <label for="date_to" class="mr-2">To:</label>
                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>

            <div class="form-group mr-2 mb-2">
                <label for="search" class="mr-2">Search:</label>
                <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Search description..." value="{{ request('search') }}">
            </div>

            <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search mr-1"></i>Filter</button>
            <a href="{{ route('audit.hr') }}" class="btn btn-sm btn-secondary mb-2 ml-2"><i class="bi bi-arrow-clockwise mr-1"></i>Reset</a>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:160px;">Time</th>
                        <th style="width:120px;">User</th>
                        <th style="width:100px;">Action</th>
                        <th style="width:100px;">Module</th>
                        <th>Description</th>
                        <th style="width:120px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="small text-muted">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="small">
                            @if($log->user)
                                <span class="badge badge-info">{{ $log->user->name }}</span>
                            @else
                                <span class="badge badge-secondary">System</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $actionClass = match($log->action) {
                                    'created'  => 'success',
                                    'updated'  => 'primary',
                                    'deleted'  => 'danger',
                                    'approved' => 'info',
                                    'rejected' => 'warning',
                                    'generated' => 'success',
                                    'paid' => 'success',
                                    'reverted' => 'warning',
                                    'terminated' => 'danger',
                                    'reactivated' => 'success',
                                    'enrolled' => 'info',
                                    'completed' => 'success',
                                    'applied' => 'info',
                                    'hired' => 'success',
                                    'rejected' => 'danger',
                                    default    => 'secondary',
                                };
                            @endphp
                            <span class="badge badge-{{ $actionClass }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td>
                            @php
                                $moduleClass = match($log->module) {
                                    'payroll' => 'danger',
                                    'employee' => 'primary',
                                    'leave' => 'warning',
                                    'recruitment' => 'info',
                                    'performance' => 'success',
                                    'training' => 'secondary',
                                    'attendance' => 'dark',
                                    'contract' => 'info',
                                    default => 'light',
                                };
                            @endphp
                            <span class="badge badge-{{ $moduleClass }} text-{{ $moduleClass == 'light' ? 'dark' : 'white' }}">
                                {{ ucfirst($log->module) }}
                            </span>
                        </td>
                        <td class="small">
                            <span title="{{ $log->description }}">
                                {{ Str::limit($log->description, 80) }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox"></i> No audit logs found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->appends(request()->query())->links() }}
    </div>
    @endif
</div>

<!-- Statistics Section -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Total Logs</h6>
                <h3 class="text-primary">{{ $logs->total() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Payroll Actions</h6>
                <h3 class="text-danger">
                    {{ $logs->getCollection()->where('module', 'payroll')->count() }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Employee Actions</h6>
                <h3 class="text-primary">
                    {{ $logs->getCollection()->where('module', 'employee')->count() }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Other HR Actions</h6>
                <h3 class="text-info">
                    {{ $logs->getCollection()->whereNotIn('module', ['payroll', 'employee'])->count() }}
                </h3>
            </div>
        </div>
    </div>
</div>

@endsection
