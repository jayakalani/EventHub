<?php

namespace App\Http\Controllers;

use App\Http\Middleware\FailedLoginAttempts;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuthLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class AuthController extends Controller
{
    public function __construct(
        protected AuthLoginService $authLogin
    ) {}

    /**
     * Show the login page.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Show the registration page.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Register a new attendee.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $attendeeRole = UserRole::firstOrCreate([
            'role_name' => UserRole::ATTENDEE,
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $attendeeRole->id,
        ]);

        return Redirect::route('login')
            ->with('success', 'Registration successful. Please log in.');
    }

    /**
     * Authenticate the user and redirect based on role.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Check if user account is locked
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->is_locked) {
            return back()->with('error', 'Your account is locked due to multiple failed login attempts. Please contact the administrator.');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Record failed login attempt
            FailedLoginAttempts::recordFailedAttempt($credentials['email']);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();
        Auth::logout();

        return $this->authLogin->finalizeLogin($request, $user, $request->boolean('remember'));
    }

    /**
     * Logout the current user.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget(['two_factor_verified', 'login.id', 'login.remember', 'two_factor_setup_secret']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login');
    }
}
