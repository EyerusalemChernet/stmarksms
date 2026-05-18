{{-- Employee Self-Service Menu --}}
<li class="sidebar-section-label">My HR</li>
<li class="nav-item">
    <a href="{{ route('my.profile') }}" class="nav-link {{ Route::is('my.profile') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i><span>My Profile</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('my.payslips') }}" class="nav-link {{ Route::is('my.payslips') || Route::is('my.payslip') ? 'active' : '' }}">
        <i class="bi bi-cash-stack"></i><span>My Payslips</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('my.leave.index') }}" class="nav-link {{ Route::is('my.leave.*') ? 'active' : '' }}">
        <i class="bi bi-calendar-heart"></i><span>My Leave</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('my.training') }}" class="nav-link {{ Route::is('my.training') ? 'active' : '' }}">
        <i class="bi bi-mortarboard"></i><span>My Training</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('my.performance.self') }}" class="nav-link {{ Route::is('my.performance.self') || Route::is('my.performance') ? 'active' : '' }}">
        <i class="bi bi-star"></i><span>My Performance</span>
    </a>
</li>
