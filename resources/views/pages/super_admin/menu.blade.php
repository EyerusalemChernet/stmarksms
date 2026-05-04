{{-- Pins (super_admin only) --}}
<li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['pins.create','pins.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
    <a href="#" class="nav-link"><i class="bi bi-lock2"></i><span>Pins</span></a>
    <ul class="nav nav-group-sub" data-submenu-title="Manage Pins">
        <li class="nav-item"><a href="{{ route('pins.create') }}" class="nav-link {{ Route::is('pins.create') ? 'active' : '' }}">Generate Pins</a></li>
        <li class="nav-item"><a href="{{ route('pins.index') }}" class="nav-link {{ Route::is('pins.index') ? 'active' : '' }}">View Pins</a></li>
    </ul>
</li>
{{-- NOTE: System Settings link is already rendered in partials/menu.blade.php under the Settings section --}}
