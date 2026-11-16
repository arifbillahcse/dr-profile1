@php
    $introductionPortrait = $profile->tallImage ?? $profile->portrait;
    $introductionStack = collect([$profile->firstImage, $profile->secondImage])->filter();
    $introductionImageCount = $introductionStack->count() + ($introductionPortrait ? 1 : 0);
@endphp
@if($introductionImageCount)
<div class="about-image-wrapper introduction-visual" data-aos="fade-right">
    <div class="introduction-collage {{ $introductionImageCount === 1 ? 'introduction-collage--single' : '' }} {{ $introductionPortrait ? '' : 'introduction-collage--no-portrait' }}">
        @if($introductionStack->isNotEmpty())
        <div class="introduction-stack">
            @foreach($introductionStack as $image)
            <figure class="introduction-photo introduction-photo--practice">
                <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?: $profile->name }}" loading="lazy" decoding="async">
                <figcaption><span aria-hidden="true"></span>{{ $loop->first ? 'In consultation' : 'Care in practice' }}</figcaption>
            </figure>
            @endforeach
        </div>
        @endif
        @if($introductionPortrait)
        <figure class="introduction-photo introduction-photo--portrait">
            <img src="{{ $introductionPortrait->url() }}" alt="{{ $introductionPortrait->alt_text ?: $profile->name }}" itemprop="image" loading="lazy" decoding="async">
        </figure>
        @endif
        @if($introductionImageCount === 3)
        <div class="introduction-seal">
            <svg viewBox="0 0 120 120" aria-hidden="true" class="introduction-seal-text">
                <defs><path id="introduction-seal-circle" d="M60,60 m-45,0 a45,45 0 1,1 90,0 a45,45 0 1,1 -90,0"></path></defs>
                <text><textPath href="#introduction-seal-circle" textLength="282.7" lengthAdjust="spacing">DERMATOLOGY · PERSONALISED CARE · </textPath></text>
            </svg>
            @if($introductionVideoId)
            <button type="button" class="introduction-seal-center" data-bs-toggle="modal" data-bs-target="#videoModal" aria-label="Watch {{ $introductionVideoTitle }}"><i class="fas fa-play" aria-hidden="true"></i></button>
            @else
            <span class="introduction-seal-center" aria-hidden="true"><i class="fas fa-leaf"></i></span>
            @endif
        </div>
        @elseif($introductionVideoId)
        <button type="button" class="introduction-watch" data-bs-toggle="modal" data-bs-target="#videoModal"><i class="fas fa-play" aria-hidden="true"></i> Watch introduction</button>
        @endif
    </div>
</div>
@endif
