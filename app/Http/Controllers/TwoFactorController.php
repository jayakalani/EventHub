<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactor
    ) {}

    public function showChallenge(): View|RedirectResponse
    {
        if (! session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required_without:recovery_code', 'nullable', 'string', 'size:6'],
            'recovery_code' => ['required_without:code', 'nullable', 'string'],
        ]);

        $userId = session('login.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        $verified = false;

        if ($request->filled('code')) {
            $verified = $this->twoFactor->verifyCode($user, $request->input('code'));
        } elseif ($request->filled('recovery_code')) {
            $verified = $this->twoFactor->verifyRecoveryCode($user, $request->input('recovery_code'));
        }

        if (! $verified) {
            return back()->withErrors([
                'code' => 'The provided two-factor authentication code is invalid.',
            ]);
        }

        Auth::login($user, session('login.remember', false));

        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->put('two_factor_verified', true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('welcome_back', true);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor authentication is already enabled.');
        }

        $secret = $this->twoFactor->generateSecret();

        $request->session()->put('two_factor_setup_secret', $secret);

        return back()->with('two_factor_setup', [
            'secret' => $secret,
            'qr_code' => $this->twoFactor->getQrCodeSvg($user, $secret),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $secret = $request->session()->get('two_factor_setup_secret');

        if (! $secret) {
            return back()->with('error', 'Two-factor setup session expired. Please start again.');
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        if (! $google2fa->verifyKey($secret, $request->input('code'))) {
            return back()->withErrors([
                'two_factor_code' => 'The provided authentication code is invalid.',
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        $request->session()->forget('two_factor_setup_secret');
        $request->session()->put('two_factor_verified', true);

        return back()->with([
            'status' => 'two-factor-enabled',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $request->session()->forget('two_factor_verified');

        return back()->with('status', 'two-factor-disabled');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor authentication is not enabled.');
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return back()->with([
            'status' => 'recovery-codes-regenerated',
            'recovery_codes' => $recoveryCodes,
        ]);
    }
}
