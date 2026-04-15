@extends('layouts.master')
@section('page_title', 'Audit Logs')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-journal-text mr-2"></i>Audit Logs</h5>
    <a href="{{ route('settings') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to Settings</a>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">System Activity Log</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:160px;">Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="small text-muted">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>
                        @php
                            $cls = match($log->action) {
                                'created'  => 'success',
                                'updated'  => 'primary',
                                'deleted'  => 'danger',
                                'payment'  => 'warning',
                                'approved' => 'info',
                                'returned' => 'secondary',
                                default    => 'secondary',
                            };
                        @endphp
                        <span class="badge badge-{{ $cls }}">{{ ucfirst($log->action) }}</span>
                    </td>
                    <td><span class="badge badge-light text-dark">{{ ucfirst($log->module) }}</span></td>
                    <td class="small">{{ $log->description }}</td>
                    <td class="small text-muted">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No audit logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
