@foreach([
    'Clear appointment information and a reassuring explanation of what to expect during a consultation.',
    'Thoughtful communication and time to discuss skin concerns in a comfortable setting.',
    'Helpful guidance on the next steps and an easy way to arrange a follow-up visit.',
] as $message)
<div class="swiper-slide" data-demo-review>
    <div class="p-4 bg-white border shadow-sm review-card rounded-4 demo-review-card">
        <div class="demo-review-top"><i class="fas fa-quote-left" aria-hidden="true"></i><span>Demo review</span></div>
        <p class="mb-4 text-muted small">{{ $message }}</p>
        <div class="mb-3 stars text-warning" aria-label="Sample five-star rating">@for($star = 0; $star < 5; $star++)<i class="fas fa-star" aria-hidden="true"></i>@endfor</div>
        <div class="demo-review-person"><span class="demo-review-avatar" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h6 class="m-0 fw-semibold">Sample Patient {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</h6><small class="text-muted">Illustrative feedback</small></div></div>
    </div>
</div>
@endforeach
