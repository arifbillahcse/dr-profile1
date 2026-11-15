@php
    $inputId = str_replace(['.', '_'], '-', $field);
    $parts = explode('.', $field, 2);
    $imageInputName = count($parts) === 2 ? $parts[0].'['.$parts[1].']' : $field;
    $uploadField = 'uploads.'.$field;
    $uploadName = 'uploads['.implode('][', explode('.', $field)).']';
    $selectedId = old($field, $value ?? '');
    $selectedImage = $mediaOptions->firstWhere('id', $selectedId);
@endphp
<div class="image-choice inline-image-upload" data-inline-image>
    <label class="form-label" for="{{ $inputId }}-upload">{{ $label }} @if($required ?? false)<span aria-hidden="true">*</span>@endif</label>
    <div class="inline-upload-box">
        <span class="inline-upload-heading"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Upload new image</span>
        <input type="file" class="form-control @error($uploadField) is-invalid @enderror" id="{{ $inputId }}-upload" name="{{ $uploadName }}" accept="image/jpeg,image/png,image/webp" data-inline-file aria-describedby="{{ $inputId }}-help">
        <div class="form-text" id="{{ $inputId }}-help">JPG, PNG or WebP. Maximum 5 MB and 4096 × 4096 pixels. The image uploads when you save this form.</div>
        @error($uploadField)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <p class="inline-upload-status mb-0 small" data-inline-status aria-live="polite">{{ $selectedImage ? 'Current image — choose a file to replace it here.' : 'Choose an image from your device.' }}</p>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-inline-cancel hidden>Cancel new upload</button>
    </div>
    <img class="selected-image mt-2" id="{{ $inputId }}-preview" @if($selectedImage) src="{{ $selectedImage->url() }}" @else hidden @endif alt="Selected {{ strtolower($label) }} preview">
    <details class="inline-library-choice mt-3" @if($errors->has($field)) open @endif>
        <summary>Or choose an existing image</summary>
        <label class="form-label mt-2 small" for="{{ $inputId }}">Image from library</label>
        <select class="form-select @error($field) is-invalid @enderror" name="{{ $imageInputName }}" id="{{ $inputId }}" data-image-select="{{ $inputId }}-preview">
            <option value="">No image</option>
            @foreach($mediaOptions as $option)<option value="{{ $option->id }}" data-preview="{{ $option->url() }}" @selected((string)$selectedId === (string)$option->id)>{{ $option->original_name }} (#{{ $option->id }})</option>@endforeach
        </select>
        @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </details>
</div>