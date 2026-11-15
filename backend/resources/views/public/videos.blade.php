@extends('public.layout')
@section('content')
<main id="main-content">
    <section class="breadcrumb-area slanted-breadcrumb position-relative">
        <div class="container py-5"><h1 class="breadcrumb-title section-title section-title-inverse">Video <em>Gallery</em></h1><a class="text-white" href="{{ route('public.home') }}">Home</a></div>
    </section>
    <section class="py-5 video-gallery-area">
        <div class="container">
            @if($videos->isNotEmpty())
            <div class="video-library-grid">
                @foreach($videos as $video)
                    @include('public.video-card')
                @endforeach
            </div>
            @else
            <p class="text-center py-5">Videos will be published here when available.</p>
            @endif
        </div>
    </section>
</main>
@endsection
