<?php

namespace App\Models;

use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Host extends Model
{
    use Auditable, HasFactory, HasTitleCaseAttributes, Notifiable;

    /**
     * @var list<string>
     */
    protected array $titleCase = [
        'name',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'cover',
        'created_by',
        'is_active',
    ];

    public function events()
    {
        return $this->hasMany(Event::class, 'host_id');
    }
}
