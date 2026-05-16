<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class Host extends Model
{
    use HasFactory;

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
