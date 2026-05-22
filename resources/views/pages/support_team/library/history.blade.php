@extends('layouts.master')
@section('page_title', 'Borrowing History')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Borrowing History</h5>
        <p style="font-size:13px;color:#64748b;margin:0;">Complete record of all issued and returned books.</p>
    </div>
    <a href="{{ route('library.requests') }}" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(79,70,229,.3);">
        <i class="bi bi-clock"></i> Active Requests
    </a>
</div>

{{-- Search --}}
<form action="{{ route('library.history') }}" method="GET" style="margin-bottom:16px;display:flex;gap:8px;">
    <div style="position:relative;flex:1;max-width:360px;">
        <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
        <input type="text" name="search" value="{{ $search }}" placeholder="Search book or borrower…"
               style="width:100%;padding:8px 12px 8px 32px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
    </div>
    <button type="submit" style="background:#4f46e5;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;">Search</button>
    @if($search)
    <a href="{{ route('library.history') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:8px 12px;font-size:13px;text-decoration:none;">Clear</a>
    @endif
</form>

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
    @if($history->isEmpty())
    <div style="padding:60px 20px;text-align:center;">
        <i class="bi bi-clock-history" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
        <p style="color:#94a3b8;font-size:13px;margin:0;">No borrowing history yet.</p>
    </div>
    @else
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">#</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Book</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Borrower</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Issued</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Due</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Returned</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Fine</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $i => $r)
            <tr style="border-bottom:1px solid #f1f5f9;"
                onmouseover="this.style.background='#f8fafc'"
                onmouseout="this.style.background=''">
                <td style="padding:12px 16px;color:#94a3b8;">{{ $history->firstItem() + $i }}</td>
                <td style="padding:12px 16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="{{ $r->book->cover_url ?? '' }}" alt=""
                             style="width:32px;height:32px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;"
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
                    {{ $r->issued_at ? $r->issued_at->format('d M Y') : '—' }}
                </td>
                <td style="padding:12px 16px;font-size:12px;">
                    @if($r->due_date)
                    <span style="color:#64748b;">{{ $r->due_date->format('d M Y') }}</span>
                    @else
                    <span style="color:#94a3b8;">—</span>
                    @endif
                </td>
                <td style="padding:12px 16px;font-size:12px;">
                    @if($r->returned_at)
                    <span style="color:#10b981;font-weight:500;">{{ $r->returned_at->format('d M Y') }}</span>
                    @else
                    <span style="color:#94a3b8;">Still out</span>
                    @endif
                </td>
                <td style="padding:12px 16px;">
                    @if($r->overdue_fine > 0)
                    <span style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px;">{{ $r->overdue_fine }} ETB</span>
                    @else
                    <span style="color:#94a3b8;font-size:12px;">—</span>
                    @endif
                </td>
                <td style="padding:12px 16px;">
                    @php
                    $bc = $r->status === 'returned'
                        ? ['bg'=>'#f1f5f9','color'=>'#475569']
                        : ['bg'=>'#d1fae5','color'=>'#065f46'];
                    @endphp
                    <span style="background:{{ $bc['bg'] }};color:{{ $bc['color'] }};font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">
                        {{ ucfirst($r->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9;">{{ $history->links() }}</div>
    @endif
</div>

@endsection
