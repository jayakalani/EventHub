<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cover',
        'is_active',
        'created_by',
    ];

    public function events()
    {
        return $this->hasMany(Event::class,'category_id');
    }
    
}
