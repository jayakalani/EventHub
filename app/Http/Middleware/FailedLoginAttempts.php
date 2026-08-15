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
                    $user->recoverIfLastAdminLocked();
                    $user->refresh();

                    if ($user->locked_until && now()->greaterThan($user->locked_until)) {
                        $user->update([
                            'is_locked' => false,
                            'failed_attempts' => 0,
                            'locked_until' => null,
                        ]);
                    } elseif ($user->is_locked) {
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
                // Never lock the last operational admin — that would make the platform unrecoverable.
                if ($user->isSoleOperationalAdmin()) {
                    $user->update([
                        'failed_attempts' => $failedAttempts,
                    ]);

                    if ($failedAttempts === self::MAX_ATTEMPTS) {
                        app(AdminNotificationService::class)->notifyLastAdminLockPrevented($user->fresh());
                    }

                    return;
                }

                // Lock the account permanently - requires manual unlock by admin
                $user->update([
                    'failed_attempts' => $failedAttempts,
                    'is_locked' => true,
                    'locked_until' => null, // No auto-unlock
                ]);

                $user->invalidateSessions();

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
