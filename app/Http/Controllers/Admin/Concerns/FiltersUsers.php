<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait FiltersUsers
{
    /**
     * Apply shared user-list filters (search, role, account, lock, email, dates).
     */
    protected function filteredUsersQuery(Request $request): Builder
    {
        $query = User::query()->with('userRole');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('userRole', function ($q) use ($request) {
                $q->where('name_en', $request->role);
            });
        }

        $accountStatus = $request->input('account_status');
        $lockStatus = $request->input('lock_status');

        if (! $accountStatus && ! $lockStatus && $request->filled('status')) {
            match ($request->status) {
                'active', 'inactive' => $accountStatus = $request->status,
                'lock' => $lockStatus = 'locked',
                'unlocked' => $lockStatus = 'unlocked',
                default => null,
            };
        }

        if ($accountStatus === 'active') {
            $query->where('is_active', 1);
        } elseif ($accountStatus === 'inactive') {
            $query->where('is_active', 0);
        }

        if ($lockStatus === 'locked') {
            $query->where('is_locked', 1);
        } elseif ($lockStatus === 'unlocked') {
            $query->where('is_locked', 0);
        }

        if ($request->filled('email_state')) {
            if ($request->email_state === 'yes') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if ($request->boolean('staff_only')) {
            $query->whereHas('userRole', function ($q) {
                $q->whereIn('name_en', UserRole::staffRoleNames());
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return $query;
    }

    protected function usersHaveActiveFilters(Request $request): bool
    {
        return $request->filled('search')
            || $request->filled('role')
            || $request->filled('account_status')
            || $request->filled('lock_status')
            || $request->filled('status')
            || $request->filled('email_state')
            || $request->boolean('staff_only')
            || $request->filled('from_date')
            || $request->filled('to_date');
    }
}
