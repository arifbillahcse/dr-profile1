<?php

use App\Models\Award;
use App\Models\DoctorProfile;
use App\Models\Education;
use App\Models\Experience;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\Video;

return [
    'references' => [
        'Doctor profile' => ['model' => DoctorProfile::class, 'columns' => ['portrait_image_id', 'first_image_id', 'second_image_id', 'tall_image_id']],
        'Site settings' => ['model' => SiteSetting::class, 'columns' => ['logo_image_id', 'favicon_image_id', 'social_image_id']],
        'Sliders' => ['model' => Slider::class, 'columns' => ['image_id']],
        'Services' => ['model' => Service::class, 'columns' => ['image_id']],
        'Education' => ['model' => Education::class, 'columns' => ['image_id']],
        'Awards' => ['model' => Award::class, 'columns' => ['image_id']],
        'Gallery' => ['model' => GalleryImage::class, 'columns' => ['image_id']],
        'Videos' => ['model' => Video::class, 'columns' => ['thumbnail_image_id']],
        'Experience' => ['model' => Experience::class, 'columns' => ['image_id']],
    ],
];
