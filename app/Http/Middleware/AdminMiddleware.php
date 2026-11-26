<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
{

        if (Auth::check() && Auth::user()->is_admin) {
            if (isset(Auth::user()->is_active) && !Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(403, 'Your account has been deactivated.');
            }
            return $next($request);
        }

        abort(403, 'Unauthorized');
    }
}





