@extends('public.layout')
@section('content')
<main id="main-content">
        <section class="breadcrumb-area slanted-breadcrumb position-relative">
    <div class="breadcrumb-bg-overlay"></div>

    <div class="container position-relative" style="z-index: 5;">
        <div class="row align-items-center min-vh-40">
            <div class="py-5 text-center col-lg-7 text-lg-start">
                <div class="breadcrumb-text-box animate-up">
                    <h1 class="mb-3 text-white breadcrumb-title display-4 fw-bold">Medical Gallery</h1>

                    <p class="mt-3 breadcrumb-desc lead text-white-50">{{ $profile->summary }}</p>

                    <nav aria-label="breadcrumb" class="mt-4">
                        <ol class="breadcrumb custom-breadcrumb justify-content-lg-start justify-content-center">
                            <li class="breadcrumb-item"><a href="{{ route('public.home') }}" class="text-white-50 text-decoration-none {{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a></li>
                            <li class="breadcrumb-item active text-warning" aria-current="page">Gallery</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="slanted-image-wrapper d-none d-lg-block">
        <div class="slanted-shape"></div>
        <div class="doctor-image-bg" @if($profile->portrait)style="background-image: url('{{ $profile->portrait->url() }}')"@endif></div>
    </div>
</section>

<section class="py-5 gallery-main" style="background: #fdfdfd;">
    <div class="container">
                    <div class="mb-5 text-center section-header animate-up">
                <h6 class="text-success fw-bold text-uppercase ls-2">THE PHOTO JOURNAL</h6>
                <h2 class="fw-bold display-6">In practice. In person.</h2>
                <div class="mx-auto mt-2 gold-line"></div>
            </div>

            <div id="gallery-container" class="photo-journal popup-gallery">
                                    @foreach($gallery as $photo)<div class="gallery-item journal-item {{ $photo->image && $photo->image->width < 400 ? 'journal-small' : '' }}" data-aos="fade-up" data-aos-delay="0">
                        <div class="overflow-hidden shadow-sm premium-gallery-card position-relative rounded-4">
                            <a href="{{ $photo->image->url() }}" title="{{ $photo->title }}" class="img-popup">
                                @if($photo->image)<img src="{{ $photo->image->url() }}" class="img-fluid gallery-img w-100" alt="{{ $photo->image->alt_text ?? $photo->title }}" loading="lazy">@endif
                                <div class="premium-overlay" aria-hidden="true">
                                    <i class="text-white fas fa-search-plus fs-3"></i>
                                </div>
                            </a>@if($photo->caption)<p class="p-3 mb-0 small public-copy">{{ $photo->caption }}</p>@endif
                        </div>
                    </div>@endforeach
                                    
                                    
                                    
                            </div>

            <div class="mt-5 d-flex justify-content-center">

            </div>
            </div>
</section>
    </main>
@endsection
