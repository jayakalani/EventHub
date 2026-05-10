<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    /**
     * Handle an incoming request.
     * Prevents users from using browser back/forward buttons after logout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add headers to prevent caching of protected pages
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }
}