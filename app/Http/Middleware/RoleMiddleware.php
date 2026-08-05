<?php

namespace App\Http\Middleware;

use App\Models\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Can be used in two ways:
     * 1. As a global middleware to check role-based access
     * 2. As a route middleware to restrict access to specific roles
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $roleName = $user->userRole?->role_name;

        // If a specific role is required for this route
        if ($requiredRole) {
            if ($user->userRole?->name_en !== $requiredRole) {
                if ($request->expectsJson()) {
                    abort(403, 'Unauthorized action.');
                }

                $dashboard = getRoleBasedDashboard();

                if ($dashboard !== 'login') {
                    return redirect()
                        ->route($dashboard)
                        ->with('error', 'You do not have access to that area.');
                }

                abort(403, 'Unauthorized action.');
            }
        }
              

        return $next($request);
    }
}

/**
 * Get the role-based dashboard route name
 */
function getRoleBasedDashboard(): string
{
    $roleName = Auth::user()?->userRole?->name_en;

    return match ($roleName) {
        UserRole::ADMIN => 'dashboard',
        UserRole::ORGANIZER => 'organizer.dashboard',
        UserRole::CRO => 'cro.dashboard',
        UserRole::ATTENDEE => 'attendee.dashboard',
        default => 'login',
    };
}