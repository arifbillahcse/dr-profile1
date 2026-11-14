<header class="main-header-area">


    <section class="header-sticky header-navigation-main">
        <div class="container">
            <div class="header-inner d-flex align-items-center justify-content-between">

                <div class="logo-box">
                    <a href="{{ route('public.home') }}">
                        @if($site->logo)<img src="{{ $site->logo->url() }}" alt="{{ $site->site_title }}" width="200" height="60" class="main-logo-img" loading="lazy">@endif
                    </a>
                </div>

                <nav class="d-none d-lg-block">
                    <ul class="mb-0 luxury-menu d-flex list-unstyled">
                        <li><a href="{{ route('public.home') }}" class="{{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a></li>
                        <li><a href="{{ route('public.about') }}" class="{{ request()->routeIs('public.about') ? 'active' : '' }}">About</a></li>
                        <li><a href="{{ route('public.services') }}" class="{{ request()->routeIs('public.services') ? 'active' : '' }}">Services</a></li>
                        <li><a href="{{ route('public.faqs') }}" class="{{ request()->routeIs('public.faqs') ? 'active' : '' }}">Faqs</a></li>
                        <li><a href="{{ route('public.gallery') }}" class="{{ request()->routeIs('public.gallery') ? 'active' : '' }}">Gallery</a></li>
                    </ul>
                </nav>

                <div class="gap-2 header-action-btns d-flex align-items-center">
                    @if($phoneLink)<a href="{{ $phoneLink }}" class="btn-call-neon" aria-label="Call the practice">

                        <i class="fas fa-phone-alt phone-shake" aria-hidden="true"></i>

                        <span class="ms-2 d-none d-lg-inline">{{ $site->phone }}</span>
                    </a>@endif

                    <button class="mobile-nav-toggle d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="Open Navigation Menu" title="Open Menu">
                        <i class="fas fa-bars-staggered" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="offcanvas offcanvas-start luxury-mobile-canvas" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header border-bottom border-light border-opacity-10">
            @if($site->logo)<img src="{{ $site->logo->url() }}" alt="{{ $site->site_title }}" height="35" loading="lazy">@endif
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close navigation"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="mobile-nav-links list-unstyled">
                <li><a href="{{ route('public.home') }}" class="{{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a></li>
                <li><a href="{{ route('public.about') }}" class="{{ request()->routeIs('public.about') ? 'active' : '' }}">About</a></li>
                <li><a href="{{ route('public.services') }}" class="{{ request()->routeIs('public.services') ? 'active' : '' }}">Services</a></li>
                <li><a href="{{ route('public.faqs') }}" class="{{ request()->routeIs('public.faqs') ? 'active' : '' }}">Faqs</a></li>
                <li><a href="{{ route('public.gallery') }}" class="{{ request()->routeIs('public.gallery') ? 'active' : '' }}">Gallery</a></li>
            </ul>
        </div>
    </div>
</header>