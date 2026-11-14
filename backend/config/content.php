<?php

use App\Models\Award;
use App\Models\Chamber;
use App\Models\ChamberSchedule;
use App\Models\ConsultationStep;
use App\Models\DoctorProfile;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Faq;
use App\Models\FeaturedStatistic;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\SpecialistHighlight;
use App\Models\Video;

return [
    'modules' => [
        'Doctor profile' => DoctorProfile::class,
        'Site settings' => SiteSetting::class,
        'Media library' => MediaAsset::class,
        'Sliders' => Slider::class,
        'Featured statistics' => FeaturedStatistic::class,
        'Services' => Service::class,
        'Education' => Education::class,
        'Awards' => Award::class,
        'Gallery' => GalleryImage::class,
        'FAQs' => Faq::class,
        'YouTube videos' => Video::class,
        'Experience' => Experience::class,
        'Chambers' => Chamber::class,
        'Chamber schedules' => ChamberSchedule::class,
        'Consultation process' => ConsultationStep::class,
        'Specialist highlights' => SpecialistHighlight::class,
        'Reviews' => Review::class,
    ],
];
