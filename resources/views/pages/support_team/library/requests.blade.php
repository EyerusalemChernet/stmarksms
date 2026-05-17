@extends('layouts.master')
@section('page_title', 'Borrow Requests')
@section('content')

{{-- ── Header ───────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Borrow Requests</h5>
        <p style="font-size:13px;color:#64748b;margin:0;">Manage book borrowing requests and returns.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('library.history') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-clock-history"></i> History
        </a>
        <a href="{{ route('library.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Library
        </a>
    </div>
</div>

{{-- ── Status Tabs ──────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;">
    @php
    $tabs = [
        'pending'  => ['label'=>'Pending',  'color'=>'#f59e0b','bg'=>'#fef3c7'],
        'approved' => ['label'=>'Approved', 'color'=>'#10b981','bg'=>'#d1fae5'],
        'returned' => ['label'=>'Returned', 'color'=>'#64748b','bg'=>'#f1f5f9'],
        'rejected' => ['label'=>'Rejected', 'color'=>'#ef4444','bg'=>'#fee2e2'],
        'all'      => ['label'=>'All',      'color'=>'#4f46e5','bg'=>'#ede9fe'],
    ];
    @endphp
    @foreach($tabs as $key => $tab)
    <a href="{{ route('library.requests', ['status'=>$key,'search'=>$search]) }}"
       style="background:{{ $status === $key ? $tab['bg'] : '#fff' }};border:1px solid {{ $status === $key ? $tab['color'] : '#e2e8f0' }};color:{{ $status === $key ? $tab['color'] : '#64748b' }};border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
        {{ $tab['label'] }}
        <span style="background:{{ $tab['bg'] }};color:{{ $tab['color'] }};font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;">
            {{ $key === 'all' ? array_sum($statusCounts) : ($statusCounts[$key] ?? 0) }}
        </span>
    </a>
    @endforeach
    @if($overdueCount > 0)
    <span style="background:#fee2e2;border:1px solid #fecaca;color:#ef4444;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        {{ $overdueCount }} Overdue
    </span>
    @endif
</div>

{{-- ── Search ───────────────────────────────────────────────────────────── --}}
<form action="{{ route('library.requests') }}" method="GET" style="margin-bottom:16px;display:flex;gap:8px;">
    <input type="hidden" name="status" value="{{ $status }}">
    <div style="position:relative;flex:1;max-width:360px;">
        <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
        <input type="text" name="search" value="{{ $search }}" placeholder="Search book or borrower…"
               style="width:100%;padding:8px 12px 8px 32px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
    </div>
    <button type="submit" style="background:#4f46e5;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;">Search</button>
    @if($search)
    <a href="{{ route('library.requests', ['status'=>$status]) }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:8px 12px;font-size:13px;text-decoration:none;">Clear</a>
    @endif
</form>

{{-- ── Requests Table ───────────────────────────────────────────────────── --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
    @if($requests->isEmpty())
    <div style="padding:60px 20px;text-align:center;">
        <i class="bi bi-inbox" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
        <p style="color:#94a3b8;font-size:13px;margin:0;">No {{ $status !== 'all' ? $status : '' }} requests found.</p>
    </div>
    @else
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">#</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Book</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Borrower</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Requested</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Due Date</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Status</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $i => $r)
            @php $isOverdue = $r->is_overdue; @endphp
            <tr style="border-bottom:1px solid #f1f5f9;{{ $isOverdue ? 'background:#fff5f5;' : '' }}"
                onmouseover="this.style.background='{{ $isOverdue ? '#fee2e2' : '#f8fafc' }}'"
                onmouseout="this.style.background='{{ $isOverdue ? '#fff5f5' : '' }}'">
                <td style="padding:12px 16px;color:#94a3b8;">{{ $requests->firstItem() + $i }}</td>
                <td style="padding:12px 16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="{{ $r->book->cover_url ?? '' }}" alt=""
                             style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;"
                             onerror="this.style.display='none'">
                        <div>
                            <div style="font-weight:600;color:#1e293b;">{{ $r->book->name ?? '—' }}</div>
                            @if($r->book && $r->book->author)
                            <div style="font-size:11px;color:#94a3b8;">{{ $r->book->author }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="padding:12px 16px;">
                    <div style="font-weight:500;color:#1e293b;">{{ $r->user->name ?? '—' }}</div>
                    <div style="font-size:11px;color:#94a3b8;">{{ ucwords(str_replace('_',' ',$r->user->user_type ?? '')) }}</div>
                </td>
                <td style="padding:12px 16px;color:#64748b;font-size:12px;">
                    {{ $r->created_at->format('d M Y') }}
                </td>
                <td style="padding:12px 16px;">
                    @if($r->due_date)
                        <span style="font-size:12px;font-weight:600;color:{{ $isOverdue ? '#ef4444' : '#10b981' }};">
                            {{ $r->due_date->format('d M Y') }}
                        </span>
                        @if($isOverdue)
                        <div style="font-size:10px;color:#ef4444;font-weight:600;">{{ $r->days_overdue }}d overdue</div>
                        @endif
                    @else
                        <span style="color:#94a3b8;font-size:12px;">—</span>
                    @endif
                </td>
                <td style="padding:12px 16px;">
                    @php
                    $badgeColors = [
                        'pending'  => ['bg'=>'#fef3c7','color'=>'#92400e'],
                        'approved' => ['bg'=>'#d1fae5','color'=>'#065f46'],
                        'returned' => ['bg'=>'#f1f5f9','color'=>'#475569'],
                        'rejected' => ['bg'=>'#fee2e2','color'=>'#991b1b'],
                    ];
                    $bc = $isOverdue ? ['bg'=>'#fee2e2','color'=>'#991b1b'] : ($badgeColors[$r->status] ?? ['bg'=>'#f1f5f9','color'=>'#64748b']);
                    @endphp
                    <span style="background:{{ $bc['bg'] }};color:{{ $bc['color'] }};font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">
                        {{ $r->statusLabel() }}
                    </span>
                    @if($r->overdue_fine > 0)
                    <div style="font-size:10px;color:#ef4444;margin-top:3px;">Fine: {{ $r->overdue_fine }} ETB</div>
                    @endif
                </td>
                <td style="padding:12px 16px;">
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        @if($r->status === 'pending')
                        <form method="POST" action="{{ route('library.approve', $r->id) }}" class="d-inline">
                            @csrf @method('PUT')
                            <button style="background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;border-radius:7px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;">
                                <i class="bi bi-check-lg mr-1"></i>Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('library.reject', $r->id) }}" class="d-inline">
                            @csrf @method('PUT')
                            <button onclick="return confirm('Reject this request?')"
                                    style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;border-radius:7px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;">
                                <i class="bi bi-x-lg mr-1"></i>Reject
                            </button>
                        </form>
                        @elseif($r->status === 'approved')
                        <form method="POST" action="{{ route('library.return', $r->id) }}" class="d-inline">
                            @csrf @method('PUT')
                            <button onclick="return confirm('Mark this book as returned?')"
                                    style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;border-radius:7px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;">
                                <i class="bi bi-arrow-return-left mr-1"></i>Return
                            </button>
                        </form>
                        @else
                        <span style="color:#94a3b8;font-size:12px;">—</span>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9;">{{ $requests->links() }}</div>
    @endif
</div>

@endsection
