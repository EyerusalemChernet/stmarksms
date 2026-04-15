@extends('layouts.master')
@section('page_title', 'Inbox')
@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Inbox</h6>
        <a href="{{ route('compose') }}" class="btn btn-sm btn-primary">Compose</a>
    </div>
    <div class="card-body p-0">
        @if(session('flash_success'))<div class="alert alert-success m-3">{{ session('flash_success') }}</div>@endif
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr><th>From</th><th>Subject</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($messages as $m)
                <tr class="{{ !$m->read ? 'font-weight-bold' : '' }}">
                    <td>{{ $m->sender->name ?? '-' }}</td>
                    <td>{{ $m->subject ?: '(no subject)' }}</td>
                    <td>{{ $m->created_at->format('d M Y H:i') }}</td>
                    <td><a href="{{ route('messages.read', $m->id) }}" class="btn btn-xs btn-info">Read</a></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">No messages.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $messages->links() }}</div>
    </div>
</div>
@endsection
