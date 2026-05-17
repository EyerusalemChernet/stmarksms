@extends('layouts.master')
@section('page_title', 'Dashboard')
@section('content')

@php
    $session = \App\Helpers\Qs::getCurrentSession();
    $userName = explode(' ', auth()->user()->name)[0];
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

{{-- ── Welcome Banner ──────────────────────────────────────────────────── --}}
<div style="
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(79,70,229,.35);
">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.06);border-radius:50%;"></div>
    <div style="position:absolute;bottom:-60px;right:80px;width:150px;height:150px;background:rgba(255,255,255,.04);border-radius:50%;"></div>
    <div style="position:relative;z-index:1;">
        <div style="color:rgba(255,255,255,.75);font-size:13px;margin-bottom:4px;">{{ $greeting }},</div>
        <h4 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">{{ $userName }} 👋</h4>
        <div style="color:rgba(255,255,255,.7);font-size:13px;">
            <i class="bi bi-calendar3 mr-1"></i>Academic Year {{ $session }}
            &nbsp;·&nbsp;
            <i class="bi bi-clock mr-1"></i>{{ now()->format('l, d M Y') }}
        </div>
    </div>
</div>

{{-- ── Stat Cards ───────────────────────────────────────────────────────── --}}
<div class="row mb-2">
    <div class="col-6 col-xl-3 mb-3">
        <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:14px;padding:20px;box-shadow:0 4px 20px rgba(79,70,229,.3);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-15px;right:-15px;width:80px;height:80px;background:rgba(255,255,255,.1);border-radius:50%;"></div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;">
                <div>
                    <div style="color:rgba(255,255,255,.75);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Total Students</div>
                    <div style="color:#fff;font-size:32px;font-weight:800;line-height:1;">{{ $total_students ?? 0 }}</div>
                </div>
                <div style="background:rgba(255,255,255,.2);border-radius:12px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-people-fill" style="font-size:20px;color:#fff;"></i>
                </div>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);">
                <a href="{{ route('students.list', \App\Models\MyClass::first()->id ?? 1) }}" style="color:rgba(255,255,255,.8);font-size:12px;text-decoration:none;">
                    View all <i class="bi bi-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:14px;padding:20px;box-shadow:0 4px 20px rgba(16,185,129,.3);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-15px;right:-15px;width:80px;height:80px;background:rgba(255,255,255,.1);border-radius:50%;"></div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;">
                <div>
                    <div style="color:rgba(255,255,255,.75);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Total Teachers</div>
                    <div style="color:#fff;font-size:32px;font-weight:800;line-height:1;">{{ $total_teachers ?? 0 }}</div>
                </div>
                <div style="background:rgba(255,255,255,.2);border-radius:12px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-workspace" style="font-size:20px;color:#fff;"></i>
                </div>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);">
                <a href="{{ route('users.index') }}" style="color:rgba(255,255,255,.8);font-size:12px;text-decoration:none;">
                    Manage staff <i class="bi bi-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:14px;padding:20px;box-shadow:0 4px 20px rgba(59,130,246,.3);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-15px;right:-15px;width:80px;height:80px;background:rgba(255,255,255,.1);border-radius:50%;"></div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;">
                <div>
                    <div style="color:rgba(255,255,255,.75);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Avg Attendance</div>
                    <div style="color:#fff;font-size:32px;font-weight:800;line-height:1;">{{ $attendance_pct ?? 0 }}<span style="font-size:18px;">%</span></div>
                </div>
                <div style="background:rgba(255,255,255,.2);border-radius:12px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-clipboard-check-fill" style="font-size:20px;color:#fff;"></i>
                </div>
            </div>
            <div style="margin-top:12px;">
                <div style="background:rgba(255,255,255,.2);border-radius:20px;height:4px;overflow:hidden;">
                    <div style="background:#fff;height:4px;width:{{ $attendance_pct ?? 0 }}%;border-radius:20px;transition:width .6s;"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div style="background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;padding:20px;box-shadow:0 4px 20px rgba(245,158,11,.3);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-15px;right:-15px;width:80px;height:80px;background:rgba(255,255,255,.1);border-radius:50%;"></div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;">
                <div>
                    <div style="color:rgba(255,255,255,.75);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Total Parents</div>
                    <div style="color:#fff;font-size:32px;font-weight:800;line-height:1;">{{ $total_parents ?? 0 }}</div>
                </div>
                <div style="background:rgba(255,255,255,.2);border-radius:12px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-heart" style="font-size:20px;color:#fff;"></i>
                </div>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);">
                <span style="color:rgba(255,255,255,.8);font-size:12px;">
                    {{ $total_admins ?? 0 }} admin(s) registered
                </span>
            </div>
        </div>
    </div>
</div>

{{-- ── Second row stats ─────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="background:#d1fae5;border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-check-circle-fill" style="color:#10b981;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#1e293b;line-height:1;">{{ $total_paid ?? 0 }}</div>
                    <div style="font-size:11px;color:#64748b;font-weight:500;margin-top:2px;">Fees Cleared</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="background:#fee2e2;border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-exclamation-circle-fill" style="color:#ef4444;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#1e293b;line-height:1;">{{ $total_unpaid ?? 0 }}</div>
                    <div style="font-size:11px;color:#64748b;font-weight:500;margin-top:2px;">Outstanding</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="background:#dbeafe;border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-calendar3" style="color:#3b82f6;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#1e293b;line-height:1;">{{ $total_sessions ?? 0 }}</div>
                    <div style="font-size:11px;color:#64748b;font-weight:500;margin-top:2px;">Att. Sessions</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="background:#{{ ($unread_messages??0) > 0 ? 'fee2e2' : 'f1f5f9' }};border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-envelope-fill" style="color:#{{ ($unread_messages??0) > 0 ? 'ef4444' : '64748b' }};font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#1e293b;line-height:1;">{{ $unread_messages ?? 0 }}</div>
                    <div style="font-size:11px;color:#64748b;font-weight:500;margin-top:2px;">Unread Messages</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Main content row ─────────────────────────────────────────────────── --}}
<div class="row">

    {{-- Quick Actions --}}
    <div class="col-lg-4 mb-4">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;">
                <div style="background:#fef3c7;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-lightning-charge-fill" style="color:#f59e0b;font-size:14px;"></i>
                </div>
                <span style="font-weight:700;font-size:14px;color:#1e293b;">Quick Actions</span>
            </div>
            <div style="padding:16px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                @php
                $actions = [
                    ['route'=>'students.create','icon'=>'bi-person-plus-fill','label'=>'Admit','color'=>'#4f46e5','bg'=>'#ede9fe'],
                    ['route'=>'attendance.index','icon'=>'bi-clipboard-check-fill','label'=>'Attendance','color'=>'#10b981','bg'=>'#d1fae5'],
                    ['route'=>'marks.index','icon'=>'bi-journal-check','label'=>'Marks','color'=>'#3b82f6','bg'=>'#dbeafe'],
                    ['route'=>'reports.index','icon'=>'bi-bar-chart-line-fill','label'=>'Reports','color'=>'#f59e0b','bg'=>'#fef3c7'],
                    ['route'=>'announcements','icon'=>'bi-megaphone-fill','label'=>'Announce','color'=>'#ec4899','bg'=>'#fce7f3'],
                    ['route'=>'inbox','icon'=>'bi-envelope-fill','label'=>'Inbox','color'=>'#64748b','bg'=>'#f1f5f9'],
                    ['route'=>'users.index','icon'=>'bi-people-fill','label'=>'Users','color'=>'#8b5cf6','bg'=>'#ede9fe'],
                    ['route'=>'classes.index','icon'=>'bi-grid-3x3-gap-fill','label'=>'Classes','color'=>'#14b8a6','bg'=>'#ccfbf1'],
                    ['route'=>'marks.bulk','icon'=>'bi-file-earmark-text-fill','label'=>'Marksheet','color'=>'#f97316','bg'=>'#ffedd5'],
                ];
                @endphp
                @foreach($actions as $a)
                <a href="{{ route($a['route']) }}" style="
                    background:#f8fafc;
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:14px 8px;
                    text-align:center;
                    text-decoration:none;
                    display:block;
                    transition:all .15s;
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
    </div>

    {{-- Announcements --}}
    <div class="col-lg-8 mb-4">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;height:100%;">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="background:#dbeafe;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-megaphone-fill" style="color:#3b82f6;font-size:14px;"></i>
                    </div>
                    <span style="font-weight:700;font-size:14px;color:#1e293b;">Recent Announcements</span>
                </div>
                <a href="{{ route('announcements') }}" style="font-size:12px;color:#4f46e5;text-decoration:none;font-weight:500;">
                    View all <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div>
                @forelse($announcements ?? [] as $a)
                <div style="padding:14px 20px;border-bottom:1px solid #f8fafc;display:flex;gap:12px;align-items:flex-start;">
                    <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                        <i class="bi bi-bell-fill" style="color:#fff;font-size:14px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                            <span style="font-weight:600;font-size:13px;color:#1e293b;">{{ $a->title }}</span>
                            <span style="font-size:11px;color:#94a3b8;white-space:nowrap;flex-shrink:0;">{{ $a->created_at->diffForHumans() }}</span>
                        </div>
                        <p style="margin:4px 0 0;font-size:12px;color:#64748b;line-height:1.5;">{{ \Illuminate\Support\Str::limit($a->body, 130) }}</p>
                    </div>
                </div>
                @empty
                <div style="padding:48px 20px;text-align:center;">
                    <i class="bi bi-megaphone" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
                    <p style="color:#94a3b8;font-size:13px;margin:0;">No announcements yet.</p>
                    <a href="{{ route('announcements') }}" style="font-size:12px;color:#4f46e5;text-decoration:none;margin-top:8px;display:inline-block;">Create one <i class="bi bi-arrow-right"></i></a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
