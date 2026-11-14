@php
    $inputId = str_replace(['.', '_'], '-', $field);
    $segments = explode('.', $field);
    $inputName = array_shift($segments);
    foreach ($segments as $segment) { $inputName .= '['.$segment.']'; }
    $fieldValue = old($field, $value ?? '');
@endphp
<div class="{{ $class ?? '' }}">
    <label class="form-label" for="{{ $inputId }}">{{ $label }}@if($required ?? false) <span aria-hidden="true">*</span>@endif</label>
    @if(($type ?? 'text') === 'textarea')
    <textarea id="{{ $inputId }}" class="form-control @error($field) is-invalid @enderror" name="{{ $inputName }}" rows="{{ $rows ?? 3 }}" maxlength="{{ $max ?? 5000 }}" @if($required ?? false) required @endif @if(isset($hint)) aria-describedby="{{ $inputId }}-hint" @endif>{{ $fieldValue }}</textarea>
    @else
    <input id="{{ $inputId }}" class="form-control @error($field) is-invalid @enderror" type="{{ $type ?? 'text' }}" name="{{ $inputName }}" value="{{ $fieldValue }}" maxlength="{{ $max ?? 255 }}" @if(isset($min)) min="{{ $min }}" @endif @if(isset($maxValue)) max="{{ $maxValue }}" @endif @if($required ?? false) required @endif @if(isset($hint)) aria-describedby="{{ $inputId }}-hint" @endif @if($isEndDate ?? false) data-end-date @endif>
    @endif
    @if(isset($hint))<div class="form-text" id="{{ $inputId }}-hint">{{ $hint }}</div>@endif
    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
