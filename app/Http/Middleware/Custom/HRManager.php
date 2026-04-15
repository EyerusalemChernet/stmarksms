<?php

namespace App\Http\Middleware\Custom;

use Closure;
use Illuminate\Support\Facades\Auth;

class HRManager
{
    /**
     * Allow only hr_manager role.
     * Admin and super_admin do NOT have access to HR/Finance routes.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->user_type === 'hr_manager') {
            return $next($request);
        }
        return redirect()->route('dashboard')
            ->with('flash_danger', 'Access denied. This area is restricted to HR Managers.');
    }
}
