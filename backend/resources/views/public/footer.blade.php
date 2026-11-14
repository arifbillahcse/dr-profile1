<footer class="pt-5 pb-4 text-white footer-section position-relative" itemscope="" itemtype="https://schema.org/MedicalOrganization">
    <div class="container">
        <div class="row g-4">

            <div class="mb-4 col-lg-4 col-md-6" data-aos="fade-up">
                <div class="footer-about">
                    <h3 class="mb-2 footer-title fw-bold text-warning" itemprop="name">{{ $profile->name }}</h3>
                    <div class="mb-3 doctor-credentials-wrapper">

                        <div class="flex-wrap gap-2 d-flex align-items-center">@foreach($profile->credentials ?? [] as $credential)<span class="credential-tag">{{ $credential }}</span>@unless($loop->last)<span class="opacity-50 credential-separator text-warning">•</span>@endunless @endforeach</div>
                    </div>
                    <div class="mb-4 opacity-75 about-text small lh-lg" itemprop="description">{{ $profile->summary }}</div>

                    <div class="footer-contact-info">@if($phoneLink)<div class="contact-link"><i class="fas fa-phone-alt text-warning"></i><a href="{{ $phoneLink }}" class="text-white text-decoration-none small">{{ $site->phone }}</a></div>@endif
 @if($site->additional_phones)<div class="contact-link"><i class="fas fa-phone-alt text-warning"></i>@foreach($site->additional_phones as $number)@if($dial($number))<a href="{{ $dial($number) }}" class="text-white text-decoration-none small">{{ $number }}</a>@else<span class="small">{{ $number }}</span>@endif @unless($loop->last), @endunless @endforeach</div>@endif
 @if($site->email)<div class="contact-link"><i class="fas fa-envelope text-warning"></i><a href="mailto:{{ $site->email }}" class="text-white text-decoration-none small">{{ $site->email }}</a></div>@endif
 @if($site->address)<div class="contact-link d-flex align-items-start"><i class="fas fa-map-marker-alt text-warning mt-1"></i><address class="mb-0 text-white small ms-1 public-copy">{{ $site->address }}</address></div>@endif</div>
                </div>
            </div>


            <div class="mb-4 col-lg-2 col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                <h4 class="pb-1 mb-4 text-white footer-subtitle fw-bold border-bottom border-warning d-inline-block">
                    Services</h4>
                <ul class="footer-links list-unstyled">@foreach($services as $service)<li class="mb-2"><a href="{{ route('public.services') }}#service-{{ $service->id }}" class="text-white-50 text-decoration-none small"><i class="fas fa-plus x-small text-warning me-2"></i>{{ $service->name }}</a></li>@endforeach</ul>
            </div>

            <div class="mb-4 col-lg-3 col-md-6 col-6" data-aos="fade-up" data-aos-delay="200">
                <h4 class="pb-1 mb-4 text-white footer-subtitle fw-bold border-bottom border-warning d-inline-block">
                    Expertise</h4>
                <ul class="footer-links list-unstyled">@foreach($specialists as $specialist)<li class="mb-2"><span class="text-white-50 small"><i class="fas fa-check-circle x-small text-warning me-2"></i>{{ $specialist->title }}</span></li>@endforeach</ul>
            </div>


            <div class="mb-4 col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <h4 class="mb-4 text-white footer-subtitle fw-bold">Official Facebook</h4>
                                    <div class="text-center fb-premium-card">
                        @if($site->socialImage)<img src="{{ $site->socialImage->url() }}" alt="{{ $profile->name }}" class="mb-3 shadow fb-profile-img rounded-circle" loading="lazy">@endif
                        <h2 class="mb-1 text-white fw-bold h6-size-fix">{{ $profile->name }}</h2>
                        <p class="mb-3 x-small text-white-50">{{ $profile->specialty }}</p>

                        @if(!empty($site->social_links['facebook']))<a href="{{ $site->social_links['facebook'] ?? '' }}" target="_blank" rel="noopener noreferrer" class="btn btn-fb-follow" aria-label="Visit our Facebook profile">
                            <i class="fab fa-facebook-f me-2"></i> Visit Facebook Page
                        </a>@endif
                        <div class="gap-2 mt-3 d-flex justify-content-center">@foreach(['linkedin'=>'fab fa-linkedin-in','youtube'=>'fab fa-youtube','instagram'=>'fab fa-instagram'] as $network=>$icon)@if(!empty($site->social_links[$network]))<a href="{{ $site->social_links[$network] }}" target="_blank" rel="noopener noreferrer" class="social-v4-btn" aria-label="{{ ucfirst($network) }}"><i class="{{ $icon }}"></i></a>@endif @endforeach @if($phoneLink)<a href="{{ $phoneLink }}" class="social-v4-btn" aria-label="Call the practice"><i class="fas fa-phone"></i></a>@endif</div>
                    </div>
                            </div>
        </div>

        <hr class="mt-5 mb-4 opacity-10">

        <div class="row align-items-center">
            <div class="text-center col-md-6 text-md-start">
                <p class="mb-0 opacity-50 x-small">© {{ now()->year }} {{ $profile->name }}. All rights reserved.</p>
            </div>
            <div class="mt-3 text-center col-md-6 text-md-end mt-md-0">
                <p class="mb-0 opacity-50 x-small">Developed by <a href="https://classicit.com.bd" target="_blank" rel="noopener noreferrer" class="text-warning text-decoration-none fw-bold">Classic IT</a></p>
            </div>
        </div>
    </div>

    <a href="#" class="shadow-lg back-to-top-simple" aria-label="Scroll back to top">
        <i class="fas fa-chevron-up"></i>
    </a>


</footer>