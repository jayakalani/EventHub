<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AdminNotificationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FailedLoginAttempts
{
    /**
     * Maximum number of failed login attempts before locking the account.
     */
    private const MAX_ATTEMPTS = 8;

    /**
     * Handle an incoming request.
     * Checks if user account is locked due to failed login attempts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check on login page
        if ($request->is('login') || $request->is('login/*')) {
            $email = $request->input('email');

            if ($email) {
                $user = User::where('email', $email)->first();

                if ($user && $user->is_locked) {
                    // Check if lock has expired
                    if ($user->locked_until && now()->greaterThan($user->locked_until)) {
                        // Unlock automatically after lock period expires
                        $user->update([
                            'is_locked' => false,
                            'failed_attempts' => 0,
                            'locked_until' => null,
                        ]);
                    } else {
                        // Account is still locked
                        return back()->with('error', 'Your account is locked due to multiple failed login attempts. Please contact the administrator.');
                    }
                }
            }
        }

        return $next($request);
    }

    /**
     * Record a failed login attempt for a user.
     */
    public static function recordFailedAttempt(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $failedAttempts = $user->failed_attempts + 1;

            if ($failedAttempts >= self::MAX_ATTEMPTS) {
                // Lock the account permanently - requires manual unlock by admin
                $user->update([
                    'failed_attempts' => $failedAttempts,
                    'is_locked' => true,
                    'locked_until' => null, // No auto-unlock
                ]);

                app(AdminNotificationService::class)->notifyAccountLocked($user->fresh());
            } else {
                $user->update([
                    'failed_attempts' => $failedAttempts,
                ]);
            }
        }
    }

    /**
     * Reset failed login attempts after successful login.
     */
    public static function resetFailedAttempts(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'failed_attempts' => 0,
                'is_locked' => false,
                'locked_until' => null,
            ]);
        }
    }
}
