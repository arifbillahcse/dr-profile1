@foreach([
    ['topic' => 'A clear plan', 'message' => 'I had a lot of questions about my skin and wasn’t sure where to start. Having things explained in simple terms helped me understand the next steps.'],
    ['topic' => 'Time to ask questions', 'message' => 'What mattered most to me was being able to explain my concerns without feeling rushed. I appreciated clear advice and the chance to ask a few more questions.'],
    ['topic' => 'Planning a visit', 'message' => 'I like knowing the appointment time, the chamber location and what I need to bring before I go. Clear information makes preparing for a visit much easier.'],
] as $example)
<div class="swiper-slide" data-demo-review>
    <div class="p-4 bg-white border shadow-sm review-card rounded-4 demo-review-card">
        <div class="demo-review-top"><i class="fas fa-quote-left" aria-hidden="true"></i><span>Sample review</span></div>
        <p class="mb-4 text-muted small">{{ $example['message'] }}</p>
        <div class="demo-review-person"><span class="demo-review-avatar" aria-hidden="true"><i class="far fa-comment"></i></span><div><h6 class="m-0 fw-semibold">{{ $example['topic'] }}</h6><small class="text-muted">Illustrative example</small></div></div>
    </div>
</div>
@endforeach
