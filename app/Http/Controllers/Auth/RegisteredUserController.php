<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {

        $date = new Carbon();
        $before = $date->subYears(16)->format('Y-m-d');

        $message = [
            'before' => 'You must be 16 years or older to register',
        ];

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'nic' => ['required', 'string', 'max:16', 'unique:users'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'date_of_birth' => ['required', 'date', 'before:' . $before],
            'contact_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string',],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $role_id= UserRole::where('name_en', 'attendee')->first()->id;

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'nic' => $request->nic,
            'email' => $request->email,
            'role_id' => $role_id,
            'date_of_birth' => $request->date_of_birth,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
