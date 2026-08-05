<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AttendeeNotificationCategory;
use App\Http\Controllers\Controller;
use App\Services\AttendeeNotificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            app(AttendeeNotificationService::class)->send(
                $request->user(),
                AttendeeNotificationCategory::Account,
                'email_verified',
                'Your email address was verified successfully.',
                route('profile.edit'),
            );
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
