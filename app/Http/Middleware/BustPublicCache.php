<?php

namespace App\Http\Middleware;

use App\Support\PublicCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** After any successful admin change (POST/PUT/PATCH/DELETE), the public pages rebuild their cached data on the next visit. */
class BustPublicCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodSafe() && $response->getStatusCode() < 400) {
            PublicCache::bust();
        }

        return $response;
    }
}
