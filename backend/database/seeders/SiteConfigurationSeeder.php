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
            'name' => 'Dr. Farzana Rahman Shathi',
            'designation' => 'Assistant Professor, Dermatology',
            'credentials' => [],
        ]);

        SiteSetting::firstOrCreate(['key' => 'primary'], [
            'site_title' => 'Dr. Farzana Rahman Shathi',
            'social_links' => [],
        ]);
    }
}
