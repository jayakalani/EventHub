<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleCompleteProfileRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuthLoginService;
use App\Support\TitleCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected AuthLoginService $authLogin
    ) {}

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Google sign-in was cancelled or failed. Please try again.');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                $user = $this->createGoogleUser($googleUser);
            }
        }

        return $this->authLogin->finalizeLogin(request(), $user);
    }

    public function showCompleteProfile(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->profile_completed) {
            return redirect()->route('dashboard');
        }

        return view('auth.google-complete-profile', ['user' => $user]);
    }

    public function storeCompleteProfile(GoogleCompleteProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_completed) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validated();

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'nic' => $validated['nic'],
            'contact_number' => $validated['contact_number'],
            'date_of_birth' => $validated['date_of_birth'],
            'address' => $validated['address'],
            'gender' => $validated['gender'],
            'profile_completed' => true,
        ]);

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', false);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->put('two_factor_verified', true);

        return redirect()->route('dashboard')
            ->with('welcome_back', true)
            ->with('success', 'Your profile has been completed. Welcome to EventHub!');
    }

    protected function createGoogleUser($googleUser): User
    {
        $nameParts = $this->splitName($googleUser->getName());
        $attendeeRole = UserRole::where('name_en', 'attendee')->first();

        return User::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'nic' => 'PENDING-'.$googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'contact_number' => '0000000000',
            'role_id' => $attendeeRole?->id,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
            'profile_completed' => false,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{first_name: string, last_name: string}
     */
    protected function splitName(?string $fullName): array
    {
        $fullName = trim($fullName ?? '');

        if ($fullName === '') {
            return ['first_name' => 'Google', 'last_name' => 'User'];
        }

        $parts = preg_split('/\s+/', $fullName, 2);

        return [
            'first_name' => TitleCase::format($parts[0]) ?? '',
            'last_name' => TitleCase::format($parts[1] ?? '') ?? '',
        ];
    }
}
