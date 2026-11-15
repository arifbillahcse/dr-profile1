<article class="practice-video-card">
    <div class="ratio ratio-16x9">
        <iframe src="{{ 'https://www.youtube-nocookie.com/embed/'.$video->youtube_id.'?rel=0' }}" title="{{ $video->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" loading="lazy" allowfullscreen></iframe>
    </div>
    <div class="practice-video-copy">
        <h3>{{ $video->title }}</h3>
        @if($video->description)<p>{{ $video->description }}</p>@endif
        <a href="{{ 'https://www.youtube.com/watch?v='.$video->youtube_id }}" target="_blank" rel="noopener noreferrer" aria-label="{{ 'Watch '.$video->title.' on YouTube (opens in a new tab)' }}">Watch on YouTube <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
    </div>
</article>
