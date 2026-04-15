@extends('layouts.master')
@section('page_title', 'Borrowing History')
@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Borrowing History</h6>
        <a href="{{ route('library.requests') }}" class="btn btn-sm btn-primary">Active Requests</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr><th>#</th><th>Book</th><th>Borrower</th><th>Issued</th><th>Returned</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($history as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->book->name ?? '-' }}</td>
                    <td>{{ $r->user->name ?? '-' }}</td>
                    <td>{{ $r->issued_at ? \Carbon\Carbon::parse($r->issued_at)->format('d M Y') : '-' }}</td>
                    <td>{{ $r->returned_at ? \Carbon\Carbon::parse($r->returned_at)->format('d M Y') : '-' }}</td>
                    <td><span class="badge badge-{{ $r->status === 'returned' ? 'secondary' : 'success' }}">{{ ucfirst($r->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $history->links() }}</div>
    </div>
</div>
@endsection
