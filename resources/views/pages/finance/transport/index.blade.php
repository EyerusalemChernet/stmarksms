@extends('layouts.master')
@section('page_title', 'Transport Management')
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-truck mr-2"></i>Add Transport Route</h6></div>
            <div class="card-body">
                <form action="{{ route('transport.store') }}" method="POST">@csrf
                    <div class="form-group"><label>Route Name *</label><input type="text" name="route_name" class="form-control" required placeholder="e.g. Route A - Main City"></div>
                    <div class="form-group"><label>Vehicle No</label><input type="text" name="vehicle_no" class="form-control" placeholder="e.g. AA-12345"></div>
                    <div class="form-group"><label>Driver Name</label><input type="text" name="driver_name" class="form-control"></div>
                    <div class="form-group"><label>Driver Phone</label><input type="text" name="driver_phone" class="form-control"></div>
                    <div class="form-group"><label>Monthly Fee *</label><input type="number" name="fee" class="form-control" required step="0.01"></div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Route</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul mr-2"></i>Transport Routes</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light"><tr><th>Route</th><th>Vehicle</th><th>Driver</th><th>Fee</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($transports as $t)
                        <tr>
                            <td><strong>{{ $t->route_name }}</strong></td>
                            <td>{{ $t->vehicle_no }}</td>
                            <td>{{ $t->driver_name }}<br><small>{{ $t->driver_phone }}</small></td>
                            <td>{{ number_format($t->fee, 2) }}</td>
                            <td>@if($t->active)<span class="badge badge-success">Active</span>@else<span class="badge badge-secondary">Inactive</span>@endif</td>
                            <td>
                                <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editT{{ $t->id }}"><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('transport.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button></form>
                            </td>
                        </tr>
                        <div class="modal fade" id="editT{{ $t->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                            <div class="modal-header"><h6 class="modal-title">Edit: {{ $t->route_name }}</h6><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                            <form action="{{ route('transport.update', $t->id) }}" method="POST">@csrf @method('PUT')
                                <div class="modal-body">
                                    <div class="form-group"><label>Route Name</label><input type="text" name="route_name" class="form-control" value="{{ $t->route_name }}" required></div>
                                    <div class="form-group"><label>Vehicle No</label><input type="text" name="vehicle_no" class="form-control" value="{{ $t->vehicle_no }}"></div>
                                    <div class="form-group"><label>Driver Name</label><input type="text" name="driver_name" class="form-control" value="{{ $t->driver_name }}"></div>
                                    <div class="form-group"><label>Driver Phone</label><input type="text" name="driver_phone" class="form-control" value="{{ $t->driver_phone }}"></div>
                                    <div class="form-group"><label>Monthly Fee</label><input type="number" name="fee" class="form-control" value="{{ $t->fee }}" required step="0.01"></div>
                                    <div class="form-group"><label>Status</label><select name="active" class="form-control"><option value="1" {{ $t->active?'selected':'' }}>Active</option><option value="0" {{ !$t->active?'selected':'' }}>Inactive</option></select></div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-primary btn-sm">Update</button></div>
                            </form>
                        </div></div></div>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No transport routes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
