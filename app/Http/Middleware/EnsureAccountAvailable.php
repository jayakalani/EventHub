<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAvailable
{
    /**
     * @var array<int, string>
     */
    protected array $except = [
        'logout',
        'login',
    ];

    /**
     * End the session when the signed-in account is locked or deactivated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs($this->except)) {
            return $next($request);
        }

        $user->recoverIfLastAdminLocked();

        if ($user->is_locked) {
            return $this->endSession(
                $request,
                'Your account is locked. Please contact the administrator.'
            );
        }

        if (! $user->is_active) {
            return $this->endSession(
                $request,
                'Your account is inactive. Please contact the administrator.'
            );
        }

        return $next($request);
    }

    private function endSession(Request $request, string $message): Response
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', $message);
    }
}
