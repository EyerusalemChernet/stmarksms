<?php

namespace App\Http\Middleware\Custom;

use Closure;

class Accountant
{
    public function handle($request, Closure $next)
    {
        if (auth()->check() && auth()->user()->user_type === 'accountant') {
            return $next($request);
        }

        return redirect('/dashboard')->with('flash_danger', 'Unauthorised access');
    }
}
