@php
    $inputId = str_replace(['.', '_'], '-', $field);
    $parts = explode('.', $field, 2);
    $imageInputName = count($parts) === 2 ? $parts[0].'['.$parts[1].']' : $field;
    $selectedId = old($field, $value ?? '');
    $selectedImage = $mediaOptions->firstWhere('id', $selectedId);
@endphp
<div class="image-choice">
    <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
    <select class="form-select @error($field) is-invalid @enderror" name="{{ $imageInputName }}" id="{{ $inputId }}" data-image-select="{{ $inputId }}-preview" @if($required ?? false) required @endif>
        <option value="">No image</option>
        @foreach($mediaOptions as $option)<option value="{{ $option->id }}" data-preview="{{ $option->url() }}" @selected((string)$selectedId === (string)$option->id)>{{ $option->original_name }} (#{{ $option->id }})</option>@endforeach
    </select>
    <img class="selected-image mt-2" id="{{ $inputId }}-preview" @if($selectedImage) src="{{ $selectedImage->url() }}" @else hidden @endif alt="Selected {{ strtolower($label) }} preview">
    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
