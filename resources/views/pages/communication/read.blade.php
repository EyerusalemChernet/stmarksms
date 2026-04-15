@extends('layouts.master')
@section('page_title', 'Read Message')
@section('breadcrumb')
    <a href="{{ route('inbox') }}" style="color:#4f46e5;text-decoration:none;">Inbox</a>
    <span style="margin:0 6px;color:#cbd5e1;">/</span>
    <span style="color:#64748b;">Message</span>
@endsection
@section('content')
@include('partials.back_button')

<div class="card" style="max-width:680px;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            <i class="bi bi-envelope-open mr-2 text-primary"></i>
            {{ $message->subject ?: '(no subject)' }}
        </h6>
        <a href="{{ route('inbox') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left mr-1"></i> Inbox
        </a>
    </div>
    <div class="card-body">
        <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;margin-bottom:16px;border:1px solid #e2e8f0;">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>From:</strong>
                    <span class="ml-1">{{ $message->sender->name ?? 'Unknown' }}</span>
                    <span class="badge badge-secondary ml-2">{{ ucwords(str_replace('_',' ', $message->sender->user_type ?? '')) }}</span>
                </div>
                <small class="text-muted">{{ $message->created_at->format('d M Y, H:i') }}</small>
            </div>
        </div>

        <div style="font-size:14px;line-height:1.7;color:#1e293b;white-space:pre-wrap;">{{ $message->body }}</div>

        <hr style="border-color:#e2e8f0;margin:20px 0;">

        <a href="{{ route('compose') }}?reply={{ $message->sender_id }}" class="btn btn-primary btn-sm">
            <i class="bi bi-reply mr-1"></i> Reply
        </a>
    </div>
</div>
@endsection
