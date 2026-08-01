<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->nic = $validated['nic'];
        $user->email = $validated['email'];
        $user->contact_number = $validated['contact_number'];
        $user->date_of_birth = $validated['date_of_birth'];
        $user->address = $validated['address'];
        $user->gender = $validated['gender'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $fileName = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/users-profile-photos'), $fileName);

            if ($user->profile_photo) {
                $oldPath = public_path('uploads/users-profile-photos/'.$user->profile_photo);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $user->profile_photo = $fileName;
        }

        $user->save();
        $user->refresh();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
