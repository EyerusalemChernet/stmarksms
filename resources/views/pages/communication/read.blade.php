@extends('layouts.master')
@section('page_title', 'Read Message')
@section('breadcrumb')
    <a href="{{ route('inbox') }}" style="color:#4f46e5;text-decoration:none;">Inbox</a>
    <span style="margin:0 6px;color:#cbd5e1;">/</span>
    <span style="color:#64748b;">Message</span>
@endsection
@section('content')

<div class="card" style="max-width:700px;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            <i class="bi bi-envelope-open mr-2 text-primary"></i>
            {{ $message->subject ?: '(no subject)' }}
        </h6>
        <a href="{{ route('inbox') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i> Back to Inbox
        </a>
    </div>
    <div class="card-body">
        {{-- Message meta --}}
        <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;margin-bottom:16px;border:1px solid #e2e8f0;">
            <div class="row" style="font-size:13px;">
                <div class="col-sm-6 mb-1">
                    <span class="text-muted">From:</span>
                    <strong class="ml-1">{{ $message->sender->name ?? 'Unknown' }}</strong>
                    <span class="badge badge-secondary ml-1" style="font-size:10px;">
                        {{ ucwords(str_replace('_', ' ', $message->sender->user_type ?? '')) }}
                    </span>
                </div>
                <div class="col-sm-6 mb-1">
                    <span class="text-muted">To:</span>
                    <strong class="ml-1">{{ $message->receiver->name ?? 'Unknown' }}</strong>
                </div>
                <div class="col-sm-6 mb-1">
                    <span class="text-muted">Subject:</span>
                    <span class="ml-1">{{ $message->subject ?: '(no subject)' }}</span>
                </div>
                <div class="col-sm-6 mb-1">
                    <span class="text-muted">Date:</span>
                    <span class="ml-1">{{ $message->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div style="font-size:14px;line-height:1.8;color:#1e293b;white-space:pre-wrap;padding:4px 0;">{{ $message->body }}</div>

        <hr style="border-color:#e2e8f0;margin:20px 0;">

        <a href="{{ route('compose') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-reply mr-1"></i> Reply
        </a>
        <a href="{{ route('inbox') }}" class="btn btn-secondary btn-sm ml-2">
            <i class="bi bi-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>
@endsection
