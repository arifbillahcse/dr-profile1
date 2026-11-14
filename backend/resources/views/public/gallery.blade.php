@extends('public.layout')
@section('content')
@php
    $cover = $gallery->firstWhere('image_id', $profile->portrait_image_id)
        ?? $gallery->first(fn ($photo) => $photo->image->width >= 400);
    $moments = $gallery->filter(fn ($photo) => $photo->id !== $cover?->id && $photo->image->width >= 600 && $photo->image->width > $photo->image->height)->take(2);
    $collection = $gallery->reject(fn ($photo) => $photo->id === $cover?->id || $moments->contains('id', $photo->id));
@endphp
<main id="main-content" class="gallery-editorial">
    <header class="gallery-masthead">
        <div class="container">
            <nav class="gallery-breadcrumb" aria-label="breadcrumb"><a href="{{ route('public.home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">Gallery</span></nav>
            <div class="gallery-masthead-row">
                <div><p class="section-eyebrow">THE VISUAL JOURNAL</p><h1 class="section-title section-title-inverse">In practice.<br><em>In person.</em></h1></div>
                <div class="gallery-masthead-note"><span class="gallery-line" aria-hidden="true"></span><p>A glimpse into the consultations, clinical practice and everyday care of {{ $profile->name }}.</p><a href="#gallery-container">Discover the collection <i class="fas fa-arrow-down" aria-hidden="true"></i></a></div>
            </div>
        </div>
    </header>

    <div class="popup-gallery">
        @if($cover)
        <section class="gallery-feature" aria-labelledby="gallery-feature-title">
            <div class="container gallery-feature-grid">
                <figure class="gallery-cover">
                    <a class="img-popup gallery-image-link" href="{{ $cover->image->url() }}" title="{{ $cover->title }}">
                        <img src="{{ $cover->image->url() }}" alt="{{ $cover->image->alt_text ?: $cover->title }}" width="{{ $cover->image->width }}" height="{{ $cover->image->height }}" fetchpriority="high">
                        <span class="gallery-open" aria-hidden="true"><i class="fas fa-expand"></i></span>
                    </a>
                    <figcaption><span class="section-eyebrow">IN FOCUS</span><strong>{{ $cover->title }}</strong></figcaption>
                </figure>
                <div class="gallery-feature-copy">
                    <p class="section-eyebrow">A PERSONAL PERSPECTIVE</p>
                    <h2 id="gallery-feature-title" class="section-title">The person<br><em>behind the care.</em></h2>
                    <p class="gallery-intro">From a conversation at the consultation desk to a closer look at clinical practice. Explore moments from {{ $profile->name }}’s professional life.</p>
                    <div class="gallery-mini-grid">
                        @foreach($moments as $moment)
                        <figure class="gallery-mini">
                            <a class="img-popup gallery-image-link" href="{{ $moment->image->url() }}" title="{{ $moment->title }}">
                                <img src="{{ $moment->image->url() }}" alt="{{ $moment->image->alt_text ?: $moment->title }}" width="{{ $moment->image->width }}" height="{{ $moment->image->height }}" loading="lazy">
                                <span class="gallery-open" aria-hidden="true"><i class="fas fa-expand"></i></span>
                            </a>
                            <figcaption>{{ $moment->title }}</figcaption>
                        </figure>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        @endif

        <section class="gallery-collection" aria-labelledby="gallery-collection-title">
            <div class="container">
                <div class="gallery-collection-heading">
                    <div><p class="section-eyebrow">PORTRAITS · PRACTICE · CHAMBER</p><h2 id="gallery-collection-title" class="section-title">Moments <em>&amp; details.</em></h2></div>
                    <p>Tap a photograph to explore it in full.</p>
                </div>
                <div id="gallery-container" class="gallery-curated-grid">
                    @forelse($collection as $photo)
                        @php
                            $document = str_contains($photo->image->original_name, 'chamber') || str_contains($photo->image->original_name, 'sparkle-profile');
                            $compact = $photo->image->width < 400;
                        @endphp
                        <figure class="gallery-curated-card {{ $document ? 'gallery-document' : '' }} {{ $compact ? 'gallery-compact' : '' }}">
                            <a class="img-popup gallery-image-link" href="{{ $photo->image->url() }}" title="{{ $photo->title }}">
                                <img src="{{ $photo->image->url() }}" alt="{{ $photo->image->alt_text ?: $photo->title }}" width="{{ $photo->image->width }}" height="{{ $photo->image->height }}" loading="lazy">
                                <span class="gallery-open" aria-hidden="true"><i class="fas fa-expand"></i></span>
                            </a>
                            <figcaption><span class="gallery-index" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $photo->title }}</h3>@if($photo->caption && $photo->caption !== $photo->title)<p>{{ $photo->caption }}</p>@endif</div></figcaption>
                        </figure>
                    @empty
                        <p class="gallery-empty">{{ $cover ? 'Explore the featured photographs above.' : 'Photographs will be published here soon.' }}</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <section class="gallery-invitation">
        <div class="container gallery-invitation-inner">
            <div><p class="section-eyebrow">YOUR NEXT STEP</p><h2 class="section-title">Let’s talk about <em>your care.</em></h2><p>Find consultation details and services for your next visit.</p></div>
            <div class="gallery-invitation-links"><a href="{{ route('public.services') }}">Explore services <i class="fas fa-arrow-right" aria-hidden="true"></i></a>@if($phoneLink)<a class="gallery-book" href="{{ $phoneLink }}">Call for an appointment <i class="fas fa-phone" aria-hidden="true"></i></a>@endif</div>
        </div>
    </section>
</main>
@endsection
