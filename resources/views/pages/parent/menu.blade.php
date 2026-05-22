{{-- Parent Dashboard --}}
<li class="nav-item">
    <a href="{{ route('parent.dashboard') }}" class="nav-link {{ Route::is('parent.dashboard') ? 'active' : '' }}">
        <i class="icon-home4"></i> <span>My Children</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('parent.fees') }}" class="nav-link {{ Route::is('parent.fees') || Route::is('parent.fee') ? 'active' : '' }}">
        <i class="icon-coin-dollar"></i> <span>School Fees</span>
    </a>
</li>
