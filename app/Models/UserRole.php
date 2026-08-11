<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserRole extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Auditable;

    public const ADMIN = 'admin';
    public const ORGANIZER = 'event organizer';
    public const CRO = 'customer relations officer';
    public const ATTENDEE = 'attendee';

    /**
     * Staff roles that can be assigned when creating employees.
     *
     * @return list<string>
     */
    public static function staffRoleNames(): array
    {
        return [
            self::ADMIN,
            self::ORGANIZER,
            self::CRO,
        ];
    }

    /**
     * Roles an admin may assign when editing any user.
     *
     * @return list<string>
     */
    public static function assignableRoleNames(): array
    {
        return [
            ...self::staffRoleNames(),
            self::ATTENDEE,
        ];
    }

    /**
     * Active staff roles for employee create forms.
     */
    public static function activeStaffRoles()
    {
        return static::query()
            ->whereIn('name_en', self::staffRoleNames())
            ->where('is_active', true);
    }

    /**
     * Active roles available on the user edit form.
     */
    public static function assignableRoles()
    {
        return static::query()
            ->whereIn('name_en', self::assignableRoleNames())
            ->where('is_active', true);
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name_en',
        'name_si',
        'is_active',
    ];

    /**
     * Get the users assigned to this role.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
