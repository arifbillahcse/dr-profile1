@extends('public.layout')
@section('content')
<main id="main-content">
            <section class="breadcrumb-area slanted-breadcrumb position-relative">
        <div class="breadcrumb-bg-overlay"></div>
        <div class="container position-relative" style="z-index: 5;">
            <div class="row align-items-center min-vh-40">
                <div class="py-5 text-center col-lg-7 text-lg-start">
                    <div class="breadcrumb-text-box animate-up">
                        <h1 class="mb-3 breadcrumb-title section-title section-title-inverse">{{ $profile->name }}</h1>
                        <p class="mt-3 breadcrumb-desc lead text-white-50">{{ $profile->summary }}</p>
                        <nav aria-label="breadcrumb" class="mt-4">
                            <ol class="breadcrumb custom-breadcrumb justify-content-lg-start justify-content-center">
                                <li class="breadcrumb-item"><a href="{{ route('public.home') }}" class="text-white-50 text-decoration-none {{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a></li>
                                <li class="breadcrumb-item active text-warning" aria-current="page">About Me</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="slanted-image-wrapper d-none d-lg-block">
            <div class="slanted-shape"></div>
            <div class="doctor-image-bg" @if($profile->portrait)style="background-image: url('{{ $profile->portrait->url() }}')"@endif>
            </div>
        </div>
    </section>

    <section class="py-5 overflow-hidden dr-about-section position-relative" style="background: var(--brand-soft);">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-5" data-aos="fade-right">
                    <div class="doctor-frame-luxury position-relative">
                        <div class="p-2 overflow-hidden bg-white shadow-lg rounded-4 border-gold">
                            @if($profile->portrait)<img src="{{ $profile->portrait->url() }}" alt="{{ $profile->name }}" class="img-fluid rounded-3 w-100 main-dr-img" loading="lazy">@endif

                            @if($experienceStat)<div class="shadow-lg experience-floating-card animate-bounce">
                                <h3 class="mb-0 text-white fw-bold">{{ $experienceStat->value.$experienceStat->suffix.' Years' }}</h3>
                                <p class="mb-0 small text-white-50">Years of Service</p>
                            </div>@endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-7" data-aos="fade-up">
                    <h6 class="mb-2 text-success section-eyebrow">Expert Physician</h6>
                    <h2 class="mb-3 section-title">{{ $profile->name }}</h2>
                    <h4 class="mb-4 text-success-glow fw-semibold">{{ $profile->specialty }}</h4>

                    <div class="mb-4 dr-short-bio text-muted lead">{{ $profile->summary }}</div>
                    @include('public.memberships')

                                            @if($experiences->isNotEmpty())<div class="p-4 bg-white shadow-sm current-position-card rounded-4 border-start-gold">@if($experiences->isNotEmpty())<p class="mb-0 text-dark"><i class="fas fa-briefcase me-2 text-warning"></i>{{ $experiences->first()->position }} — {{ $experiences->first()->institution }}</p>@endif</div>@endif
                                    </div>
            </div>

            <div class="mt-5 dr-long-bio animate-up" data-aos="fade-up">
                <div class="p-5 bg-white shadow-sm rounded-5 border-light public-copy">@if(str_starts_with($profile->biography ?? '', $profile->name."\n"))<strong class="fw-semibold">{{ $profile->name }}</strong>{{ substr($profile->biography, strlen($profile->name)) }}@else{{ $profile->biography }}@endif</div>
            </div>

            @if($chambers->isNotEmpty())
            <section class="mt-5" id="chambers" aria-labelledby="about-chambers-title">
                <h2 class="section-title" id="about-chambers-title">Consultation <em>Chambers</em></h2>
                <p class="text-muted">Choose your preferred clinic and call to confirm availability.</p>
                <div class="row g-4">@foreach($chambers as $chamber)
                    <div class="col-md-6"><div class="practice-chamber practice-chamber-card">@include('public.chamber-details')</div></div>
                @endforeach</div>
            </section>
            @endif

            <div class="mt-5 row">
                <div class="col-12" data-aos="fade-up">
                    @if($educations->isNotEmpty())<div class="p-5 overflow-hidden shadow-lg academic-luxury-card rounded-5 position-relative">
                        <div class="academic-icon-bg"><i class="fas fa-user-graduate"></i></div>
                        <div class="position-relative z-2">
                            <h3 class="mb-4 content-title d-flex align-items-center">
                                <i class="bi bi-mortarboard-fill me-3 text-warning"></i> Academic Excellence
                            </h3>
                            <div class="row g-4">@foreach($educations as $education)<div class="col-md-6"><ul class="list-unstyled timeline-luxury"><li class="mb-3"><strong>{{ $education->qualification }}</strong> {{ $education->year }}<br>{{ $education->description ?: $education->institution }}</li></ul></div>@endforeach</div>
                        </div>
                    </div>@endif
                </div>
            </div>

            <div class="mt-5 row" data-aos="zoom-in">
                <div class="col-12">
                    @if($profile->introduction_youtube_id)<div class="overflow-hidden shadow-2xl video-container-luxury rounded-5">
                                                                            <div class="ratio ratio-16x9">
                                <iframe src="{{ 'https://www.youtube-nocookie.com/embed/'.$profile->introduction_youtube_id.'?rel=0' }}" title="Dr. Introduction" allowfullscreen=""></iframe>
                            </div>
                                            </div>@endif
                </div>
            </div>

            <div class="mt-5 col-lg-12" data-aos="zoom-in-up">
                <div class="overflow-hidden shadow-lg cta-help-luxury position-relative rounded-5">
                    <div class="luxury-blob blob-1"></div>
                    <div class="luxury-blob blob-2"></div>
                    <div class="p-5 d-flex flex-column flex-lg-row align-items-center justify-content-between position-relative z-3">
                        <div class="text-center cta-content text-lg-start">
                            <h2 class="mb-3 section-title section-title-inverse">Care for Your Skin <em>&amp; Wellbeing</em></h2>
                            <p class="mb-4 text-white-50 lead">Specialist consultations in dermatology, venereology and aesthetic medicine.</p>
                            @if($phoneLink)<a href="{{ $phoneLink }}" class="btn-help-luxury ripple" aria-label="Call the practice">
                                <span>CALL FOR APPOINTMENT</span>
                                <div class="btn-glow"></div>
                            </a>@endif
                        </div>
                        <div class="cta-visual-luxury d-none d-lg-block">
                            <i class="opacity-25 fas fa-hand-holding-heart" style="font-size: 150px; color: var(--brand-gold);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </main>
@endsection
