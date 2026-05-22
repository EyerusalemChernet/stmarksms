@extends('layouts.master')
@section('page_title', 'Parent Dashboard')
@section('content')

@php
    $userName = explode(' ', auth()->user()->name)[0];
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

{{-- ── Welcome Banner ──────────────────────────────────────────────────── --}}
<div style="
    background: linear-gradient(135deg, #ec4899 0%, #db2777 50%, #be185d 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(236,72,153,.35);
">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.06);border-radius:50%;"></div>
    <div style="position:absolute;bottom:-60px;right:80px;width:150px;height:150px;background:rgba(255,255,255,.04);border-radius:50%;"></div>
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="color:rgba(255,255,255,.75);font-size:13px;margin-bottom:4px;">{{ $greeting }},</div>
            <h4 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">{{ $userName }} 👋</h4>
            <div style="color:rgba(255,255,255,.7);font-size:13px;">
                <i class="bi bi-calendar3 mr-1"></i>{{ now()->format('l, d M Y') }}
                &nbsp;·&nbsp; Academic Year {{ $year ?? '' }}
            </div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center;">
                <div style="color:#fff;font-size:24px;font-weight:800;">{{ count($childData) }}</div>
                <div style="color:rgba(255,255,255,.8);font-size:11px;font-weight:500;">{{ count($childData) === 1 ? 'Child' : 'Children' }}</div>
            </div>
            @if($unread > 0)
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center;">
                <div style="color:#fff;font-size:24px;font-weight:800;">{{ $unread }}</div>
                <div style="color:rgba(255,255,255,.8);font-size:11px;font-weight:500;">Unread Messages</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Unread message alert ─────────────────────────────────────────────── --}}
@if($unread > 0)
<div style="background:#dbeafe;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <i class="bi bi-envelope-fill" style="color:#3b82f6;font-size:18px;"></i>
        <span style="color:#1e40af;font-size:13px;font-weight:500;">
            You have <strong>{{ $unread }}</strong> unread message(s) from the school.
        </span>
    </div>
    <a href="{{ route('inbox') }}" style="background:#3b82f6;color:#fff;border-radius:7px;padding:6px 14px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;">
        Open Inbox <i class="bi bi-arrow-right ml-1"></i>
    </a>
</div>
@endif

@if(isset($familyInfo) && ($familyInfo['sibling_eligible'] || $familyInfo['employee_eligible']))
<div class="alert alert-success mb-3" style="font-size:13px;">
  <i class="bi bi-percent mr-2"></i>
  <strong>Automatic fee discounts active for your family.</strong>
  @if($familyInfo['employee_eligible'])
    Employee child discount: <strong>{{ $familyInfo['employee_pct'] }}%</strong>
  @elseif($familyInfo['sibling_eligible'])
    Sibling discount ({{ $familyInfo['children_count'] }} children): <strong>{{ $familyInfo['sibling_pct'] }}%</strong>
  @endif
  — applied automatically on invoices. <a href="{{ route('parent.fees') }}" class="alert-link">View school fees</a>
</div>
@endif

{{-- Children Cards --}}
@forelse($childData as $cd)
@php $sr = $cd['sr']; @endphp

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:24px;overflow:hidden;">

    {{-- Child header --}}
    <div style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="position:relative;">
                <img src="{{ $sr->user->photo }}" class="rounded-circle"
                     width="56" height="56"
                     style="object-fit:cover;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.15);"
                     alt="{{ $sr->user->name }}">
                <div style="position:absolute;bottom:0;right:0;background:#10b981;border-radius:50%;width:14px;height:14px;border:2px solid #fff;"></div>
            </div>
            <div>
                <h5 style="margin:0 0 4px;font-size:16px;font-weight:700;color:#1e293b;">{{ $sr->user->name }}</h5>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="background:#ede9fe;color:#4f46e5;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">
                        {{ $sr->my_class->name ?? '—' }}
                    </span>
                    <span style="background:#f1f5f9;color:#64748b;font-size:11px;font-weight:500;padding:3px 10px;border-radius:20px;">
                        Section {{ $sr->section->name ?? '—' }}
                    </span>
                    <span style="color:#94a3b8;font-size:11px;">ADM: {{ $sr->adm_no }}</span>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            @if($sr->section && $sr->section->teacher_id)
            <a href="{{ route('compose', ['reply' => $sr->section->teacher_id]) }}"
               style="background:#fef3c7;border:1px solid #fde68a;color:#d97706;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-chat-text-fill"></i> Message Teacher
            </a>
            @endif
            <a href="{{ route('parent.timeline', $sr->user_id) }}"
               style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-clock-history"></i> Timeline
            </a>
            <a href="{{ route('parent.child', $sr->user_id) }}"
               style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(79,70,229,.3);">
                <i class="bi bi-eye-fill"></i> Full Details
            </a>
        </div>
    </div>

    {{-- Stats row --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;">

            {{-- Fees --}}
            <div class="col-md-3 mb-3">
                <div class="card border h-100">
                    <div class="card-body text-center py-3">
                        <i class="icon-coin-dollar icon-2x {{ $cd['unpaid'] > 0 ? 'text-danger' : 'text-success' }} mb-2"></i>
                        @if($cd['unpaid'] > 0)
                            <h4 class="text-danger">ETB {{ number_format($cd['fee_balance'], 0) }}</h4>
                            <small class="text-muted">{{ $cd['unpaid'] }} unpaid invoice(s)</small>
                            @if($cd['fee_discount'] > 0)
                            <div class="mt-1"><span class="badge badge-success">Discount applied</span></div>
                            @endif
                            @if($cd['discount_type'])
                            <div class="mt-1"><small class="text-muted">{{ \App\Services\DiscountService::discountTypeLabel($cd['discount_type']) }}</small></div>
                            @endif
                        @else
                            <h4 class="text-success"><i class="icon-checkmark3"></i></h4>
                            <small class="text-muted">All Fees Cleared</small>
                        @endif
                        <div class="mt-2">
                            <a href="{{ route('parent.fees') }}" class="btn btn-xs btn-outline-primary">View Fees</a>
                        </div>
                    </div>
                </div>
        {{-- Attendance --}}
        <div style="padding:20px;border-right:1px solid #f1f5f9;text-align:center;">
            @php $attColor = $cd['att_pct'] >= 75 ? '#10b981' : '#ef4444'; $attBg = $cd['att_pct'] >= 75 ? '#d1fae5' : '#fee2e2'; @endphp
            <div style="background:{{ $attBg }};border-radius:50%;width:52px;height:52px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                <i class="bi bi-clipboard-check-fill" style="font-size:22px;color:{{ $attColor }};"></i>
            </div>
            <div style="font-size:22px;font-weight:800;color:{{ $attColor }};line-height:1;">{{ $cd['att_pct'] }}%</div>
            <div style="font-size:11px;color:#64748b;margin-top:4px;font-weight:500;">Attendance</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $cd['att_present'] }}/{{ $cd['att_total'] }} days</div>
            @if(count($cd['recent_att']) > 0)
            <div style="margin-top:8px;display:flex;align-items:center;justify-content:center;gap:4px;" title="Last 5 Days Attendance (Left to Right)">
                @foreach($cd['recent_att']->reverse() as $att)
                    @if($att->status == 'present')
                        <div title="{{ \Carbon\Carbon::parse($att->date)->format('D, M d') }} - Present" style="width:12px;height:12px;border-radius:50%;background:#10b981;"></div>
                    @elseif($att->status == 'late')
                        <div title="{{ \Carbon\Carbon::parse($att->date)->format('D, M d') }} - Late" style="width:12px;height:12px;border-radius:50%;background:#f59e0b;"></div>
                    @else
                        <div title="{{ \Carbon\Carbon::parse($att->date)->format('D, M d') }} - Absent" style="width:12px;height:12px;border-radius:50%;background:#ef4444;"></div>
                    @endif
                @endforeach
            </div>
            @endif
            @if($cd['att_pct'] < 75)
            <div style="margin-top:8px;">
                <span style="background:#fee2e2;color:#991b1b;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;">Below 75%</span>
            </div>
            @endif
        </div>

        {{-- Latest Exam --}}
        <div style="padding:20px;border-right:1px solid #f1f5f9;text-align:center;">
            <div style="background:#fef3c7;border-radius:50%;width:52px;height:52px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                <i class="bi bi-journal-check" style="font-size:22px;color:#f59e0b;"></i>
            </div>
            @if($cd['blocked'])
                <div style="font-size:13px;font-weight:600;color:#ef4444;">Blocked</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Attendance/fees issue</div>
            @elseif($cd['latest_exr'])
                <div style="font-size:22px;font-weight:800;color:#f59e0b;line-height:1;">{{ $cd['latest_exr']->total ?? '—' }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;font-weight:500;">Latest Score</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                    Avg: {{ $cd['latest_exr']->ave ?? '—' }} &bull; Pos: {{ $cd['latest_exr']->pos ?? '—' }}
                </div>
            @else
                <div style="font-size:13px;color:#94a3b8;font-weight:500;">No results yet</div>
            @endif
        </div>

        {{-- Fees --}}
        <div style="padding:20px;border-right:1px solid #f1f5f9;text-align:center;">
            @php $feeColor = $cd['unpaid'] > 0 ? '#ef4444' : '#10b981'; $feeBg = $cd['unpaid'] > 0 ? '#fee2e2' : '#d1fae5'; @endphp
            <div style="background:{{ $feeBg }};border-radius:50%;width:52px;height:52px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                <i class="bi bi-{{ $cd['unpaid'] > 0 ? 'exclamation-circle-fill' : 'check-circle-fill' }}" style="font-size:22px;color:{{ $feeColor }};"></i>
            </div>
            @if($cd['unpaid'] > 0)
                <div style="font-size:22px;font-weight:800;color:#ef4444;line-height:1;">{{ $cd['unpaid'] }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;font-weight:500;">Outstanding Fee(s)</div>
                <div style="margin-top:8px;">
                    <span style="background:#fee2e2;color:#991b1b;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;">Payment Required</span>
                </div>
            @else
                <div style="font-size:22px;font-weight:800;color:#10b981;line-height:1;"><i class="bi bi-check-lg"></i></div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;font-weight:500;">All Fees Cleared</div>
            @endif
        </div>

        {{-- Library --}}
        <div style="padding:20px;text-align:center;">
            <div style="background:#dbeafe;border-radius:50%;width:52px;height:52px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                <i class="bi bi-bookshelf" style="font-size:22px;color:#3b82f6;"></i>
            </div>
            <div style="font-size:22px;font-weight:800;color:#3b82f6;line-height:1;">{{ $cd['borrowed']->count() }}</div>
            <div style="font-size:11px;color:#64748b;margin-top:4px;font-weight:500;">Books Borrowed</div>
            @foreach($cd['borrowed']->take(2) as $br)
            <div style="margin-top:4px;">
                <span style="background:#dbeafe;color:#1e40af;font-size:10px;font-weight:500;padding:2px 8px;border-radius:20px;">{{ \Illuminate\Support\Str::limit($br->book->name ?? '—', 18) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@empty
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:60px 20px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.06);">
    <i class="bi bi-people" style="font-size:48px;color:#cbd5e1;display:block;margin-bottom:16px;"></i>
    <h5 style="color:#475569;font-weight:600;margin-bottom:8px;">No children linked</h5>
    <p style="color:#94a3b8;font-size:13px;margin:0;">Please contact the school administrator to link your child's account.</p>
</div>
@endforelse

{{-- ── Announcements ────────────────────────────────────────────────────── --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-top:4px;">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="background:#dbeafe;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-megaphone-fill" style="color:#3b82f6;font-size:14px;"></i>
            </div>
            <span style="font-weight:700;font-size:14px;color:#1e293b;">School Announcements</span>
        </div>
        <a href="{{ route('announcements') }}" style="font-size:12px;color:#4f46e5;text-decoration:none;font-weight:500;">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    @forelse($announcements as $a)
    <div style="padding:14px 20px;border-bottom:1px solid #f8fafc;display:flex;gap:12px;align-items:flex-start;">
        <div style="background:linear-gradient(135deg,#ec4899,#db2777);border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <i class="bi bi-bell-fill" style="color:#fff;font-size:13px;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <span style="font-weight:600;font-size:13px;color:#1e293b;">{{ $a->title }}</span>
                <span style="font-size:11px;color:#94a3b8;white-space:nowrap;flex-shrink:0;">{{ $a->created_at->diffForHumans() }}</span>
            </div>
            <p style="margin:4px 0 0;font-size:12px;color:#64748b;line-height:1.5;">{{ \Illuminate\Support\Str::limit($a->body, 150) }}</p>
        </div>
    </div>
    @empty
    <div style="padding:32px 20px;text-align:center;color:#94a3b8;font-size:13px;">No announcements at this time.</div>
    @endforelse
</div>

@endsection
