<div class="navbar navbar-expand-md navbar-dark">

    {{-- Brand --}}
    <div class="d-flex align-items-center" style="min-width:220px;gap:10px;">
        <div style="width:32px;height:32px;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-mortarboard-fill" style="font-size:16px;color:#fff;"></i>
        </div>
        <a href="{{ route('dashboard') }}" class="d-inline-block text-decoration-none">
            <h4 class="text-bold mb-0" style="color:#fff;font-size:16px;font-weight:700;">{{ Qs::getSystemName() }}</h4>
        </a>
    </div>

    <div class="d-md-none ml-auto d-flex" style="gap:6px;">
        {{-- Mobile sidebar toggle --}}
        <button id="mobile-sidebar-toggle" type="button"
                style="border:none;background:rgba(255,255,255,.15);border-radius:6px;padding:6px 10px;color:#fff;display:inline-flex;align-items:center;">
            <i class="bi bi-layout-sidebar" style="font-size:18px;"></i>
        </button>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile" style="border:none;background:rgba(255,255,255,.15);border-radius:6px;padding:6px 10px;color:#fff;"><i class="bi bi-three-dots-vertical"></i></button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav">
            <li class="nav-item"><a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block" style="color:rgba(255,255,255,.75);"><i class="bi bi-layout-sidebar" style="font-size:16px;"></i></a></li>
        </ul>
        <span class="navbar-text ml-md-3 mr-md-auto"></span>
        <ul class="navbar-nav" style="gap:6px;align-items:center;">
            <li class="nav-item d-none d-md-block">
                <span style="font-size:12px;background:rgba(255,255,255,.12);border-radius:20px;padding:5px 14px;color:rgba(255,255,255,.85);"><i class="bi bi-calendar3 mr-1"></i>{{ Qs::getSetting('current_session') }}</span>
            </li>

            {{-- Notification Bell --}}
            @php
                $unreadMessages   = \App\Models\Message::where('receiver_id', Auth::id())->where('read', false)->where('archived', false)->count();
                $userType         = Qs::getUserType();
                $audienceKey      = $userType . 's';
                $newAnnouncements = \App\Models\Announcement::where('active', true)
                    ->where(function($q) use ($audienceKey) {
                        $q->where('audience', 'all')->orWhere('audience', $audienceKey);
                    })
                    ->where('created_at', '>=', now()->subDays(3))
                    ->count();
                $totalNotifications = $unreadMessages + $newAnnouncements;
            @endphp
            <li class="nav-item" style="position:relative;">
                <a href="{{ route('inbox') }}" class="navbar-nav-link" title="Notifications"
                   style="color:rgba(255,255,255,.75);position:relative;display:inline-flex;align-items:center;padding:8px 10px;">
                    <i class="bi bi-bell" style="font-size:17px;"></i>
                    @if($totalNotifications > 0)
                    <span style="
                        position:absolute;
                        top:4px;right:4px;
                        min-width:17px;height:17px;
                        background:#ef4444;
                        border-radius:9px;
                        border:2px solid #1e1b4b;
                        font-size:10px;font-weight:700;
                        color:#fff;
                        display:flex;align-items:center;justify-content:center;
                        line-height:1;padding:0 3px;
                    ">{{ $totalNotifications > 99 ? '99+' : $totalNotifications }}</span>
                    @endif
                </a>
                {{-- Dropdown panel --}}
                <div id="notif-dropdown" style="
                    display:none;
                    position:absolute;top:100%;right:0;
                    width:300px;
                    background:#fff;
                    border-radius:10px;
                    box-shadow:0 8px 30px rgba(0,0,0,.18);
                    z-index:9999;
                    overflow:hidden;
                ">
                    <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                        <strong style="font-size:13px;color:#1e293b;">Notifications</strong>
                        @if($totalNotifications > 0)
                        <span style="font-size:11px;background:#ef4444;color:#fff;border-radius:10px;padding:2px 8px;">{{ $totalNotifications }} new</span>
                        @endif
                    </div>
                    <div style="max-height:320px;overflow-y:auto;">
                        @if($unreadMessages > 0)
                        <a href="{{ route('inbox') }}" style="display:flex;align-items:center;gap:10px;padding:11px 16px;text-decoration:none;border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <span style="width:34px;height:34px;background:#ede9fe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-envelope-fill" style="color:#7c3aed;font-size:15px;"></i>
                            </span>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ $unreadMessages }} unread message{{ $unreadMessages > 1 ? 's' : '' }}</div>
                                <div style="font-size:11px;color:#94a3b8;">Click to open inbox</div>
                            </div>
                        </a>
                        @endif
                        @if($newAnnouncements > 0)
                        <a href="{{ route('announcements') }}" style="display:flex;align-items:center;gap:10px;padding:11px 16px;text-decoration:none;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <span style="width:34px;height:34px;background:#fef3c7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-megaphone-fill" style="color:#d97706;font-size:15px;"></i>
                            </span>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ $newAnnouncements }} new announcement{{ $newAnnouncements > 1 ? 's' : '' }}</div>
                                <div style="font-size:11px;color:#94a3b8;">Posted in the last 3 days</div>
                            </div>
                        </a>
                        @endif
                        @if($totalNotifications === 0)
                        <div style="padding:24px 16px;text-align:center;color:#94a3b8;font-size:13px;">
                            <i class="bi bi-check-circle" style="font-size:22px;display:block;margin-bottom:6px;color:#10b981;"></i>
                            All caught up!
                        </div>
                        @endif
                    </div>
                </div>
            </li>
            <li class="nav-item d-none d-md-flex align-items-center" style="gap:8px;padding:0 8px;">
                <img src="{{ Auth::user()->photo }}" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;border:2px solid rgba(255,255,255,.3);" alt="photo">
                <span style="font-size:13px;font-weight:500;color:rgba(255,255,255,.85);">{{ Auth::user()->name }}</span>
            </li>
            <li class="nav-item"><a href="{{ route('my_account') }}" class="navbar-nav-link" title="Account Settings" style="color:rgba(255,255,255,.75);"><i class="bi bi-gear" style="font-size:16px;"></i></a></li>

            {{-- Language toggle --}}
            <li class="nav-item">
                <button id="lang-toggle" title="Switch Language / ቋንቋ ቀይር"
                        style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;border-radius:7px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;letter-spacing:.3px;">
                    <span id="lang-label">EN</span>
                </button>
            </li>
            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" title="Sign Out" style="background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);color:#fca5a5;border-radius:7px;padding:6px 14px;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;" onmouseover="this.style.background='rgba(239,68,68,.35)';this.style.color='#fff';" onmouseout="this.style.background='rgba(239,68,68,.2)';this.style.color='#fca5a5';"><i class="bi bi-box-arrow-right"></i><span class="d-none d-md-inline">Sign Out</span></button>
                </form>
            </li>
        </ul>
    </div>
</div>