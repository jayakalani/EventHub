<?php

namespace App\Services;

use App\Http\Middleware\FailedLoginAttempts;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthLoginService
{
    public function finalizeLogin(Request $request, User $user, bool $remember = false): RedirectResponse
    {
        if ($user->is_locked) {
            $user->recoverIfLastAdminLocked();
        }

        if ($user->is_locked) {
            return redirect()->route('login')
                ->with('error', 'Your account is locked due to multiple failed login attempts. Please contact the administrator.');
        }

        if (! $user->is_active) {
            return redirect()->route('login')
                ->with('error', 'Your account is inactive. Please contact the administrator.');
        }

        FailedLoginAttempts::resetFailedAttempts($user->email);

        if (! $user->profile_completed) {
            Auth::login($user, $remember);
            $request->session()->regenerate();

            return redirect()->route('auth.google.complete-profile');
        }

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $remember);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $remember);
        $request->session()->put('two_factor_verified', true);
        $request->session()->regenerate();
        $request->session()->forget('postponement_alerts_shown');

        return redirect()->intended(route('dashboard'))
            ->with('welcome_back', true);
    }
}
