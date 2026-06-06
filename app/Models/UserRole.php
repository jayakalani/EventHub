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
