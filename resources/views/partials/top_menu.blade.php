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
            <li class="nav-item">
                <a href="{{ route('inbox') }}" class="navbar-nav-link" title="Inbox" style="color:rgba(255,255,255,.75);position:relative;">
                    <i class="bi bi-envelope" style="font-size:16px;"></i>
                    @php $unreadCount = \App\Models\Message::where('receiver_id', Auth::id())->where('read', false)->count(); @endphp
                    @if($unreadCount > 0)<span style="position:absolute;top:10px;right:8px;width:7px;height:7px;background:#ef4444;border-radius:50%;border:2px solid #1e1b4b;"></span>@endif
                </a>
            </li>
            <li class="nav-item d-none d-md-flex align-items-center" style="gap:8px;padding:0 8px;">
                <img src="{{ Auth::user()->photo }}" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;border:2px solid rgba(255,255,255,.3);" alt="photo">
                <span style="font-size:13px;font-weight:500;color:rgba(255,255,255,.85);">{{ Auth::user()->name }}</span>
            </li>
            <li class="nav-item"><a href="{{ route('my_account') }}" class="navbar-nav-link" title="Account Settings" style="color:rgba(255,255,255,.75);"><i class="bi bi-gear" style="font-size:16px;"></i></a></li>
            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" title="Sign Out" style="background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);color:#fca5a5;border-radius:7px;padding:6px 14px;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;" onmouseover="this.style.background='rgba(239,68,68,.35)';this.style.color='#fff';" onmouseout="this.style.background='rgba(239,68,68,.2)';this.style.color='#fca5a5';"><i class="bi bi-box-arrow-right"></i><span class="d-none d-md-inline">Sign Out</span></button>
                </form>
            </li>
        </ul>
    </div>
</div>