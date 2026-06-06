<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * @var array<int, string>
     */
    protected array $except = [
        'auth.google.complete-profile',
        'auth.google.complete-profile.store',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->profile_completed) {
            return $next($request);
        }

        if ($request->routeIs($this->except)) {
            return $next($request);
        }

        return redirect()->route('auth.google.complete-profile');
    }
}
