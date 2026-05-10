@extends('layouts.master')
@section('page_title', 'User ↔ Employee Linking')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-link-45deg mr-2"></i>User ↔ Employee Linking</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Dashboard
    </a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle mr-1"></i>
    The self-service portal (My Profile, My Payslips, My Leave, My Performance) requires every
    staff user to have a linked <strong>Employee record</strong>. Use this page to fix any gaps.
</div>

{{-- ── Section 1: Staff users with NO employee record ──────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            <i class="bi bi-person-x mr-1 text-danger"></i>
            Staff Users Without an Employee Record
            <span class="badge badge-danger ml-1">{{ $unlinked->count() }}</span>
        </h6>
        @if($unlinked->count() > 0)
        <form action="{{ route('hr.employees.sync_all') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-success"
                    onclick="return confirm('Auto-create Employee records for ALL {{ $unlinked->count() }} unlinked user(s)?')">
                <i class="bi bi-magic mr-1"></i>Auto-Create All
            </button>
        </form>
        @endif
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($unlinked as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <img src="{{ $user->photo }}" width="28" height="28"
                                 class="rounded-circle" style="object-fit:cover;">
                            <span class="font-weight-bold">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="text-muted small">{{ $user->email ?? '—' }}</td>
                    <td><span class="badge badge-secondary">{{ ucwords(str_replace('_',' ',$user->user_type)) }}</span></td>
                    <td>
                        <form action="{{ route('hr.employees.sync_user', $user->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-success"
                                    onclick="return confirm('Create Employee record for {{ $user->name }}?')">
                                <i class="bi bi-plus-circle mr-1"></i>Create Employee Record
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-success py-4">
                        <i class="bi bi-check-circle mr-1"></i>
                        All staff users have Employee records.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Section 2: Employee records with NO user account ────────────────────── --}}
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-person-badge mr-1 text-warning"></i>
            Employee Records Without a User Account
            <span class="badge badge-warning ml-1">{{ $employees->count() }}</span>
        </h6>
        <small class="text-muted">These employees exist in HR but cannot log in. Link them to a user account to enable self-service.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Link to User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                <tr>
                    <td><span class="badge badge-light border text-monospace">{{ $emp->employee_code }}</span></td>
                    <td class="font-weight-bold">{{ $emp->full_name }}</td>
                    <td>{{ $emp->employmentDetails?->department?->name ?? '—' }}</td>
                    <td>
                        <form action="{{ route('hr.employees.link_user', $emp->id) }}" method="POST" class="form-inline">
                            @csrf
                            <select name="user_id" class="form-control form-control-sm mr-2" style="width:200px;" required>
                                <option value="">— Select User —</option>
                                @foreach(\App\User::whereIn('user_type',['teacher','hr_manager','admin','super_admin'])->whereNotIn('id', \App\Models\Employee::whereNotNull('user_id')->pluck('user_id'))->orderBy('name')->get() as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->user_type }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-xs btn-primary">
                                <i class="bi bi-link mr-1"></i>Link
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-success py-4">
                        <i class="bi bi-check-circle mr-1"></i>
                        All employee records are linked to user accounts.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
