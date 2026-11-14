<?php

use App\Models\Award;
use App\Models\Chamber;
use App\Models\ConsultationStep;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Faq;
use App\Models\FeaturedStatistic;
use App\Models\GalleryImage;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\SpecialistHighlight;
use App\Models\Video;

return [
    'modules' => [
        'slider' => ['label' => 'Slider', 'icon' => 'images', 'model' => Slider::class, 'color' => 'purple', 'card' => 'Sliders'],
        'featured' => ['label' => 'Featured', 'icon' => 'star-fill', 'model' => FeaturedStatistic::class, 'color' => 'teal', 'card' => 'Features'],
        'service' => ['label' => 'Services', 'icon' => 'heart-pulse', 'model' => Service::class, 'color' => 'blue', 'card' => 'Services'],
        'review' => ['label' => 'Review', 'icon' => 'chat-square-quote', 'model' => Review::class, 'color' => 'amber', 'card' => 'Reviews'],
        'education' => ['label' => 'Education', 'icon' => 'mortarboard', 'model' => Education::class, 'color' => 'indigo', 'card' => 'Education'],
        'award' => ['label' => 'Award', 'icon' => 'award', 'model' => Award::class, 'color' => 'green', 'card' => 'Awards'],
        'gallery' => ['label' => 'Gallery', 'icon' => 'camera', 'model' => GalleryImage::class, 'color' => 'slate', 'card' => 'Gallery'],
        'faq' => ['label' => 'Faq', 'icon' => 'question-circle', 'model' => Faq::class, 'color' => 'cyan', 'card' => 'FAQs'],
        'youtube' => ['label' => 'Youtube Video', 'icon' => 'play-btn', 'model' => Video::class, 'color' => 'red', 'card' => 'Videos'],
        'experience' => ['label' => 'Experience', 'icon' => 'briefcase', 'model' => Experience::class, 'color' => 'pink', 'card' => 'Experience'],
        'chamber' => ['label' => 'Chamber', 'icon' => 'geo-alt', 'model' => Chamber::class, 'color' => 'violet', 'card' => 'Chambers'],
        'process' => ['label' => 'Process', 'icon' => 'gear', 'model' => ConsultationStep::class],
        'specialist' => ['label' => 'Specialist', 'icon' => 'person-badge', 'model' => SpecialistHighlight::class, 'color' => 'orange', 'card' => 'Specialists'],
        'setting' => ['label' => 'Settings', 'icon' => 'sliders', 'model' => SiteSetting::class],
    ],
    'cards' => ['slider', 'service', 'award', 'review', 'youtube', 'experience', 'chamber', 'faq', 'gallery', 'featured', 'education', 'specialist'],
];
