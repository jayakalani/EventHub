<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $attendeeRoleId = UserRole::attendee()->id;

        // Permanently remove the previous dummy batch so phpMyAdmin is not filled
        // with soft-deleted Western names (email/nic prefixed deleted.{id}.).
        User::withTrashed()
            ->where(function ($query) {
                $query->where('email', 'like', 'attendee%@eventhub.lk')
                    ->orWhere('email', 'like', 'deleted.%.attendee%@eventhub.lk');
            })
            ->forceDelete();

        $districts = [
            'Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale',
            'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota', 'Jaffna',
            'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu', 'Batticaloa',
            'Ampara', 'Trincomalee', 'Kurunegala', 'Puttalam', 'Anuradhapura',
            'Polonnaruwa', 'Badulla', 'Monaragala', 'Ratnapura', 'Kegalle',
        ];

        $ageGroups = [
            [18, 25],
            [26, 35],
            [36, 50],
            [51, 65],
            [66, 80],
        ];

        $streets = [
            'Temple Road', 'Galle Road', 'Kandy Road', 'Lake Road', 'Station Road',
            'Hospital Road', 'Beach Road', 'Hill Street', 'Bauddhaloka Mawatha', 'Peradeniya Road',
        ];

        $prefixes = ['070', '071', '072', '074', '075', '076', '077', '078'];

        $maleFirstNames = [
            'Nuwan', 'Kasun', 'Chamara', 'Dilshan', 'Isuru',
            'Tharindu', 'Sachith', 'Pasindu', 'Dinesh', 'Lasith',
            'Ruwan', 'Amila', 'Gayan', 'Hasitha', 'Pradeep',
            'Sandun', 'Kavindu', 'Roshan', 'Chathura', 'Mahesh',
            'Lakshan', 'Naveen', 'Thusitha', 'Suresh', 'Arjun',
        ];

        $femaleFirstNames = [
            'Nadeesha', 'Thilini', 'Kavindi', 'Sanduni', 'Ishara',
            'Chathurika', 'Dilani', 'Hasini', 'Malsha', 'Nipuni',
            'Achini', 'Chamodi', 'Dinithi', 'Gayani', 'Hiruni',
            'Janani', 'Lakmali', 'Madushani', 'Pavithra', 'Tharushi',
            'Upeksha', 'Anjali', 'Priya', 'Nisha', 'Fathima',
        ];

        $lastNames = [
            'Perera', 'Fernando', 'Silva', 'Jayawardena', 'Bandara',
            'Wickramasinghe', 'Gunasekara', 'Dissanayake', 'Hettiarachchi', 'Weerasinghe',
            'Karunaratne', 'Rathnayake', 'Amarasinghe', 'Wijesinghe', 'Ekanayake',
            'Liyanage', 'Herath', 'Ranasinghe', 'Pathirana', 'Peiris',
            'Dias', 'Jayasuriya', 'Kulathunga', 'Nanayakkara', 'Mendis',
            'Samarakoon', 'Cooray', 'Atapattu', 'De Silva', 'Fonseka',
            'Senanayake', 'Abeysekara', 'Rajapaksa', 'Gamage', 'Seneviratne',
            'Kumara', 'Vadivel', 'Rajaratnam', 'Mohamed', 'Cassim',
            'Jayakody', 'Withanage', 'Bopearachchi', 'Tissera', 'Alwis',
            'Goonewardena', 'Kannangara', 'Balasubramaniam', 'Jegatheeswaran', 'Yusuf',
        ];

        for ($i = 0; $i < 50; $i++) {
            $gender = $i % 2 === 0 ? GenderEnum::Male : GenderEnum::Female;
            $nameIndex = intdiv($i, 2);
            $firstName = $gender === GenderEnum::Male
                ? $maleFirstNames[$nameIndex]
                : $femaleFirstNames[$nameIndex];
            $lastName = $lastNames[$i];

            [$minAge, $maxAge] = $ageGroups[$i % count($ageGroups)];
            $age = $minAge + ($i % ($maxAge - $minAge + 1));
            $dayOfYear = (($i * 17) % 365) + 1;
            $dob = Carbon::now()->subYears($age)->startOfYear()->addDays($dayOfYear - 1);

            $emailLocal = Str::lower($firstName).'.'.Str::lower(str_replace([' ', "'"], '', $lastName));

            User::updateOrCreate(
                ['email' => $emailLocal.'@eventhub.lk'],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'nic' => $this->sriLankanNic($dob, $gender, $i + 1),
                    'contact_number' => $prefixes[$i % count($prefixes)].sprintf('%07d', 2100000 + $i),
                    'date_of_birth' => $dob->format('Y-m-d'),
                    'address' => ($i + 12).' '.$streets[$i % count($streets)].', '.$districts[$i % count($districts)],
                    'gender' => $gender,
                    'role_id' => $attendeeRoleId,
                    'password' => Hash::make('12345678'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'profile_completed' => true,
                    'is_default_password_changed' => true,
                ]
            );
        }
    }

    /**
     * 12-digit NIC: YYYY + day-of-year (female +500) + 5-digit serial.
     * Example: 200167900767 → born 2001, day 179, female (679 = 179 + 500).
     */
    private function sriLankanNic(Carbon $dob, GenderEnum $gender, int $serial): string
    {
        $dayOfYear = (int) $dob->format('z') + 1;
        $dayCode = $gender === GenderEnum::Female ? $dayOfYear + 500 : $dayOfYear;

        return sprintf('%04d%03d%05d', (int) $dob->format('Y'), $dayCode, $serial);
    }
}
