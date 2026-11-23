<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Chamber;
use App\Models\ConsultationStep;
use App\Models\DoctorProfile;
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
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function __invoke(Request $request): View
    {
        $page = $request->route()->defaults['page'] ?? 'home';
        $site = SiteSetting::with(['logo', 'favicon', 'socialImage'])->where('key', 'primary')->first() ?? new SiteSetting(['site_title' => config('app.name'), 'social_links' => []]);
        $profile = DoctorProfile::with(['portrait', 'firstImage', 'secondImage', 'tallImage'])->where('key', 'primary')->first() ?? new DoctorProfile(['name' => config('app.name'), 'credentials' => []]);
        $statistics = FeaturedStatistic::published()->ordered()->get();
        $dial = function (?string $number): ?string {
            if (preg_match('/^(.+\\d{4,})-\\d{1,2}$/', $number ?? '', $range)) {
                $number = $range[1];
            }
            $digits = preg_replace('/[^+0-9]/', '', $number ?? '');

            return strlen($digits) >= 5 ? 'tel:'.$digits : null;
        };
        $videos = Video::published()->ordered()->with('thumbnail')->get()->filter(fn (Video $video): bool => (bool) preg_match('/^[A-Za-z0-9_-]{11}$/D', $video->youtube_id));
        if (! preg_match('/^[A-Za-z0-9_-]{11}$/D', $profile->introduction_youtube_id ?? '')) {
            $profile->introduction_youtube_id = null;
        }
        $introductionVideoId = $profile->introduction_youtube_id ?: $videos->first()?->youtube_id;
        $introductionVideoTitle = $profile->introduction_youtube_id ? 'Introduction Video' : $videos->first()?->title;

        return view('public.'.$page, [
            'page' => $page, 'site' => $site, 'profile' => $profile, 'dial' => $dial, 'phoneLink' => $dial($site->phone),
            'qualifications' => collect($profile->credentials ?? [])->reject(fn (string $credential): bool => str_starts_with($credential, 'Member of'))->values(),
            'memberships' => collect($profile->credentials ?? [])->filter(fn (string $credential): bool => str_starts_with($credential, 'Member of'))->values(),
            'sliders' => Slider::published()->ordered()->with('image')->whereHas('image')->get(),
            'statistics' => $statistics,
            'experienceStat' => $statistics->first(fn ($stat): bool => str_contains(strtolower($stat->label), 'year')),
            'educations' => Education::published()->ordered()->with('image')->get(),
            'awards' => Award::published()->ordered()->with('image')->get(),
            'services' => Service::published()->ordered()->with('image')->get(),
            'specialists' => SpecialistHighlight::published()->ordered()->get(),
            'steps' => ConsultationStep::published()->ordered()->get(),
            'chambers' => Chamber::published()->ordered()->with(['schedules' => fn ($query) => $query->where('is_active', true)])->get(),
            'videos' => $videos,
            'introductionVideoId' => $introductionVideoId,
            'introductionVideoTitle' => $introductionVideoTitle,
            'experiences' => Experience::published()->ordered()->with('image')->get(),
            'faqs' => Faq::published()->ordered()->get(),
            'gallery' => GalleryImage::published()->ordered()->with('image')->whereHas('image')->get(),
            'reviews' => Review::approved()->latest('id')->get(),
        ]);
    }
}
