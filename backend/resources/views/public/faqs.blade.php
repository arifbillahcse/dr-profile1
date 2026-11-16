@extends('public.layout')
@section('content')
<main id="main-content">
            <section class="breadcrumb-area slanted-breadcrumb position-relative">
        <div class="breadcrumb-bg-overlay"></div>
        <div class="container position-relative" style="z-index: 5;">
            <div class="row align-items-center min-vh-40">
                <div class="py-5 text-center col-lg-7 text-lg-start">
                    <div class="breadcrumb-text-box animate-up">
                        <h1 class="mb-3 breadcrumb-title section-title section-title-inverse">Frequently Asked <em>Questions</em></h1>
                        <p class="mt-3 breadcrumb-desc lead text-white-50">{{ $profile->summary }}</p>
                        <nav aria-label="breadcrumb" class="mt-4">
                            <ol class="breadcrumb custom-breadcrumb justify-content-lg-start justify-content-center">
                                <li class="breadcrumb-item"><a href="{{ route('public.home') }}" class="text-white-50 text-decoration-none {{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a></li>
                                <li class="breadcrumb-item active text-warning" aria-current="page">FAQs</li>
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

    <section class="py-5 faq-main-section" style="background: var(--brand-soft);">
        <div class="container">
                            <div class="mb-5 text-center section-header animate-up">
                    <span class="text-success section-eyebrow">Knowledge Base</span>
                    <h2 class="mt-2 section-title">Have Any <em>Questions?</em></h2>
                    <div class="mx-auto mb-4 gold-line-divider"></div>
                    <p class="mx-auto text-muted col-lg-8">
                        Explore answers to common questions about the doctor and chamber.
                        @if($phoneLink)For appointment information, <a href="{{ $phoneLink }}" class="text-success fw-bold text-decoration-none">call the chamber</a>.@endif
                    </p>
                </div>

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion luxury-accordion" id="faqAccordion">
                                                    @foreach($faqs as $faq)<div class="mb-4 overflow-hidden border-0 shadow-sm accordion-item rounded-4 animate-up" data-aos="fade-up" data-aos-delay="0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button fw-bold py-4 {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="{{ '#faq-'.$faq->id }}" aria-controls="{{ 'faq-'.$faq->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}"><span class="faq-number me-3 text-success-glow">{{ str_pad((string)$loop->iteration,2,"0",STR_PAD_LEFT) }}</span>{{ $faq->question }}</button>
                                </h2>
                                <div id="{{ 'faq-'.$faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                                    <div class="px-5 pb-4 bg-white accordion-body text-secondary lead-sm public-copy">{{ $faq->answer }}</div>
                                </div>
                            </div>@endforeach
                                                    
                                                    
                                            </div>
                </div>
            </div>
        </div>
    </section>
    </main>
@endsection
