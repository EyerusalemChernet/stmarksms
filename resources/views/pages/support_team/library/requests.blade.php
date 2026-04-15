@extends('layouts.master')
@section('page_title', 'Borrow Requests')
@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Book Borrow Requests</h6>
        <a href="{{ route('library.history') }}" class="btn btn-sm btn-secondary">Borrowing History</a>
    </div>
    <div class="card-body p-0">
        @if(session('flash_success'))<div class="alert alert-success m-3">{{ session('flash_success') }}</div>@endif
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr><th>#</th><th>Book</th><th>Requested By</th><th>Status</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach($requests as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->book->name ?? '-' }}</td>
                    <td>{{ $r->user->name ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $r->status === 'approved' ? 'success' : ($r->status === 'rejected' ? 'danger' : ($r->status === 'returned' ? 'secondary' : 'warning')) }}">
                            {{ ucfirst($r->status ?? 'pending') }}
                        </span>
                    </td>
                    <td>{{ $r->created_at->format('d M Y') }}</td>
                    <td>
                        @if($r->status === 'pending')
                        <form method="POST" action="{{ route('library.approve', $r->id) }}" class="d-inline">
                            @csrf @method('PUT')
                            <button class="btn btn-xs btn-success">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('library.reject', $r->id) }}" class="d-inline">
                            @csrf @method('PUT')
                            <button class="btn btn-xs btn-danger">Reject</button>
                        </form>
                        @elseif($r->status === 'approved')
                        <form method="POST" action="{{ route('library.return', $r->id) }}" class="d-inline">
                            @csrf @method('PUT')
                            <button class="btn btn-xs btn-warning">Mark Returned</button>
                        </form>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
