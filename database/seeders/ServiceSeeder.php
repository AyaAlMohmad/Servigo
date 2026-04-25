<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;  // <-- Add this import

class ServiceSeeder extends Seeder
{
    public function run()
    {
        Service::create([
            'name_ar' => 'تنظيف',
            'name_en' => 'Cleaning',
            'photo' => 'https://api.servigo.com/storage/services/cleaning.jpg'
        ]);

        // Add more services as needed
        Service::create([
            'name_ar' => 'سباكة',
            'name_en' => 'Plumbing',
            'photo' => 'https://api.servigo.com/storage/services/plumbing.jpg'
        ]);

        Service::create([
            'name_ar' => 'كهرباء',
            'name_en' => 'Electrical',
            'photo' => 'https://api.servigo.com/storage/services/electrical.jpg'
        ]);
    }
}