<?php

namespace App\Http\Middleware\Custom;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Qs;

class AdminOrSuperAdmin
{
    /**
     * Handle an incoming request.
     * Allows both super_admin and admin user types.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && (Qs::userIsAdmin() || Qs::userIsSuperAdmin())) {
            return $next($request);
        }
        return redirect()->route('login');
    }
}
