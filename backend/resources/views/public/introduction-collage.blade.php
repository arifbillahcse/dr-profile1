@php
    $introductionPortrait = $profile->tallImage ?? $profile->portrait;
    $introductionStack = collect([$profile->firstImage, $profile->secondImage])->filter();
    $introductionImageCount = $introductionStack->count() + ($introductionPortrait ? 1 : 0);
@endphp
@if($introductionImageCount)
<div class="about-image-wrapper introduction-visual" data-aos="fade-right">
    <div class="introduction-collage {{ $introductionImageCount === 1 ? 'introduction-collage--single' : '' }} {{ $introductionPortrait ? '' : 'introduction-collage--no-portrait' }}">
        @if($introductionPortrait)
        <div class="introduction-main">
            <figure class="introduction-photo introduction-photo--portrait">
                <img src="{{ $introductionPortrait->url() }}" alt="{{ $introductionPortrait->alt_text ?: $profile->name }}" itemprop="image" loading="lazy" decoding="async">
            </figure>
            @if($introductionVideoId)
            <button type="button" class="introduction-watch" data-bs-toggle="modal" data-bs-target="#videoModal" aria-label="Watch {{ $introductionVideoTitle }}"><span><i class="fas fa-play" aria-hidden="true"></i></span> Watch video <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></button>
            @endif
        </div>
        @endif
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
        @if(!$introductionPortrait && $introductionVideoId)
        <div class="introduction-video-row">
            <button type="button" class="introduction-watch" data-bs-toggle="modal" data-bs-target="#videoModal" aria-label="Watch {{ $introductionVideoTitle }}"><span><i class="fas fa-play" aria-hidden="true"></i></span> Watch video <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></button>
        </div>
        @endif
    </div>
</div>
@endif
