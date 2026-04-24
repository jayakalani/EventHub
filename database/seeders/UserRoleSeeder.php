<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name_en' => 'admin', 'name_si' => 'පරිපාලක', 'is_active' => true],
            ['name_en' => 'event organizer', 'name_si' => 'උත්සව සංවිධායක', 'is_active' => true],
            ['name_en' => 'customer relations officer', 'name_si' => 'පාරිභෝගික සම්බන්ධතා නිලධාරී', 'is_active' => true],
            ['name_en' => 'attendee', 'name_si' => 'ප්‍රේක්ෂක', 'is_active' => true],
        ];

        foreach ($roles as $role) {
            UserRole::create($role);
        }
    }
}
