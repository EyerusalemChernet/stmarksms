@extends('layouts.master')
@section('page_title', 'Compose Message')
@section('breadcrumb')
    <a href="{{ route('inbox') }}" style="color:#4f46e5;text-decoration:none;">Inbox</a>
    <span style="margin:0 6px;color:#cbd5e1;">/</span>
    <span style="color:#64748b;">Compose</span>
@endsection
@section('content')
@include('partials.back_button')

<div class="card" style="max-width:680px;">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-pencil-square mr-2 text-primary"></i>
            New Message
            @if(isset($label))
                <small class="text-muted ml-2" style="font-weight:400;font-size:12px;">— {{ $label }}</small>
            @endif
        </h6>
    </div>
    <div class="card-body">

        @if(session('flash_success'))
            <div class="alert alert-success">{{ session('flash_success') }}</div>
        @endif

        @if($users->isEmpty())
            <div class="alert alert-info">
                <i class="bi bi-info-circle mr-2"></i>
                No recipients available. Teachers are assigned to your child's classes once subjects are set up.
            </div>
        @else
        <form method="POST" action="{{ route('messages.send') }}">
            @csrf
            <div class="form-group">
                <label>To <span class="text-danger">*</span></label>
                <select name="receiver_id" required class="select-search form-control" data-placeholder="Select recipient">
                    <option value=""></option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('reply') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                            ({{ ucwords(str_replace('_',' ', $u->user_type)) }})
                        </option>
                    @endforeach
                </select>
                @error('receiver_id')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" class="form-control" placeholder="Optional subject">
            </div>

            <div class="form-group">
                <label>Message <span class="text-danger">*</span></label>
                <textarea name="body" required rows="6" class="form-control" placeholder="Write your message here..."></textarea>
                @error('body')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            <div class="d-flex" style="gap:10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send mr-1"></i> Send Message
                </button>
                <a href="{{ route('inbox') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
