<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'provider@example.com'],
            [
                'name' => 'مقدم خدمة تجريبي',
                'phone' => '123456789',
                'password' => bcrypt('password'),
                'role' => 'provider',
                'is_banned' => false,
            ]
        );

        Provider::updateOrCreate(
            ['user_id' => $user->id],
            [
                'location_name' => 'دمشق',
                'latitude' => 33.5138,
                'longitude' => 36.2765,
                'work_type' => 'both',
                'main_service_id' => 1,
                'sub_service_id' => 1,
                'status' => 'approved',
                'profile_completed' => true,
                'is_available' => true,
                'min_price' => 50,
                'max_price' => 200,
                'currency' => 'USD',
                'work_start_time' => '09:00:00',
                'work_end_time' => '18:00:00',
                'overnight' => false,
                'about_me' => 'provider test',
                'id_photo_front' => 'providers/id_photos/default_front.jpg',
                'id_photo_back' => 'providers/id_photos/default_back.jpg',
            ]
        );
    }
}