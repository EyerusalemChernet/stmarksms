@extends('layouts.master')
@section('page_title', 'Announcements')
@section('content')

@if(Qs::userIsTeamSA())
<div class="card mb-3">
    <div class="card-header bg-white"><h6 class="card-title">Post Announcement</h6></div>
    <div class="card-body">
        <form method="POST" action="{{ route('announcements.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Audience</label>
                        <select name="audience" class="select form-control" data-fouc data-placeholder="Choose..">
                            <option value="all">Everyone</option>
                            <option value="students">Students</option>
                            <option value="teachers">Teachers</option>
                            <option value="parents">Parents</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="body" required rows="3" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Post</button>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header bg-white"><h6 class="card-title">Announcements</h6></div>
    <div class="card-body">
        @forelse($announcements as $a)
        <div class="card mb-2 border-left border-primary">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between">
                    <strong>{{ $a->title }}</strong>
                    <small class="text-muted">{{ $a->created_at->diffForHumans() }} &bull; {{ ucfirst($a->audience) }}</small>
                </div>
                <p class="mb-1 mt-1">{{ $a->body }}</p>
                <small class="text-muted">By {{ $a->author->name ?? 'System' }}</small>
                @if(Qs::userIsTeamSA())
                <form method="POST" action="{{ route('announcements.delete', $a->id) }}" class="d-inline float-right">
                    @csrf @method('DELETE')
                    <button class="btn btn-xs btn-danger">Delete</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <p class="text-muted">No announcements yet.</p>
        @endforelse
        {{ $announcements->links() }}
    </div>
</div>
@endsection
