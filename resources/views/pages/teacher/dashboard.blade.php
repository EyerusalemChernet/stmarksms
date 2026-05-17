@extends('layouts.master')
@section('page_title', 'Dashboard')
@section('content')

@php
    $userName = explode(' ', auth()->user()->name)[0];
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $subjectCount = isset($my_subjects) ? $my_subjects->count() : 0;
    $sessionCount = isset($today_sessions) ? $today_sessions->count() : 0;
@endphp

{{-- ── Welcome Banner ──────────────────────────────────────────────────── --}}
<div style="
    background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(16,185,129,.35);
">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.06);border-radius:50%;"></div>
    <div style="position:absolute;bottom:-60px;right:80px;width:150px;height:150px;background:rgba(255,255,255,.04);border-radius:50%;"></div>
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="color:rgba(255,255,255,.75);font-size:13px;margin-bottom:4px;">{{ $greeting }}, Teacher</div>
            <h4 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">{{ $userName }} 👋</h4>
            <div style="color:rgba(255,255,255,.7);font-size:13px;">
                <i class="bi bi-calendar3 mr-1"></i>{{ now()->format('l, d M Y') }}
            </div>
        </div>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center;backdrop-filter:blur(4px);">
                <div style="color:#fff;font-size:24px;font-weight:800;">{{ $subjectCount }}</div>
                <div style="color:rgba(255,255,255,.8);font-size:11px;font-weight:500;">Subjects</div>
            </div>
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center;backdrop-filter:blur(4px);">
                <div style="color:#fff;font-size:24px;font-weight:800;">{{ $sessionCount }}</div>
                <div style="color:rgba(255,255,255,.8);font-size:11px;font-weight:500;">Today's Sessions</div>
            </div>
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center;backdrop-filter:blur(4px);">
                <div style="color:#fff;font-size:24px;font-weight:800;">{{ $parent_messages ?? 0 }}</div>
                <div style="color:rgba(255,255,255,.8);font-size:11px;font-weight:500;">Parent Messages</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Main grid ────────────────────────────────────────────────────────── --}}
<div class="row">

    {{-- My Subjects --}}
    <div class="col-lg-5 mb-4">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="background:#d1fae5;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-journal-text" style="color:#10b981;font-size:14px;"></i>
                    </div>
                    <span style="font-weight:700;font-size:14px;color:#1e293b;">My Subjects</span>
                </div>
                <span style="background:#d1fae5;color:#065f46;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">{{ $subjectCount }} total</span>
            </div>
            <div>
                @forelse($my_subjects ?? [] as $sub)
                <div style="padding:12px 20px;border-bottom:1px solid #f8fafc;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-book-fill" style="color:#fff;font-size:13px;"></i>
                        </div>
                        <span style="font-weight:600;font-size:13px;color:#1e293b;">{{ $sub->name }}</span>
                    </div>
                    <span style="background:#ede9fe;color:#4f46e5;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">{{ $sub->my_class->name ?? '—' }}</span>
                </div>
                @empty
                <div style="padding:40px 20px;text-align:center;">
                    <i class="bi bi-journal-x" style="font-size:36px;color:#cbd5e1;display:block;margin-bottom:10px;"></i>
                    <p style="color:#94a3b8;font-size:13px;margin:0;">No subjects assigned yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-7">

        {{-- Quick Actions --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:20px;">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;">
                <div style="background:#fef3c7;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-lightning-charge-fill" style="color:#f59e0b;font-size:14px;"></i>
                </div>
                <span style="font-weight:700;font-size:14px;color:#1e293b;">Quick Actions</span>
            </div>
            <div style="padding:16px;display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                @php
                $actions = [
                    ['route'=>'attendance.index','icon'=>'bi-clipboard-check-fill','label'=>'Attendance','color'=>'#10b981','bg'=>'#d1fae5'],
                    ['route'=>'marks.index','icon'=>'bi-journal-check','label'=>'Enter Marks','color'=>'#4f46e5','bg'=>'#ede9fe'],
                    ['route'=>'marks.bulk','icon'=>'bi-file-earmark-text-fill','label'=>'Marksheet','color'=>'#3b82f6','bg'=>'#dbeafe'],
                    ['route'=>'tt.index','icon'=>'bi-calendar-week-fill','label'=>'Timetable','color'=>'#f59e0b','bg'=>'#fef3c7'],
                    ['route'=>'library.index','icon'=>'bi-bookshelf','label'=>'Library','color'=>'#14b8a6','bg'=>'#ccfbf1'],
                    ['route'=>'inbox','icon'=>'bi-envelope-fill','label'=>'Inbox','color'=>'#64748b','bg'=>'#f1f5f9'],
                ];
                @endphp
                @foreach($actions as $a)
                <a href="{{ route($a['route']) }}" style="
                    background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
                    padding:14px 8px;text-align:center;text-decoration:none;display:block;transition:all .15s;
                " onmouseover="this.style.borderColor='{{ $a['color'] }}';this.style.background='{{ $a['bg'] }}'"
                   onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
                    <i class="bi {{ $a['icon'] }}" style="font-size:20px;color:{{ $a['color'] }};display:block;margin-bottom:6px;"></i>
                    <span style="font-size:11px;font-weight:600;color:#475569;">{{ $a['label'] }}</span>
                    @if($a['route'] === 'inbox' && ($unread_messages??0) > 0)
                        <span style="background:#ef4444;color:#fff;border-radius:10px;font-size:9px;padding:1px 5px;margin-left:2px;">{{ $unread_messages }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>

        {{-- Upcoming Exams --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:20px;">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;">
                <div style="background:#fce7f3;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-journal-check" style="color:#ec4899;font-size:14px;"></i>
                </div>
                <span style="font-weight:700;font-size:14px;color:#1e293b;">Exams</span>
            </div>
            <div style="padding:4px 0;">
                @forelse($upcoming_exams ?? [] as $ex)
                <div style="padding:12px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f8fafc;">
                    <div>
                        <div style="font-weight:600;font-size:13px;color:#1e293b;">{{ $ex->name }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $ex->year }}</div>
                    </div>
                    <span style="background:#fce7f3;color:#9d174d;font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;">
                        Semester {{ $ex->term }}
                    </span>
                </div>
                @empty
                <div style="padding:24px 20px;text-align:center;color:#94a3b8;font-size:13px;">No exams scheduled.</div>
                @endforelse
            </div>
        </div>

        {{-- Announcements --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="background:#dbeafe;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-megaphone-fill" style="color:#3b82f6;font-size:14px;"></i>
                    </div>
                    <span style="font-weight:700;font-size:14px;color:#1e293b;">Announcements</span>
                </div>
                <a href="{{ route('announcements') }}" style="font-size:12px;color:#4f46e5;text-decoration:none;font-weight:500;">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            @forelse($announcements ?? [] as $a)
            <div style="padding:12px 20px;border-bottom:1px solid #f8fafc;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                    <span style="font-weight:600;font-size:13px;color:#1e293b;">{{ $a->title }}</span>
                    <span style="font-size:11px;color:#94a3b8;white-space:nowrap;">{{ $a->created_at->diffForHumans() }}</span>
                </div>
                <p style="margin:4px 0 0;font-size:12px;color:#64748b;">{{ \Illuminate\Support\Str::limit($a->body, 100) }}</p>
            </div>
            @empty
            <div style="padding:24px 20px;text-align:center;color:#94a3b8;font-size:13px;">No announcements.</div>
            @endforelse
        </div>
    </div>
</div>

@endsection
