<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Host;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserRoleChangeService
{
    public function __construct(
        protected WalletService $wallets,
    ) {}

    /**
     * @return array{
     *     organizerAssets: int,
     *     croAssets: int,
     *     otherOrganizers: Collection<int, User>,
     *     otherCros: Collection<int, User>
     * }
     */
    public function formContext(User $user): array
    {
        return [
            'organizerAssets' => $this->organizerAssetCount($user),
            'croAssets' => $this->croAssetCount($user),
            'otherOrganizers' => $this->eligibleStaff($user, UserRole::ORGANIZER),
            'otherCros' => $this->eligibleStaff($user, UserRole::CRO),
        ];
    }

    public function organizerAssetCount(User $user): int
    {
        return Event::withTrashed()->where('created_by', $user->id)->count()
            + Host::query()->where('created_by', $user->id)->count()
            + Artist::query()->where('created_by', $user->id)->count();
    }

    public function croAssetCount(User $user): int
    {
        return Event::withTrashed()->where('contact_person', $user->id)->count()
            + Complaint::query()->where('assigned_to', $user->id)->count()
            + Inquiry::query()->where('assigned_to', $user->id)->count();
    }

    public function apply(User $user, UserRole $newRole, ?int $reassignOrganizerId, ?int $reassignCroId): void
    {
        $oldName = $user->userRole?->name_en;
        $newName = $newRole->name_en;

        if ($oldName === $newName) {
            return;
        }

        if ($oldName === UserRole::ORGANIZER && $this->organizerAssetCount($user) > 0) {
            $this->assertEligibleStaff($reassignOrganizerId, $user, UserRole::ORGANIZER, 'reassign_organizer_id');
            $this->reassignOrganizerAssets($user, (int) $reassignOrganizerId);
        }

        if ($oldName === UserRole::CRO && $this->croAssetCount($user) > 0) {
            $this->assertEligibleStaff($reassignCroId, $user, UserRole::CRO, 'reassign_cro_id');
            $this->reassignCroAssets($user, (int) $reassignCroId);
        }

        if ($newName === UserRole::ORGANIZER) {
            $this->wallets->getOrCreateWallet($user);
        }
    }

    public function ensureOrganizerWallet(User $user): void
    {
        $user->loadMissing('userRole');

        if ($user->userRole?->name_en === UserRole::ORGANIZER) {
            $this->wallets->getOrCreateWallet($user);
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleStaff(User $except, string $roleName): Collection
    {
        return User::query()
            ->with('userRole')
            ->where('id', '!=', $except->id)
            ->where('is_active', true)
            ->where('is_locked', false)
            ->whereHas('userRole', fn ($query) => $query->where('name_en', $roleName))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function assertEligibleStaff(?int $targetId, User $except, string $roleName, string $field): void
    {
        if (! $targetId || ! $this->eligibleStaff($except, $roleName)->contains('id', $targetId)) {
            $label = $roleName === UserRole::ORGANIZER ? 'organizer' : 'CRO';

            throw ValidationException::withMessages([
                $field => "Select another active {$label} to take over this user's records before changing the role.",
            ]);
        }
    }

    private function reassignOrganizerAssets(User $user, int $organizerId): void
    {
        Event::withTrashed()->where('created_by', $user->id)->update(['created_by' => $organizerId]);
        Host::query()->where('created_by', $user->id)->update(['created_by' => $organizerId]);
        Artist::query()->where('created_by', $user->id)->update(['created_by' => $organizerId]);
    }

    private function reassignCroAssets(User $user, int $croId): void
    {
        Event::withTrashed()->where('contact_person', $user->id)->update(['contact_person' => $croId]);
        Complaint::query()->where('assigned_to', $user->id)->update(['assigned_to' => $croId]);
        Inquiry::query()->where('assigned_to', $user->id)->update(['assigned_to' => $croId]);
    }
}
