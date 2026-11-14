<?php

namespace Database\Seeders;

use App\Models\DoctorProfile;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DoctorProfile::firstOrCreate(['key' => 'primary'], [
            'name' => 'Dr. Runa Akhter Dola',
            'designation' => 'Asst. Prof.',
            'credentials' => [],
        ]);

        SiteSetting::firstOrCreate(['key' => 'primary'], [
            'site_title' => 'Dr. Runa Akhter Dola',
            'social_links' => [],
        ]);
    }
}
