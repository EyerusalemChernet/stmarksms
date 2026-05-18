<?php

namespace App\Http\Middleware\Custom;

use Closure;
use Illuminate\Support\Facades\Auth;

class HRManager
{
    /**
     * Allow hr_manager, admin, super_admin, and employee to access HR routes.
     * Employees can access self-service HR features (profile, payslips, leave, training, performance).
     * This matches standard HRMS access control where admins manage HR and employees access self-service.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && in_array(Auth::user()->user_type, ['hr_manager', 'admin', 'super_admin', 'employee'])) {
            return $next($request);
        }

        return redirect()->route('dashboard')
            ->with('flash_danger', 'Access denied. This area requires HR Manager, Admin, or Employee access.');
    }
}
