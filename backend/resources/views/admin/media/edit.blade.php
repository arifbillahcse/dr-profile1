@extends('admin.layout')
@section('title', 'Manage image')
@section('content')
<section class="dashboard-heading settings-heading"><div><h1><i class="bi bi-image" aria-hidden="true"></i> Manage image #{{ $media->id }}</h1><p>{{ $media->original_name }}</p></div><a class="btn btn-outline-success" href="{{ route('admin.media.index') }}">Back to media library</a></section>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="media-edit-grid">
    <section class="content-panel"><img class="media-full-preview" src="{{ $media->url() }}" alt="{{ $media->alt_text ?? '' }}"><p class="mt-3 mb-0">{{ $media->width }} × {{ $media->height }} pixels · {{ number_format($media->size_bytes / 1024) }} KB · WebP</p></section>
    <section class="content-panel">
        <h2>Image details</h2>
        <form method="POST" action="{{ route('admin.media.update', $media) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="mb-3"><label class="form-label" for="alt_text">Alternative text</label><textarea class="form-control" id="alt_text" name="alt_text" rows="3" maxlength="500">{{ old('alt_text', $media->alt_text) }}</textarea></div>
            <div class="mb-4"><label class="form-label" for="replacement_image">Replace image (optional)</label><input class="form-control" id="replacement_image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG or WebP, up to 5 MB and 4096 × 4096 pixels. Replacing this image updates every section using it.</div></div>
            <button class="btn btn-success" type="submit">Save image details</button>
        </form>
        <hr class="my-4">
        <h2>Where this image is used</h2>
        @if($references !== [])
            <ul>@foreach($references as $label=>$count)<li>{{ $label }}: {{ $count }} {{ Str::plural('record', $count) }}</li>@endforeach</ul>
            <p class="small text-secondary">Archived content is included. Choose a different image in these sections before deleting this file.</p>
        @else
            <p>This image is not used by any website content.</p>
            <form method="POST" action="{{ route('admin.media.destroy', $media) }}" data-confirm="Delete this unused image permanently? This cannot be undone.">@csrf @method('DELETE')<button class="btn btn-outline-danger" type="submit">Delete unused image</button></form>
        @endif
    </section>
</div>
@endsection
