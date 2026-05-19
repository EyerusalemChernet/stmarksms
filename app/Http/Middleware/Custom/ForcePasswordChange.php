<?php

namespace App\Http\Middleware\Custom;

use Closure;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle($request, Closure $next)
    {
        if (
            Auth::check() &&
            Auth::user()->must_change_password &&
            !$request->routeIs('password.change.form') &&
            !$request->routeIs('password.change.update') &&
            !$request->routeIs('logout')
        ) {
            return redirect()->route('password.change.form');
        }

        return $next($request);
    }
}
