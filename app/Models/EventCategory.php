<?php

namespace App\Models;

use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCategory extends Model
{
    use Auditable, HasFactory, HasTitleCaseAttributes;

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
        'cover',
        'is_active',
        'allows_artists',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allows_artists' => 'boolean',
        ];
    }

    public function allowsArtists(): bool
    {
        return (bool) $this->allows_artists;
    }

    public function events()
    {
        return $this->hasMany(Event::class,'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
}
