<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        if (isset($user->is_active) && ! $user->is_active) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Account deactivated.'], 403);
            }
            abort(403, 'Your account has been deactivated.');
        }

        // If no specific roles given, any authenticated staff member is allowed
        if (empty($roles)) {
            return $next($request);
        }

        if ($user->hasRole($roles)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized action. Your role does not have permission.'], 403);
        }

        abort(403, 'Unauthorized action. Your role (' . ($user->primary_role ?? 'Staff') . ') does not have permission to access this section.');
    }
}
