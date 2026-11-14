@extends('admin.layout')
@section('title', 'Media library')
@section('content')
<section class="dashboard-heading settings-heading"><div><h1><i class="bi bi-folder2-open" aria-hidden="true"></i> Media library</h1><p>Upload and reuse images across your website.</p></div><a class="btn btn-outline-success" href="{{ route('admin.settings.edit') }}">Website settings</a></section>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="content-panel mb-4" aria-labelledby="upload-heading">
    <h2 id="upload-heading">Upload an image</h2>
    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="upload-form">
        @csrf
        <div><label class="form-label" for="image">Image file</label><input class="form-control" id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required aria-describedby="upload-help"><div class="form-text" id="upload-help">JPG, PNG or WebP. Maximum 5 MB and 4096 × 4096 pixels. Images are optimized as WebP.</div></div>
        <div><label class="form-label" for="alt_text">Alternative text</label><input class="form-control" id="alt_text" name="alt_text" value="{{ old('alt_text') }}" maxlength="500"><div class="form-text">Briefly describe the image for people using screen readers.</div></div>
        <button class="btn btn-success" type="submit">Upload image</button>
    </form>
</section>
<form method="GET" action="{{ route('admin.media.index') }}" class="media-search mb-4">
    <label class="visually-hidden" for="search">Search images</label><input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Search by filename or alternative text" maxlength="100"><button class="btn btn-outline-success" type="submit">Search</button>@if($search !== '')<a class="btn btn-light" href="{{ route('admin.media.index') }}">Clear</a>@endif
</form>
<div class="media-grid">
    @forelse($mediaItems as $item)
    <article class="media-card">
        <a class="media-thumbnail" href="{{ route('admin.media.edit', $item) }}"><img src="{{ $item->url() }}" alt="{{ $item->alt_text ?? '' }}" loading="lazy"></a>
        <div class="media-card-body"><h2>{{ $item->original_name }}</h2><p>{{ $item->width }} × {{ $item->height }} · {{ number_format($item->size_bytes / 1024) }} KB</p><a class="btn btn-sm btn-outline-success" href="{{ route('admin.media.edit', $item) }}">Manage image #{{ $item->id }}</a></div>
    </article>
    @empty
    <div class="content-panel full-width"><h2>{{ $search !== '' ? 'No matching images' : 'Your media library is empty' }}</h2><p>{{ $search !== '' ? 'Try another filename or description.' : 'Upload your first image using the form above.' }}</p></div>
    @endforelse
</div>
<div class="mt-4">{{ $mediaItems->links() }}</div>
@endsection
