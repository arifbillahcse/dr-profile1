<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $pageTitle = ['home' => null, 'about' => 'About', 'services' => 'Services', 'faqs' => 'FAQs', 'gallery' => 'Gallery', 'videos' => 'Videos'][$page];
        $title = ($pageTitle ? $pageTitle.' | ' : '').($site->seo_title ?: $site->site_title);
        $description = $site->seo_description ?: $profile->summary;
    @endphp
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    @if($site->seo_keywords)<meta name="keywords" content="{{ $site->seo_keywords }}">@endif
    <link rel="canonical" href="{{ route('public.'.$page) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ route('public.'.$page) }}">
    @if($profile->portrait)<meta property="og:image" content="{{ $profile->portrait->url() }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if($site->favicon)<link rel="icon" href="{{ $site->favicon->url() }}" type="image/webp">@else<link rel="icon" href="{{ asset('site/images/doctor-mark.svg') }}" type="image/svg+xml">@endif
    @foreach(['ab62d5ee79a9-bootstrap.min','dbe397d0d04e-all.min','39bc4941d961-swiper-bundle.min','3bb26241a579-magnific-popup.min','784d76080809-bootstrap-icons','2e8ca68208a7-aos','8c4e301a8e13-style','6b743f2723bf-responsive'] as $stylesheet)
        <link rel="stylesheet" href="{{ asset('site/css/'.$stylesheet.'.css') }}">
    @endforeach
    <link rel="stylesheet" href="{{ asset('site/css/page-'.$page.'.css') }}?v={{ filemtime(public_path('site/css/page-'.$page.'.css')) }}">
    <link rel="stylesheet" href="{{ asset('site/css/site.css') }}?v={{ filemtime(public_path('site/css/site.css')) }}">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
<a class="visually-hidden-focusable" href="#main-content">Skip to content</a>
@include('public.header')
@yield('content')
@include('public.footer')
@if(preg_match('/^\+?[0-9]{7,15}$/D', $site->whatsapp_number ?? ''))
<a class="whatsapp-float" href="https://wa.me/{{ ltrim($site->whatsapp_number, '+') }}" target="_blank" rel="noopener noreferrer" aria-label="Contact the practice on WhatsApp"><i class="fab fa-whatsapp" aria-hidden="true"></i><span class="wa-text">WhatsApp</span></a>
@endif
@foreach(['0de5e5bb3196-jquery.min','b711803b04bd-popper.min','dee9369ea5e2-bootstrap.min','13cf11a4182d-swiper-bundle.min','f685c239a3a3-jquery.magnific-popup.min','4dc952d76a63-aos','site'] as $script)
    <script src="{{ asset('site/js/'.$script.'.js') }}?v={{ filemtime(public_path('site/js/'.$script.'.js')) }}" defer></script>
@endforeach
</body>
</html>
