@extends('layouts.master')
@section('page_title', 'Inbox')
@section('content')

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0"><i class="bi bi-inbox mr-2 text-primary"></i>Inbox</h6>
        <a href="{{ route('compose') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil-square mr-1"></i>Compose
        </a>
    </div>

    <div class="card-body p-0">
        @if(session('flash_success'))
            <div class="alert alert-success m-3 mb-0">{{ session('flash_success') }}</div>
        @endif
        @if(session('flash_danger'))
            <div class="alert alert-danger m-3 mb-0">{{ session('flash_danger') }}</div>
        @endif

        @if($messages->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size:40px;opacity:.3;"></i>
                <p class="mt-2 mb-0">Your inbox is empty.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead class="thead-light">
                    <tr>
                        <th style="width:30px;"></th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th style="width:180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $m)
                    <tr class="{{ !$m->read ? 'font-weight-bold' : '' }}" style="{{ !$m->read ? 'background:#f0f4ff;' : '' }}">
                        {{-- Unread dot --}}
                        <td class="text-center">
                            @if(!$m->read)
                                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#4f46e5;"></span>
                            @endif
                        </td>
                        <td>{{ $m->sender->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('messages.read', $m) }}" style="color:inherit;text-decoration:none;">
                                {{ $m->subject ?: '(no subject)' }}
                            </a>
                        </td>
                        <td class="text-muted" style="font-weight:400;">{{ $m->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            <div class="d-flex" style="gap:4px;flex-wrap:wrap;">
                                {{-- Read / Unread toggle --}}
                                @if($m->read)
                                <form method="POST" action="{{ route('messages.mark_unread', $m) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-xs btn-outline-secondary" title="Mark as unread">
                                        <i class="bi bi-envelope"></i>
                                    </button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('messages.mark_read', $m) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-xs btn-outline-primary" title="Mark as read">
                                        <i class="bi bi-envelope-open"></i>
                                    </button>
                                </form>
                                @endif

                                {{-- Archive --}}
                                <form method="POST" action="{{ route('messages.archive', $m) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-xs btn-outline-warning" title="Archive">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </form>

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('messages.delete', $m) }}"
                                      onsubmit="return confirm('Delete this message? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $messages->links() }}</div>
        @endif
    </div>
</div>

@endsection
