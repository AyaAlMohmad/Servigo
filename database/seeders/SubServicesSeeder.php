<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\SubService;
use Illuminate\Database\Seeder;

class SubServicesSeeder extends Seeder
{
    public function run(): void
    {
        $cleaning = Service::where('name_en', 'Cleaning')->first();
        $plumbing = Service::where('name_en', 'Plumbing')->first();
        $electrical = Service::where('name_en', 'Electrical')->first();

        if (!$cleaning) {
            $cleaning = Service::create(['name_ar' => 'تنظيف', 'name_en' => 'Cleaning']);
        }
        if (!$plumbing) {
            $plumbing = Service::create(['name_ar' => 'سباكة', 'name_en' => 'Plumbing']);
        }
        if (!$electrical) {
            $electrical = Service::create(['name_ar' => 'كهرباء', 'name_en' => 'Electrical']);
        }

        SubService::updateOrCreate(
            ['service_id' => $cleaning->id, 'name_en' => 'House Cleaning'],
            ['name_ar' => 'تنظيف منازل', 'name_en' => 'House Cleaning']
        );
        SubService::updateOrCreate(
            ['service_id' => $cleaning->id, 'name_en' => 'Car Cleaning'],
            ['name_ar' => 'تنظيف سيارات', 'name_en' => 'Car Cleaning']
        );
        SubService::updateOrCreate(
            ['service_id' => $cleaning->id, 'name_en' => 'Office Cleaning'],
            ['name_ar' => 'تنظيف مكاتب', 'name_en' => 'Office Cleaning']
        );


        SubService::updateOrCreate(
            ['service_id' => $plumbing->id, 'name_en' => 'Pipe Repair'],
            ['name_ar' => 'إصلاح مواسير', 'name_en' => 'Pipe Repair']
        );
        SubService::updateOrCreate(
            ['service_id' => $plumbing->id, 'name_en' => 'Water Heater Installation'],
            ['name_ar' => 'تركيب سخانات مياه', 'name_en' => 'Water Heater Installation']
        );


        SubService::updateOrCreate(
            ['service_id' => $electrical->id, 'name_en' => 'Wiring'],
            ['name_ar' => 'أسلاك كهربائية', 'name_en' => 'Wiring']
        );
        SubService::updateOrCreate(
            ['service_id' => $electrical->id, 'name_en' => 'Lighting Installation'],
            ['name_ar' => 'تركيب إضاءة', 'name_en' => 'Lighting Installation']
        );
    }
}
