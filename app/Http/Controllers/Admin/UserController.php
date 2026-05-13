<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('userRole', function($q) use ($request) {
                $q->where('name_en', $request->role);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', 1);
                    break;
                case 'inactive':
                    $query->where('is_active', 0);
                    break;
                case 'lock':
                    $query->where('is_locked', 1);
                    break;
                case 'unlocked':
                    $query->where('is_locked', 0);
                    break;
            }
        }


        // Email state filter
        if ($request->filled('email_state')) {
            $query->where('email_verified', $request->email_state === 'yes');
        }

        // Date range filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $users = $query->paginate(10)->appends($request->all());

        return view('admin.users.index', compact('users'));

    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = UserRole::all();
        return view('admin.users.user-edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:user_roles,id'],
        ]);

        $user->update($validated);

        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been updated.");
    }

    /**
     * Toggle user lock status (lock/unlock).
     */
    public function toggleLock(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'is_locked' => !$user->is_locked,
            'failed_attempts' => $user->is_locked ? 0 : $user->failed_attempts,
            'locked_until' => null,
        ]);

        $status = $user->is_locked ? 'locked' : 'unlocked';
        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been {$status}.");
    }

    /**
     * Toggle user active status (activate/inactivate).
     */
    public function toggleActive(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been {$status}.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $user->delete();

        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been deleted.");
    }
}
