@if($gallery->isNotEmpty())
<section class="practice-story py-5" aria-labelledby="practice-story-heading">
    <div class="container">
        <div class="row align-items-end mb-4 g-3">
            <div class="col-lg-8">
                <p class="story-eyebrow">THE PERSON BEHIND THE CARE</p>
                <h2 id="practice-story-heading" class="display-5 fw-bold">A closer look at <em>the practice.</em></h2>
                <p class="text-secondary mb-0">Meet {{ $profile->name }} — in consultation and in clinical practice.</p>
            </div>
            <div class="col-lg-4 text-lg-end"><a class="story-gallery-link" href="{{ route('public.gallery') }}">Explore the photo journal <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a></div>
        </div>
        <div class="practice-story-grid">
            @foreach($gallery->take(4) as $moment)
                @if($moment->image)<a class="practice-moment" href="{{ route('public.gallery') }}" aria-label="{{ $moment->title }}">
                    <img src="{{ $moment->image->url() }}" alt="{{ $moment->image->alt_text ?: $moment->title }}" loading="lazy" width="{{ $moment->image->width }}" height="{{ $moment->image->height }}">
                    <span>{{ $moment->title }} <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </a>@endif
            @endforeach
        </div>
    </div>
</section>
@endif
