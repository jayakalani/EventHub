<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    /**
     * Routes that are exempt from the two-factor verification check.
     *
     * @var array<int, string>
     */
    protected array $except = [
        'two-factor.challenge',
        'two-factor.verify',
        'logout',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->routeIs($this->except)) {
            return $next($request);
        }

        if ($request->session()->get('two_factor_verified')) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->put('login.id', $user->id);
        $request->session()->put('login.remember', false);

        return redirect()->route('two-factor.challenge');
    }
}
