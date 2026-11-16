@if($videos->isNotEmpty())
<section class="py-5 video-gallery-area" id="video-gallery" aria-labelledby="video-gallery-title" style="background-color: var(--brand-soft);">
    <div class="container">
        <header class="mb-5 text-center">
            <p class="section-eyebrow">FROM THE VIDEO LIBRARY</p>
            <h2 id="video-gallery-title" class="section-title">Video <em>Gallery</em></h2>
            <div class="mx-auto mt-2 header-underline bg-success" style="width: 70px; height: 4px; border-radius: 10px;"></div>
        </header>
        <div class="video-library-grid">
            @foreach($videos->take(3) as $video)
                @include('public.video-card')
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('public.videos') }}" class="btn-about-premium">View all videos <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a>
        </div>
    </div>
</section>
@endif
