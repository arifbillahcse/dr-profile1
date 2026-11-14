@extends('admin.layout')
@section('title', ($record->exists ? 'Edit ' : 'Add ').$definition['singular'])
@section('content')
<section class="dashboard-heading settings-heading"><div><h1><i class="bi bi-{{ $definition['icon'] }}" aria-hidden="true"></i> {{ $record->exists ? 'Edit' : 'Add' }} {{ $definition['singular'] }}</h1><p>{{ $definition['heading'] }}</p></div><a class="btn btn-outline-success" href="{{ route('admin.content.index', $moduleKey) }}">Back to list</a></section>
@if($errors->any())<div class="alert alert-danger" role="alert"><strong>Please check these details:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-info small">Visible content appears on the public website. Hidden and archived items stay private.</div>
<form method="POST" action="{{ $record->exists ? route('admin.content.update', [$moduleKey, $record->id]) : route('admin.content.store', $moduleKey) }}" class="content-editor">
    @csrf @if($record->exists) @method('PUT') @endif
    <section class="content-panel mb-4">
        <div class="settings-grid">
            @foreach($definition['fields'] as $name=>$fieldDefinition)
                @php
                    $fieldType = $fieldDefinition['type'] ?? 'text';
                    $fieldValue = $record->$name;
                    if ($fieldType === 'date' && $fieldValue) { $fieldValue = $fieldValue->format('Y-m-d'); }
                    if ($name === 'additional_phones_text') { $fieldValue = implode("\n", $record->additional_phones ?? []); }
                @endphp
                @if($fieldType === 'image')
                    <div class="full-width">
                        @include('admin.partials.image-select', ['field'=>$name,'label'=>$fieldDefinition['label'],'value'=>$fieldValue,'required'=>$fieldDefinition['required'] ?? false])
                        <p class="form-text">Choose a reusable image, or <a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener">upload an image in the media library</a> and reload this page.</p>
                    </div>
                @elseif($fieldType === 'checkbox')
                    <div class="full-width form-check editor-check"><input type="hidden" name="{{ $name }}" value="0"><input class="form-check-input" id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $fieldValue)) @if($name === 'is_current') data-current-position @endif><label class="form-check-label" for="{{ $name }}">{{ $fieldDefinition['label'] }}</label></div>
                @elseif($fieldType === 'timezone')
                    <div><label class="form-label" for="{{ $name }}">{{ $fieldDefinition['label'] }}</label><select class="form-select" id="{{ $name }}" name="{{ $name }}" required>@foreach(array_unique(array_merge(['Asia/Dhaka','UTC'], DateTimeZone::listIdentifiers())) as $zone)<option value="{{ $zone }}" @selected(old($name,$fieldValue) === $zone)>{{ $zone }}</option>@endforeach</select></div>
                @else
                    @include('admin.partials.text-field', array_merge($fieldDefinition, ['field'=>$name,'value'=>$fieldValue,'type'=>$fieldType === 'icon' ? 'text' : $fieldType,'required'=>$fieldDefinition['required'] ?? false,'class'=>$fieldType === 'textarea' ? 'full-width' : '', 'isEndDate'=>$name === 'ended_on','hint'=>$fieldDefinition['hint'] ?? ($fieldType === 'icon' ? 'Optional, for example: bi bi-heart-pulse.' : null)]))
                @endif
            @endforeach
        </div>
    </section>
    @if($definition['schedules'] ?? false)
        @include('admin.partials.schedules')
    @endif
    <section class="content-panel mb-4">
        <h2>Display settings</h2>
        <div class="settings-grid">
            @include('admin.partials.text-field', ['field'=>'sort_order','label'=>'Display order','type'=>'number','value'=>$record->sort_order,'required'=>true,'min'=>0,'maxValue'=>1000000,'hint'=>'Lower numbers appear first.'])
            <div class="form-check editor-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active',$record->is_active))><label class="form-check-label" for="is_active">Visible</label><p class="form-text">Leave unchecked to keep this content hidden.</p></div>
        </div>
    </section>
    <div class="settings-save"><a class="text-secondary" href="{{ route('admin.content.index', $moduleKey) }}">Cancel</a><button class="btn btn-success px-4" type="submit">{{ $record->exists ? 'Save changes' : 'Create '.$definition['singular'] }}</button></div>
</form>
@endsection
