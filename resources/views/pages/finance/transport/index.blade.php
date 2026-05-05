@extends('layouts.master')
@section('page_title', 'Transport Fees')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-bus-front mr-2"></i>Transport Routes — {{ $year }}</h5>
        <a href="{{ route('finance.transport.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Add Route</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th>#</th><th>Route Name</th><th>Area</th><th>Fee (ETB)</th><th>Students</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($routes as $i => $route)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $route->name }}</td>
                    <td>{{ $route->area }}</td>
                    <td>{{ number_format($route->fee, 2) }}</td>
                    <td>{{ $route->records->count() }}</td>
                    <td>
                        <a href="{{ route('finance.transport.records', $route->id) }}" class="btn btn-info btn-xs"><i class="bi bi-people"></i></a>
                        <a href="{{ route('finance.transport.edit', $route->id) }}" class="btn btn-warning btn-xs"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('finance.transport.destroy', $route->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this route?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No transport routes for this session.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
