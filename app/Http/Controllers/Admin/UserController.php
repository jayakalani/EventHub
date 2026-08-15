<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersUsers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Services\UserRoleChangeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    use FiltersUsers;

    public function __construct(
        protected UserRoleChangeService $roleChange,
    ) {}

    /**
     * Display a listing of all users.
     */
    public function index(Request $request): View
    {
        $query = $this->filteredUsersQuery($request);

        $stats = [
            'matched' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'inactive' => (clone $query)->where('is_active', false)->count(),
            'locked' => (clone $query)->where('is_locked', true)->count(),
        ];

        $users = $query->latest()->paginate(10)->appends($request->query());

        $hasActiveFilters = $this->usersHaveActiveFilters($request);

        return view('admin.users.index', compact('users', 'stats', 'hasActiveFilters'));
    }

    /**
     * Export filtered users (all roles, including attendees) as CSV.
     */
    public function exportCsv(Request $request)
    {
        $users = $this->filteredUsersQuery($request)->latest()->get();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Email', 'Contact Number', 'Role', 'Is Locked', 'Is Active'];

        foreach ($users as $user) {
            $csvData[] = [
                $user->id,
                $user->first_name.' '.$user->last_name,
                $user->email,
                $user->contact_number,
                $user->userRole->name_en ?? '',
                $user->is_locked ? 'Locked' : 'Unlocked',
                $user->is_active ? 'Active' : 'Inactive',
            ];
        }

        $filename = 'users_'.now()->format('Ymd_His').'.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Export filtered users (all roles, including attendees) as PDF.
     */
    public function exportPdf(Request $request)
    {
        $users = $this->filteredUsersQuery($request)->latest()->get();

        $pdf = Pdf::loadView('admin.exports.users_pdf', compact('users'));

        return $pdf->download('users_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id): View
    {
        $user = User::with('userRole')->findOrFail($id);

        $roles = UserRole::assignableRoles()->orderBy('name_en')->get();

        // Keep the current role selectable if it was deactivated.
        if ($user->userRole && ! $roles->contains('id', $user->role_id)) {
            $roles->push($user->userRole);
        }

        $roleChangeLocked = $user->adminProtectionError('demote') !== null;
        $emailChangeLocked = $user->adminProtectionError('change-email') !== null;
        $roleChangeContext = $this->roleChange->formContext($user);

        return view('admin.users.user-edit', compact(
            'user',
            'roles',
            'roleChangeLocked',
            'emailChangeLocked',
        ) + $roleChangeContext);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = User::with('userRole')->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at'),
            ],
            'contact_number' => ['required', 'string', 'max:20'],
            'role_id' => [
                'required',
                Rule::exists('user_roles', 'id')->where(function ($query) use ($user) {
                    $query->whereNull('deleted_at')
                        ->where(function ($inner) use ($user) {
                            $inner->where(function ($assignable) {
                                $assignable->whereIn('name_en', UserRole::assignableRoleNames())
                                    ->where('is_active', true);
                            })->orWhere('id', $user->role_id);
                        });
                }),
            ],
            'reassign_organizer_id' => ['nullable', 'integer'],
            'reassign_cro_id' => ['nullable', 'integer'],
        ]);

        $newRole = UserRole::query()->find((int) $validated['role_id']);
        $isDemotingAdmin = $user->isAdmin()
            && $newRole
            && $newRole->name_en !== UserRole::ADMIN;

        if ($isDemotingAdmin) {
            if ($response = $this->denyProtectedAdminAction($user, 'demote')) {
                return $response;
            }
        }

        $user->fill(Arr::only($validated, [
            'first_name',
            'last_name',
            'email',
            'contact_number',
            'role_id',
        ]));

        $emailChanged = $user->isDirty('email');
        $roleChanged = $user->isDirty('role_id');

        if ($emailChanged) {
            if ($response = $this->denyProtectedAdminAction($user, 'change-email')) {
                return $response;
            }

            $user->email_verified_at = null;
        }

        DB::transaction(function () use ($user, $newRole, $roleChanged, $validated) {
            if ($roleChanged && $newRole) {
                $this->roleChange->apply(
                    $user,
                    $newRole,
                    ! empty($validated['reassign_organizer_id']) ? (int) $validated['reassign_organizer_id'] : null,
                    ! empty($validated['reassign_cro_id']) ? (int) $validated['reassign_cro_id'] : null,
                );
            }

            $user->save();
        });

        if ($roleChanged) {
            $user->invalidateSessions();
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return Redirect::route('admin.user.edit', $user->id)->with(
                'success',
                "User {$user->first_name} {$user->last_name} has been updated. Email verification was reset and a verification link was sent."
            );
        }

        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been updated.");
    }

    /**
     * Resend the email verification notification.
     */
    public function resendVerification(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return Redirect::route('admin.user.edit', $user->id)
                ->with('success', 'This email is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return Redirect::route('admin.user.edit', $user->id)
            ->with('success', "Verification email sent to {$user->email}.");
    }

    /**
     * Mark the user's email as verified (admin override).
     */
    public function markVerified(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return Redirect::route('admin.user.edit', $user->id)
                ->with('success', 'This email is already verified.');
        }

        $user->markEmailAsVerified();

        return Redirect::route('admin.user.edit', $user->id)
            ->with('success', "Email for {$user->first_name} {$user->last_name} has been marked as verified.");
    }

    /**
     * Toggle user lock status (lock/unlock).
     */
    public function toggleLock(Request $request, $id): RedirectResponse
    {
        $user = User::with('userRole')->findOrFail($id);

        if ($response = $this->denyProtectedAdminAction($user, 'lock')) {
            return $response;
        }

        $user->update([
            'is_locked' => ! $user->is_locked,
            'failed_attempts' => $user->is_locked ? 0 : $user->failed_attempts,
            'locked_until' => null,
        ]);

        if ($user->is_locked) {
            $user->invalidateSessions();
        }

        $status = $user->is_locked ? 'locked' : 'unlocked';

        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been {$status}.");
    }

    /**
     * Toggle user active status (activate/inactivate).
     */
    public function toggleActive(Request $request, $id): RedirectResponse
    {
        $user = User::with('userRole')->findOrFail($id);

        if ($response = $this->denyProtectedAdminAction($user, 'deactivate')) {
            return $response;
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        if (! $user->is_active) {
            $user->invalidateSessions();
        }

        $status = $user->is_active ? 'activated' : 'deactivated';

        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been {$status}.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = User::with('userRole')->findOrFail($id);

        if ($response = $this->denyProtectedAdminAction($user, 'delete')) {
            return $response;
        }

        $user->invalidateSessions();
        $user->delete();

        return Redirect::route('admin.users')->with('success', "User {$user->first_name} {$user->last_name} has been deleted.");
    }

    /**
     * @param  'lock'|'deactivate'|'delete'|'demote'|'change-email'  $action
     */
    private function denyProtectedAdminAction(User $user, string $action): ?RedirectResponse
    {
        $message = $user->adminProtectionError($action);

        if ($message === null) {
            return null;
        }

        $fallback = in_array($action, ['demote', 'change-email'], true)
            ? route('admin.user.edit', $user->id)
            : route('admin.users');

        return Redirect::back(fallback: $fallback)->with('error', $message);
    }
}
