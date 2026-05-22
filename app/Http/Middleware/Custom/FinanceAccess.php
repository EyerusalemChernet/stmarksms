<?php

namespace App\Http\Middleware\Custom;

use Closure;

class FinanceAccess
{
    public function handle($request, Closure $next)
    {
        $allowedRoles = ['accountant', 'admin', 'super_admin'];
        
        if (auth()->check() && in_array(auth()->user()->user_type, $allowedRoles)) {
            return $next($request);
        }

        return redirect('/dashboard')->with('flash_danger', 'Access denied. This area requires Finance access.');
    }
}
