@extends('layouts.master')
@section('page_title', 'Rules Engine')
@section('breadcrumb')
    <span class="breadcrumb-item active">Rules Engine</span>
@endsection
@section('content')
@include('partials.back_button')
<div class="row">
<div class="col-md-5">
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title">Add New Rule</h6></div>
    <div class="card-body">
        @if(session('flash_success'))<div class="alert alert-success">{{ session('flash_success') }}</div>@endif
        <form method="POST" action="{{ route('rules.store') }}">
            @csrf
            <div class="form-group">
                <label>Rule Name <span class="text-danger">*</span></label>
                <input type="text" name="name" required class="form-control" placeholder="e.g. Block result if attendance below 75%">
            </div>
            <div class="form-group">
                <label>Rule Type <span class="text-danger">*</span></label>
                <select name="type" required class="select form-control" data-fouc data-placeholder="Choose..">
                    <option value=""></option>
                    <option value="attendance_block">Attendance Block</option>
                    <option value="fee_block">Fee Block</option>
                    <option value="promotion_requirement">Promotion Requirement</option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition" required class="select form-control" data-fouc data-placeholder="Choose..">
                            <option value="lt">Less than (&lt;)</option>
                            <option value="lte">Less than or equal (&le;)</option>
                            <option value="gt">Greater than (&gt;)</option>
                            <option value="gte">Greater than or equal (&ge;)</option>
                            <option value="eq">Equal to (=)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Value</label>
                        <input type="number" name="value" required step="0.01" class="form-control" placeholder="e.g. 75">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Action <span class="text-danger">*</span></label>
                <select name="action" required class="select form-control" data-fouc data-placeholder="Choose..">
                    <option value=""></option>
                    <option value="block_result">Block Exam Result</option>
                    <option value="block_report">Block Report Card</option>
                    <option value="block_promotion">Block Promotion</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" class="form-control" placeholder="Optional note">
            </div>
            <button type="submit" class="btn btn-primary">Save Rule</button>
        </form>
    </div>
</div>
</div>

<div class="col-md-7">
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title">Active Rules</h6></div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr><th>Name</th><th>Type</th><th>Condition</th><th>Value</th><th>Action</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($rules as $r)
                <tr>
                    <td>{{ $r->name }}</td>
                    <td>{{ str_replace('_', ' ', $r->type) }}</td>
                    <td>{{ $r->condition }}</td>
                    <td>{{ $r->value }}</td>
                    <td>{{ str_replace('_', ' ', $r->action) }}</td>
                    <td>
                        <span class="badge badge-{{ $r->active ? 'success' : 'secondary' }}">
                            {{ $r->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('rules.update', $r->id) }}" class="d-inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="name" value="{{ $r->name }}">
                            <input type="hidden" name="type" value="{{ $r->type }}">
                            <input type="hidden" name="condition" value="{{ $r->condition }}">
                            <input type="hidden" name="value" value="{{ $r->value }}">
                            <input type="hidden" name="action" value="{{ $r->action }}">
                            <input type="hidden" name="active" value="{{ $r->active ? 0 : 1 }}">
                            <button class="btn btn-xs btn-{{ $r->active ? 'warning' : 'success' }}">
                                {{ $r->active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('rules.destroy', $r->id) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger" onclick="return confirm('Delete rule?')">Del</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">No rules configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
@endsection
