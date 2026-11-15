@extends('public.layout')
@section('content')
<main id="main-content">

    <section class="overflow-hidden hero-section position-relative" id="home" itemscope="" itemtype="https://schema.org/Physician">
    <meta itemprop="name" content="{{ $profile->name }}">
    <meta itemprop="medicalSpecialty" content="{{ $profile->specialty }}">
    <meta itemprop="address" content="{{ $site->address }}">

    <div class="hero-bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="container position-relative" style="z-index: 5;">
        <div class="row align-items-center g-5">

            <div class="text-center col-lg-5 col-md-12 text-lg-start hero_top">
                <div class="hero-content" data-aos="fade-right">

                    

                    <div class="hero-header"><p class="hero-eyebrow"><span></span> CLINICAL &amp; AESTHETIC DERMATOLOGY</p>
                        <h1 class="mb-2 text-white hero-title fw-bold">
                            <span class="opacity-75 d-block specialist-label fs-5 fw-normal">{{ $profile->designation }}</span>
                            <span class="mt-1 doctor-name display-4 d-block" style="color: #ffb703;">{{ $profile->name }}</span>
                        </h1>
                        <div class="mb-4 degree-box">
                            <div class="border-4 border-start-lg border-warning">

                                <div class="flex-wrap gap-2 mt-2 d-flex">
                                                                            @foreach($profile->credentials ?? [] as $credential)<div class="luxury-degree-badge">
                                            <i class="fas fa-certificate text-warning me-2 small"></i>
                                            <span class="degree-text">{{ $credential }}</span>
                                        </div>@endforeach
                                                                            
                                                                            
                                                                            
                                                                    </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 mobile-hero-slider d-lg-none">
                        <div class="overflow-hidden border border-white shadow-lg swiper heroSwiperMobile rounded-4 border-opacity-10">
                            <div class="swiper-wrapper">
                                                                    @foreach($sliders as $slide)<div class="swiper-slide">
                                        @if($slide->image)<img src="{{ $slide->image->url() }}" class="img-fluid" alt="{{ $slide->image->alt_text ?? $slide->title ?? $profile->name }}" loading="eager" fetchpriority="high" decoding="async">@endif
                                    </div>@endforeach
                                                                    
                                                            </div>
                            <div class="swiper-pagination"></div>
                        </div>
                        @if(filled($profile->trusted_patients))<div class="shadow-lg trust-card-luxury animate-bounce-slow">
                        <div class="gap-3 d-flex align-items-center">
                            <div class="rating-box">
                                <h4 class="mb-0 fw-bold h5 text-dark">{{ $profile->trusted_patients }}</h4>
                                <div class="star-rating small text-warning">★★★★★</div>
                            </div>
                            <div class="avatar-stack pe-2 border-start ps-3">
                                <div class="avatar-group">
                                    <img src="{{ asset('site/images/69bdf6be6b18-100.jpg') }}" class="avatar-item" alt="{{ $profile->name }}" loading="lazy">
                                    <img src="{{ asset('site/images/16318a50a0ca-100.jpg') }}" class="avatar-item" alt="{{ $profile->name }}" loading="lazy">
                                    <div class="count-badge-luxury">+</div>
                                </div>
                                <p class="mb-0 x-small text-muted fw-bold">Trusted by Patients</p>
                            </div>
                        </div>
                    </div>@endif
                    </div>

                    <p class="mb-4 hero-description text-white-50 lead fs-6 pe-lg-4 public-copy" itemprop="description">{{ $profile->summary }}</p>
                    <p></p>

                    <div class="flex-wrap gap-2 mt-4 hero-buttons d-flex justify-content-center justify-content-lg-start">
                        @if($phoneLink)<a href="{{ $phoneLink }}" class="px-4 py-3 shadow-lg btn btn-appointment-luxury" aria-label="Call the practice">
                            <i class="fas fa-phone-alt me-2 animate-phone"></i> Call Now
                        </a>@endif
                        <a href="{{ route('public.services') }}" class="px-4 py-3 btn btn-outline-gold rounded-pill fw-bold">
                            View Services <i class="fas fa-chevron-right ms-2 small"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 d-none d-lg-block" data-aos="zoom-in">
                <div class="hero-swiper-wrapper position-relative">
                    <div class="overflow-hidden border border-white shadow-2xl swiper heroSwiper rounded-5 border-opacity-10">
                        <div class="swiper-wrapper">
                                                            @foreach($sliders as $slide)<div class="swiper-slide hero-slide">
                                    @if($slide->image)<img src="{{ $slide->image->url() }}" class="img-fluid hero-img" alt="{{ $slide->image->alt_text ?? $slide->title ?? $profile->name }}" loading="eager" fetchpriority="high" decoding="async">@endif
                                    <div class="slide-overlay"></div>
                                </div>@endforeach
                                                            
                                                    </div>
                        <div class="swiper-pagination"></div>
                    </div>

                    @if(filled($profile->trusted_patients))<div class="shadow-lg trust-card-luxury animate-bounce-slow">
                        <div class="gap-3 d-flex align-items-center">
                            <div class="rating-box">
                                <h4 class="mb-0 fw-bold h5 text-dark">{{ $profile->trusted_patients }}</h4>
                                <div class="star-rating small text-warning">★★★★★</div>
                            </div>
                            <div class="avatar-stack pe-2 border-start ps-3">
                                <div class="avatar-group">
                                    <img src="{{ asset('site/images/69bdf6be6b18-100.jpg') }}" class="avatar-item" alt="{{ $profile->name }}" loading="lazy">
                                    <img src="{{ asset('site/images/16318a50a0ca-100.jpg') }}" class="avatar-item" alt="{{ $profile->name }}" loading="lazy">
                                    <div class="count-badge-luxury">+</div>
                                </div>
                                <p class="mb-0 x-small text-muted fw-bold">Trusted by Patients</p>
                            </div>
                        </div>
                    </div>@endif
                </div>
            </div>
        </div>
    </div>
</section>



    @if($statistics->isNotEmpty())<section class="py-5 overflow-hidden feature-section position-relative" aria-labelledby="feature-heading" itemscope="" itemtype="https://schema.org/AboutPage" style="background-color: #f8fdfb;">
    <div class="container">
        <h2 id="feature-heading" class="visually-hidden">{{ $profile->name }} - Medical Expertise and Patient Success Milestones</h2>

        <div class="row g-4">
                            @foreach($statistics as $stat)<article class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="0" itemprop="mainEntity" itemscope="" itemtype="https://schema.org/PropertyValue">

                    <div class="p-4 border-0 shadow-sm feature-card h-100 infinite-float" style="animation-delay: 0s">

                        <div class="mb-3 icon-circle-custom d-flex align-items-center justify-content-center" aria-hidden="true"><i class="{{ $stat->icon }}" aria-hidden="true"></i></div>

                        <div class="feature-content">
                            <h3 class="mb-1 feature-number h2 fw-bold text-dark-green" itemprop="value">{{ $stat->value.$stat->suffix }}</h3>

                            <p class="mb-2 feature-label text-dark fw-bold" itemprop="name">{{ $stat->label }}</p>
                        </div>


                        <div class="mt-3 card-indicator-premium bg-success"></div>
                    </div>
                </article>@endforeach
                            
                            
                    </div>
    </div>
</section>@endif




    @if($educations->isNotEmpty() || $awards->isNotEmpty())<section class="py-5 overflow-hidden qualification-award-area position-relative" id="qualification" style="background-color: #f8fafc;">
    <div class="medical-watermark">
        <i class="fas fa-microscope"></i>
    </div>

    <div class="container position-relative" style="z-index: 2;">
        <header class="mb-5 text-center" data-aos="fade-up">
            <span class="mb-2 text-success fw-bold text-uppercase ls-2 small d-block">Academic Journey</span>
            <h2 class="section-title">Expertise &amp; <em>Certifications</em></h2>
            <div class="mx-auto mt-2 premium-underline"></div>
        </header>

        <div class="row g-4">

            @if($educations->isNotEmpty())<div class="{{ $awards->isNotEmpty() ? 'col-lg-7' : 'col-12' }}" data-aos="fade-right">
                <div class="p-4 bg-white shadow-sm h-100 rounded-5 border-top-premium">
                    <h4 class="mb-4 h5 fw-bold text-dark d-flex align-items-center">
                        <span class="icon-circle-sm me-3"><i class="fas fa-graduation-cap"></i></span>
                        Academic Excellence
                    </h4>

                    <div class="qualification-items {{ $awards->isEmpty() ? 'qualification-grid' : '' }}">
                                                    @foreach($educations as $education)<div class="mb-3 q-card-step d-flex align-items-center">
                                <div class="shadow-xs q-logo-wrapper me-3">
                                    @if($education->image)<img src="{{ $education->image->url() }}" alt="{{ $education->qualification }}" class="img-fluid" loading="lazy">@else<i class="fas fa-graduation-cap text-success" aria-hidden="true"></i>@endif
                                </div>

                                <div class="q-content flex-grow-1">
                                    <h5 class="mb-1 fw-bold text-dark institution-name">{{ $education->qualification }}</h5>
                                    <div class="flex-wrap gap-3 d-flex align-items-center">
                                        @if($education->year)<span class="text-success session-text fw-semibold"><i class="far fa-calendar-check me-1"></i>{{ $education->year }}</span>@endif
                                        <span class="degree-badge-inline fw-bold text-dark-emphasis">{{ $education->description ?: $education->institution }}</span>
                                    </div>
                                </div>
                            </div>@endforeach
                                                    
                                                    
                                                    
                                                    
                                                    
                                            </div>
                </div>
            </div>

            @endif
            @if($awards->isNotEmpty())<div class="{{ $educations->isNotEmpty() ? 'col-lg-5' : 'col-12' }}" data-aos="fade-left">
                <div class="p-4 bg-white shadow-sm h-100 rounded-5 border-top-premium">
                    <h4 class="mb-4 h5 fw-bold text-dark d-flex align-items-center">
                        <span class="icon-circle-sm me-3"><i class="fas fa-award"></i></span>
                        Special Recognition
                    </h4>

                    <div class="award-display-box">
                        <div class="mb-4 tab-content" id="awardContent">
                                                            @foreach($awards as $award)<div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ 'award-'.$award->id }}" role="tabpanel">
                                    <div class="gap-3 d-flex align-items-start">
                                        <div class="shadow-sm award-thumb-main">
                                            @if($award->image)<img src="{{ $award->image->url() }}" alt="{{ $award->title }}" class="img-fluid" loading="lazy">@endif
                                        </div>
                                        <div>
                                            <h3 class="mb-1 h6 fw-bold text-dark award-title">{{ $award->title }}</h3>@if($award->organization)<p class="small text-muted mb-1">{{ $award->organization }}</p>@endif
                                            <p class="mb-0 italic text-secondary award-desc">{{ $award->description }}</p>
                                        </div>
                                    </div>
                                </div>@endforeach
                                                    </div>

                        <ul class="gap-2 mt-auto nav nav-pills justify-content-start" id="awardTabs" role="tablist">
                                                            @foreach($awards as $award)<li class="nav-item">
                                    <button class="nav-link p-1 award-nav-btn {{ $loop->first ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="{{ '#award-'.$award->id }}" type="button">
                                        @if($award->image)<img src="{{ $award->image->url() }}" class="rounded shadow-xs img-fluid" style="width: 38px; height: 38px; object-fit: contain;" alt="{{ $award->title }}" loading="lazy">@endif
                                    </button>
                                </li>@endforeach
                                                    </ul>
                    </div>
                </div>
            </div>
        @endif</div>
    </div>
</section>@endif




    @if($services->isNotEmpty())
<section class="care-services" id="services" aria-labelledby="care-services-title">
    <div class="container">
        <div class="care-services-heading">
            <div>
                <span class="care-eyebrow"><span aria-hidden="true"></span> EXPERT HEALTHCARE SOLUTIONS</span>
                <h2 id="care-services-title" class="section-title">Specialized<br><em>Medical Services.</em></h2>
            </div>
            <div class="care-services-intro">
                <p>Explore care for your skin, hair and aesthetic concerns. Start with a consultation to discuss the options suited to you.</p>
                <a href="{{ route('public.services') }}">Explore all services <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="care-services-grid">
            @foreach($services as $service)
            <article class="care-service" id="{{ 'service-'.$service->id }}">
                <div class="care-service-top">
                    <span class="care-service-icon" aria-hidden="true"><i class="{{ $service->icon ?: 'fas fa-stethoscope' }}"></i></span>
                    <span class="care-service-number" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <h3>{{ $service->name }}</h3>
                <p>{{ $service->description }}</p>
                <a class="care-service-link" href="{{ route('public.services') }}#service-{{ $service->id }}" aria-label="Explore {{ $service->name }}">Explore care <span aria-hidden="true">↗</span></a>
            </article>
            @endforeach
        </div>
        @if($phoneLink)
        <div class="care-consultation">
            <div class="care-consultation-copy">
                <span class="care-consultation-icon" aria-hidden="true"><i class="fas fa-comments"></i></span>
                <div><h3>Not sure where to start?</h3><p>Book a consultation with {{ $profile->name }}.</p></div>
            </div>
            <a href="{{ $phoneLink }}">Let’s talk about your care <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        @endif
    </div>
</section>
@endif




    @include('public.photo-story')
    <section class="py-5 overflow-hidden about-area position-relative" id="about" style="background: #fff url('{{ asset('site/images/a21b1277868e-about_bg.svg') }}') no-repeat center / cover;" itemscope="" itemtype="https://schema.org/Physician">

    <div class="about-bg-anim icon-1" aria-hidden="true"><i class="fas fa-hand-holding-heart"></i></div>
    <div class="about-bg-anim icon-2" aria-hidden="true"><i class="fas fa-stethoscope"></i></div>

    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="about-image-wrapper position-relative" data-aos="fade-right">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="mb-3 overflow-hidden shadow-sm img-hover-card rounded-4">
                                @if($profile->firstImage)<img src="{{ $profile->firstImage->url() }}" alt="{{ $profile->name }}" class="img-fluid w-100 transition-img" itemprop="image" loading="lazy">@endif
                            </div>
                            <div class="overflow-hidden shadow-sm img-hover-card rounded-4">
                                @if($profile->secondImage)<img src="{{ $profile->secondImage->url() }}" alt="{{ $profile->name }}" class="img-fluid w-100 transition-img" loading="lazy">@endif
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="overflow-hidden shadow-lg main-img-card rounded-4 h-100">
                                @if($profile->tallImage)<img src="{{ $profile->tallImage->url() }}" alt="{{ $profile->name }}" class="img-fluid w-100 h-100 object-fit-cover transition-img" loading="lazy">@endif
                            </div>
                        </div>
                    </div>

                    @if($profile->introduction_youtube_id)<div class="video-promotion-wrap">
                        <div class="circle-rotating-text">
                            <svg viewBox="0 0 100 100" width="155" height="155" class="rotating-svg">
                                <defs>
                                    <path id="circlePath" d="M 50, 50 m -37, 0 a 37,37 0 1,1 74,0 a 37,37 0 1,1 -74,0 "></path>
                                </defs>
                                <text fill="#198754" font-weight="bold" font-size="8.5" letter-spacing="1">
                                    <textPath xlink:href="#circlePath">
                                        DERMATOLOGY • SPECIALIST CONSULTATION • SKIN CARE •
                                    </textPath>
                                </text>
                            </svg>
                            <button type="button" class="shadow-lg play-btn-main" data-bs-toggle="modal" data-bs-target="#videoModal" aria-label="Watch Introduction Video">
                                <div class="play-pulse-ring"></div>
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>@endif
                </div>
            </div>

            <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-left">
                <div class="ps-lg-4 about-content">
                    <span class="mb-1 text-success fw-bold text-uppercase ls-2 small d-block">🏥 Introduction</span>
                    <h2 class="mb-3 section-title" itemprop="name">
                        {{ $profile->name }}
                    </h2>
                        <div class="mt-3 degree-badge-container" itemprop="medicalSpecialty">

                            <div class="flex-wrap gap-2 d-flex">
                                                                    @foreach($profile->credentials ?? [] as $credential)<span class="degree-pill-premium"><i class="fas fa-check-circle text-warning me-1"></i>{{ $credential }}</span>@endforeach
                                                                    
                                                                    
                                                                    
                                                            </div>
                        </div>

                    <div class="mb-4 text-secondary lh-lg" itemprop="description">{{ $profile->summary }}</div>

                    <div class="mb-4 row g-2">@foreach($specialists as $specialist)<div class="col-sm-6"><ul class="list-unstyled checklist-premium"><li class="mb-2 d-flex align-items-center"><i class="{{ $specialist->icon ?: 'fas fa-check-circle' }} text-success me-2"></i><span class="fw-medium text-dark-emphasis small">{{ $specialist->title }}</span></li></ul></div>@endforeach</div>

                    <a href="{{ route('public.about') }}" class="btn-about-premium">
                        <span>Read More Biography</span>
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@if($profile->introduction_youtube_id)<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="bg-transparent border-0 modal-content">
            <div class="p-0 border-0 justify-content-end">
                <button type="button" class="shadow-lg btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-0 modal-body">
                <div class="overflow-hidden border border-4 border-white shadow-2xl ratio ratio-16x9 rounded-5">
                                        <iframe class="video-container-luxury" src="{{ 'https://www.youtube-nocookie.com/embed/'.$profile->introduction_youtube_id.'?rel=0' }}" title="Introduction Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</div>@endif




    @if($steps->isNotEmpty() || $chambers->isNotEmpty())<section class="py-5 overflow-hidden consultation-section" id="consultation" style="background-color: #f8fdfb;">
    <div class="container">
        <div class="mb-5 text-center" data-aos="fade-up">
            <span class="mb-1 text-success fw-bold text-uppercase ls-2 small d-block">How I Care For You</span>
            <h2 class="section-title">My Consultation <em>Process</em></h2>
            <div class="mx-auto mt-2 divider-premium"></div>
        </div>

        <div class="row g-4 g-lg-5 align-items-start">
            <div class="order-2 col-lg-4 order-lg-1" data-aos="fade-right">
                <div class="p-4 bg-white border-0 shadow-lg schedule-card rounded-5 sticky-lg-top" style="top: 100px; border-top: 5px solid #0a2d1e !important;">
                    <div class="mb-4 d-flex align-items-center">
                        <div class="icon-box-sm bg-success-light me-3">
                            <i class="fas fa-hospital-alt text-success"></i>
                        </div>
                        <h4 class="m-0 fw-bold h5" style="color: #0a2d1e;">Chamber Location</h4>
                    </div>

                    <div class="location-list" itemscope="" itemtype="https://schema.org/MedicalClinic">@foreach($chambers as $chamber)<div class="mb-4 location-item border-start border-3 border-success ps-3 transition-hover">
                                <h5 class="mb-1 text-dark fw-bold h6" itemprop="name">{{ $chamber->name }}</h5>
<p class="mb-1 small text-muted public-copy">{{ $chamber->address }}</p>
@if($dial($chamber->phone))<a class="small text-success" href="{{ $dial($chamber->phone) }}">{{ $chamber->phone }}</a>@endif
@foreach($chamber->additional_phones ?? [] as $number) @if($dial($number))<a class="small text-success ms-2" href="{{ $dial($number) }}">{{ $number }}</a>@endif @endforeach
@if($chamber->map_url)<a class="small d-block text-success" href="{{ $chamber->map_url }}" target="_blank" rel="noopener noreferrer">View location</a>@endif
                                <p class="mb-0 text-muted small">@foreach($chamber->schedules as $schedule)<span class="d-block"><i class="far fa-clock text-success me-1"></i>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$schedule->day_of_week] }}: {{ substr($schedule->opens_at,0,5) }}–{{ substr($schedule->closes_at,0,5) }}</span>@endforeach @if($chamber->schedules->isNotEmpty())<small>{{ $chamber->timezone }}</small>@endif</p>
                            </div>@endforeach</div>

                    <div class="pt-3">
                        @if($phoneLink)<a href="{{ $phoneLink }}" class="py-3 shadow-md btn-premium-call w-100 rounded-pill d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="fas fa-phone-alt me-2 pulse-icon"></i>
                            <span class="fw-bold">BOOK APPOINTMENT</span>
                        </a>@endif
                    </div>
                </div>
            </div>

            <div class="order-1 col-lg-8 order-lg-2" data-aos="fade-left">
                <div class="process-timeline-wrapper position-relative ps-4 ps-md-5" itemscope="" itemtype="https://schema.org/HowTo">
                    <meta itemprop="name" content="{{ $profile->name }} Consultation Process">

                                            @foreach($steps as $step)<div class="mb-5 process-item-new position-relative" itemprop="step" itemscope="" itemtype="https://schema.org/HowToStep">
                            <div class="step-badge-outer">
                                <div class="shadow step-badge-inner">{{ $loop->iteration }}</div>
                            </div>

                            <div class="p-3 bg-white border-0 shadow-sm process-content-card p-md-4 rounded-4 transition-hover">
                                <h4 class="mb-2 fw-bold h5" style="color: #0a2d1e;" itemprop="name">{{ $step->title }}</h4>
                                <p class="mb-0 text-secondary small lh-base" itemprop="itemListElement">{{ $step->description }}</p>
                            </div>
                        </div>@endforeach
                                            
                                            
                                            
                                    </div>
            </div>
        </div>
    </div>
</section>@endif




    @if($videos->isNotEmpty())<section class="py-5 overflow-hidden video-gallery-area position-relative" id="video-gallery" style="background-color: #f8fdfb;">


    <div class="video-bg-icon v-icon-1"><i class="fas fa-heartbeat"></i></div>
    <div class="video-bg-icon v-icon-2"><i class="fas fa-stethoscope"></i></div>
    <div class="video-bg-icon v-icon-3"><i class="fas fa-baby-carriage"></i></div>
    <div class="video-bg-icon v-icon-4"><i class="fas fa-user-md"></i></div>
    <div class="video-bg-icon v-icon-5"><i class="fas fa-hand-holding-medical"></i></div>
    <div class="video-bg-icon v-icon-6"><i class="fas fa-microscope"></i></div>

    <div class="container position-relative" style="z-index: 2;">
        <header class="mb-5 text-center" data-aos="fade-up">
            <h2 class="section-title">Video <em>Gallery</em></h2>
            <div class="mx-auto mt-2 header-underline bg-success" style="width: 70px; height: 4px; border-radius: 10px;"></div>
        </header>

        <div class="row g-4">

                @foreach($videos->take(3) as $video)<div class="{{ $loop->first ? 'col-12' : 'col-md-6' }}" data-aos="fade-up" data-aos-delay="100">
                    <div class="overflow-hidden bg-white border-0 shadow-sm video-card-pure rounded-4 mb-4">
                        <div class="video-inner">
                                                            <div class="ratio ratio-16x9">

                                    <iframe src="{{ 'https://www.youtube-nocookie.com/embed/'.$video->youtube_id.'?rel=0' }}" title="{{ $video->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="" loading="lazy" style="border: 0;">
                                    </iframe>
                                </div>
                                                    </div>
                    </div>
                </div>@endforeach

                

                
                    </div>


        <div class="mt-4 more-video-wrapper d-flex justify-content-center">
            <a href="{{ route('public.videos') }}" class="btn-premium-video">
                <span class="btn-text">VIEW ALL VIDEOS</span>
                <span class="btn-icon-box">
                    <i class="fas fa-play"></i>
                    <span class="pulse-ring"></span>
                </span>
            </a>
        </div>
    </div>
</section>@endif



    @if($experiences->isNotEmpty())<section class="py-5 overflow-hidden experience-area" id="experience" style="background-color: #fcfdfd;">
    <div class="container">
        <header class="mb-5 text-center" data-aos="fade-up">
            <span class="mb-1 text-success fw-bold text-uppercase ls-2 small d-block">Career Journey</span>
            <h2 id="exp-title" class="section-title">
                Professional <em>Experience</em>
            </h2>
            <div class="mx-auto mt-2 divider-premium"></div>
        </header>

        <div class="row g-4 justify-content-center">
                            @foreach($experiences as $experience)<article class="col-lg-4 col-md-6" data-aos="zoom-in-up" data-aos-delay="100" itemscope="" itemtype="https://schema.org/Organization">

                    <div class="p-4 text-center bg-white experience-card-pro h-100 rounded-4 position-relative">

                        <div class="mx-auto mb-4 logo-wrapper-premium">
                            <figure class="m-0 overflow-hidden bg-white shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; border: 3px solid #f0f7f4;">
                                @if($experience->image)<img src="{{ $experience->image->url() }}" alt="{{ $experience->institution }}" class="p-2 img-fluid object-fit-contain" itemprop="logo" loading="lazy">@else<i class="fas fa-hospital fa-2x text-success" aria-hidden="true"></i>@endif
                            </figure>
                            <span class="verified-badge-exp" title="Verified Experience">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </div>

                        <h3 class="mb-2 h5 fw-bold" itemprop="name" style="color: #0a2d1e;">{{ $experience->institution }}</h3>

                        <div class="px-3 py-1 mb-2 d-inline-block rounded-pill bg-success-subtle text-success small fw-bold"><i class="fas fa-user-md me-1"></i>{{ $experience->position }}</div>

                        <div class="my-3 text-muted d-flex align-items-center justify-content-center small fw-semibold">
                            <span class="px-3 py-1 border shadow-xs bg-light rounded-pill">
                                <i class="far fa-calendar-alt text-success me-2"></i>
                                <time>{{ $experience->period_label ?: ($experience->started_on?->format('Y').'–'.($experience->is_current ? 'present' : $experience->ended_on?->format('Y'))) }}</time>
                            </span>
                        </div>

                        <p class="mb-0 text-secondary small lh-base" itemprop="description">{{ $experience->description }}</p>

                        <div class="d-none">
                            <span itemprop="address">Bangladesh</span>
                        </div>
                    </div>
                </article>@endforeach
                    </div>
    </div>
</section>@endif




    <div class="container my-5">
    <div class="p-1 shadow-lg help-section-wrapper rounded-4" data-aos="flip-up">
        <div class="p-4 overflow-hidden help-banner-premium d-flex flex-column flex-md-row align-items-center justify-content-between p-md-5 position-relative">

            <div class="pulse-bg"></div>
            <div class="floating-medical-icon">
                <i class="fas fa-baby-carriage"></i>
            </div>
            <div class="floating-medical-icon icon-2">
                <i class="fas fa-stethoscope"></i>
            </div>

            <div class="mb-4 text-center content-side text-md-start mb-md-0 position-relative">
                <span class="px-3 py-2 mb-2 shadow-sm badge bg-warning text-dark rounded-pill fw-bold ls-1 animate__animated animate__fadeInLeft">
                    <i class="fas fa-clock me-1"></i> CONTACT THE PRACTICE
                </span>
                <h2 class="mt-3 mb-2 section-title section-title-inverse">Do You Have <em>Health Concerns?</em></h2>
                <p class="mb-0 lh-base">
                    For consultation availability and appointments, contact
                    <strong class="text-warning">{{ $profile->name }}</strong> —
                    <span class="text-light fw-semibold">{{ $profile->specialty }}</span>
                </p>
            </div>

            <div class="button-side position-relative">
                @if($phoneLink)<a href="{{ $phoneLink }}" class="btn-help-premium text-decoration-none d-inline-flex align-items-center" title="Call the chamber for an appointment">
                    <span class="btn-content d-inline-flex align-items-center justify-content-center gap-2 text-nowrap">
                        <i class="fas fa-heartbeat pulse-icon" aria-hidden="true"></i>
                        <span class="fw-bold">I NEED HELP</span>
                    </span>
                </a>@endif
            </div>
        </div>
    </div>
</div>



    
<section class="py-5 overflow-hidden review-section" id="reviews">
    <div class="container">
        <div class="flex-wrap d-flex justify-content-between align-items-end" data-aos="fade-up">
            <div class="d-none d-md-block"></div>

            <div class="text-center reveiwbutton">
                <h2 class="section-title">What Patients <em>Are Saying!</em></h2>
                <div class="mx-auto mt-2 divider bg-success" style="width: 60px; height: 4px;"></div>
            </div>

            <div>
                <button class="mt-2 client_review_btn" data-bs-toggle="modal" data-bs-target="#reviewModal">
                    Write a Review
                    <span class="btn-ring"></span>
                </button>
            </div>
        </div>

        <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true" aria-labelledby="review-modal-title" @if($errors->review->any()) data-review-errors @endif>
            <div class="modal-dialog modal-dialog-centered">
                <div class="border-0 shadow-lg modal-content">
                    <div class="text-white modal-header bg-success">
                        <h5 class="modal-title" id="review-modal-title">Share Your Experience</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="p-4 modal-body">@include('public.review-form')</div>
                </div>
            </div>
        </div>

        @if(session('review_submitted'))<div class="alert alert-success mt-4" role="status">Thank you. Your review has been received and is awaiting approval.</div>@endif
        @if($reviews->isEmpty() && config('website.show_demo_reviews'))<p class="demo-review-notice mt-4 mb-0">Sample reviews for design preview — not real patient feedback.</p>@endif
        <div class="py-4 swiper mySwiper">
            <div class="swiper-wrapper">
                                    @forelse($reviews as $review)<div class="swiper-slide">
                        <div class="p-4 bg-white border shadow-sm review-card rounded-4">
                            <div class="mb-3 quote-icon"><i class="opacity-25 fas fa-quote-left text-success fa-2x"></i>
                            </div>
                            <p class="mb-4 text-muted small">{{ $review->message }}</p>
                            <div class="mb-3 stars text-warning">@for($star=0;$star < ($review->rating ?? 5);$star++)<i class="fas fa-star"></i>@endfor</div>
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="m-0 fw-bold">{{ $review->name }}</h6>
                                    <small class="text-muted">{{ $review->workplace }}</small>
                                </div>
                            </div>
                        </div>
                    </div>@empty
                        @if(config('website.show_demo_reviews'))@include('public.demo-reviews')@endif
                    @endforelse
                            </div>
            <div class="mt-5 swiper-pagination"></div>
        </div>
    </div>
</section>


    

    </main>
@endsection
