<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;
use App\Enums\GenderEnum;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure admin role exists
        $adminRole = UserRole::firstOrCreate(
            ['name_en' => 'admin'],
            ['name_si' => 'පරිපාලක', 'is_active' => true]
        );

        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@eventhub.lk'],
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'nic' => '123456789V',
                'contact_number' => '0771234567',
                'date_of_birth' => '1990-01-01',
                'address' => 'Colombo',
                'gender' => GenderEnum::Male,
                'role_id' => $adminRole->id,
                'password' => Hash::make('12345678'), // default password
                'is_active' => true,
                'is_default_password_changed' => false,
            ]
        );
    }
}
