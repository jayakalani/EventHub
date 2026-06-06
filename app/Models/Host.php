<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;

class Host extends Model
{
    use HasFactory, Notifiable, Auditable;

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
        return $this->hasMany(Event::class,'hosted_by');
    }
}
