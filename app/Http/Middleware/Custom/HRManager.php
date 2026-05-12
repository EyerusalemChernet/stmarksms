<?php

namespace App\Http\Middleware\Custom;

use Closure;
use Illuminate\Support\Facades\Auth;

class HRManager
{
    /**
     * Allow hr_manager, admin, and super_admin to access HR routes.
     * This matches standard HRMS access control where admins can manage HR.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && in_array(Auth::user()->user_type, ['hr_manager', 'admin', 'super_admin'])) {
            return $next($request);
        }

        return redirect()->route('dashboard')
            ->with('flash_danger', 'Access denied. This area requires HR Manager or Admin access.');
    }
}
