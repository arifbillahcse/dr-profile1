@extends('public.layout')
@section('content')
<main id="main-content">
            <section class="breadcrumb-area slanted-breadcrumb position-relative">
        <div class="breadcrumb-bg-overlay"></div>

        <div class="container position-relative" style="z-index: 5;">
            <div class="row align-items-center min-vh-40">
                <div class="py-5 text-center col-lg-7 text-lg-start">
                    <div class="breadcrumb-text-box animate-up">
                        <h1 class="mb-3 text-white breadcrumb-title display-4 fw-bold">Expert Services</h1>

                        <p class="mt-3 breadcrumb-desc lead text-white-50">{{ $profile->summary }}</p>

                        <nav aria-label="breadcrumb" class="mt-4">
                            <ol class="breadcrumb custom-breadcrumb justify-content-lg-start justify-content-center">
                                <li class="breadcrumb-item"><a href="{{ route('public.home') }}" class="text-white-50 text-decoration-none {{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a></li>
                                <li class="breadcrumb-item active text-warning" aria-current="page">Services</li>
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

    <section class="py-5 bg-white service-section position-relative" id="services">
        <div class="container">
                            <div class="mb-5 text-center row justify-content-center animate-up">
                    <div class="col-lg-7">
                        <span class="text-success fw-bold text-uppercase ls-2">What I Provide</span>
                        <h2 class="mt-2 display-5 fw-bold ">Expert Medical <span class="text-success">Services</span></h2>
                        <div class="mx-auto mb-4 header-line-gold"></div>
                        <p class="text-muted">{{ $profile->summary }}</p>
                    </div>
                </div>

            <div class="row g-4">
                                    @foreach($services as $service)<article class="col-lg-4 col-md-6" data-aos="fade-up" id="{{ 'service-'.$service->id }}">
                        <div class="p-4 transition-all border-0 shadow-sm service-card-luxury h-100 rounded-4">
                            @if($service->image)<img src="{{ $service->image->url() }}" alt="{{ $service->image->alt_text ?? $service->name }}" class="w-100 rounded-3 mb-3" loading="lazy">@endif
                            <div class="mb-4 icon-box-medical-luxury"><i class="{{ $service->icon ?: 'fas fa-heart-pulse' }}" aria-hidden="true"></i></div>
                            <h3 class="mb-3 h4 fw-bold text-dark">{{ $service->name }}</h3>
                            <div class="service-desc text-secondary">{{ $service->description }}</div>
                        </div>
                    </article>@endforeach
                                    
                                    

                <div class="mt-5 col-lg-12" data-aos="zoom-in-up">
                    <div class="overflow-hidden shadow-lg cta-help-luxury position-relative rounded-5">

                        <div class="luxury-blob blob-1"></div>
                        <div class="luxury-blob blob-2"></div>

                        <div class="p-4 p-md-5 d-flex flex-column flex-lg-row align-items-center justify-content-between position-relative z-3">
                            <div class="cta-content col-lg-8">
                                <div class="mb-3 help-badge animate-flicker">
                                    <i class="fas fa-user-md me-2"></i> Specialized Healthcare Specialist
                                </div>

                                <h2 class="mb-3 text-white display-5 fw-bold header-shining">
                                    Expert Solutions for <br> <span class="text-gold-luxury">Skin Health &amp; Aesthetic Care.</span>
                                </h2>

                                <p class="mb-4 text-white-50 lead fw-medium">{{ $profile->summary }}</p>

                                <div class="mt-4 specialist-services-grid position-relative">
                                    <div class="row g-3">@foreach($specialists as $specialist)<div class="col-md-6"><ul class="list-unstyled checklist-premium-luxury mb-0"><li class="mb-3 d-flex align-items-center specialist-item-wrapper animate-up"><span class="icon-circle-glow me-3"><i class="{{ $specialist->icon ?: 'fas fa-check' }}"></i></span><span class="specialist-name text-white-50">{{ $specialist->title }}</span></li></ul></div>@endforeach</div>
                                </div>

                                <div class="cta-action-group">
                                    @if($phoneLink)<a href="{{ $phoneLink }}" class="btn-help-luxury ripple">
                                        <span>BOOK AN APPOINTMENT</span>
                                        <div class="btn-glow"></div>
                                    </a>@endif
                                </div>
                            </div>

                            <div class="cta-visual-luxury d-none d-lg-block">
                                <div class="floating-icon-wrapper">
                                    <div class="icon-circle-bg"></div>
                                    <i class="fas fa-hand-holding-heart cta-main-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </main>
@endsection
