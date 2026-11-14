@extends('admin.layout')
@section('title', 'Website settings')
@section('content')
<section class="dashboard-heading settings-heading"><div><h1><i class="bi bi-sliders" aria-hidden="true"></i> Website settings</h1><p>Manage your profile, branding and contact information.</p></div><a class="btn btn-outline-success" href="{{ route('admin.media.index') }}"><i class="bi bi-folder2-open" aria-hidden="true"></i> Media library</a></section>
@if($errors->any())<div class="alert alert-danger" role="alert"><strong>Please check these details:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-info small">Saved settings appear immediately on the public website.</div>
<form method="POST" action="{{ route('admin.settings.update') }}" class="settings-form">
    @csrf @method('PUT')
    <section class="content-panel mb-4" aria-labelledby="profile-heading">
        <h2 id="profile-heading">Doctor profile</h2><p>Your public name, qualifications and introduction.</p>
        <div class="settings-grid">
            @include('admin.partials.text-field', ['field'=>'profile.name','label'=>'Doctor name','value'=>$profile->name,'required'=>true])
            @include('admin.partials.text-field', ['field'=>'profile.designation','label'=>'Designation','value'=>$profile->designation])
            @include('admin.partials.text-field', ['field'=>'profile.specialty','label'=>'Specialty','value'=>$profile->specialty])
            @include('admin.partials.text-field', ['field'=>'profile.trusted_patients','label'=>'Patient trust label','value'=>$profile->trusted_patients,'max'=>50,'hint'=>'Optional, for example 5k. Leave blank to hide the trust card.'])
            @include('admin.partials.text-field', ['field'=>'profile.credentials_text','label'=>'Qualifications','type'=>'textarea','value'=>implode("\n",$profile->credentials ?? []),'max'=>10000,'hint'=>'Enter one qualification per line.'])
            @include('admin.partials.text-field', ['field'=>'profile.introduction_youtube_id','label'=>'Introduction video','value'=>$profile->introduction_youtube_id,'hint'=>'Paste a YouTube link or an 11-character video ID.'])
            @include('admin.partials.text-field', ['field'=>'profile.summary','label'=>'Short introduction','type'=>'textarea','value'=>$profile->summary,'class'=>'full-width','max'=>5000])
            @include('admin.partials.text-field', ['field'=>'profile.biography','label'=>'Full biography','type'=>'textarea','value'=>$profile->biography,'class'=>'full-width','rows'=>7,'max'=>100000,'hint'=>'Use plain text. Paragraphs and line breaks are preserved.'])
        </div>
    </section>
    <section class="content-panel mb-4" aria-labelledby="photos-heading">
        <h2 id="photos-heading">Profile photographs</h2><p>Choose uploaded images. You can upload new photographs in the media library.</p>
        <div class="settings-grid">
            @foreach(['portrait_image_id'=>'Profile portrait','first_image_id'=>'First introduction photograph','second_image_id'=>'Second introduction photograph','tall_image_id'=>'Tall introduction photograph'] as $column=>$label)
                @include('admin.partials.image-select', ['field'=>'profile.'.$column,'label'=>$label,'value'=>$profile->$column])
            @endforeach
        </div>
    </section>
    <section class="content-panel mb-4" aria-labelledby="branding-heading">
        <h2 id="branding-heading">Website branding</h2>
        <div class="settings-grid">
            @include('admin.partials.text-field', ['field'=>'site.site_title','label'=>'Website title','value'=>$site->site_title,'required'=>true,'class'=>'full-width'])
            @include('admin.partials.image-select', ['field'=>'site.logo_image_id','label'=>'Logo','value'=>$site->logo_image_id])
            @include('admin.partials.image-select', ['field'=>'site.favicon_image_id','label'=>'Favicon','value'=>$site->favicon_image_id])
            @include('admin.partials.image-select', ['field'=>'site.social_image_id','label'=>'Social profile image','value'=>$site->social_image_id])
        </div>
    </section>
    <section class="content-panel mb-4" aria-labelledby="contact-heading">
        <h2 id="contact-heading">Contact information</h2>
        <div class="settings-grid">
            @include('admin.partials.text-field', ['field'=>'site.phone','label'=>'Main phone number','value'=>$site->phone,'max'=>50,'type'=>'tel'])
            @include('admin.partials.text-field', ['field'=>'site.email','label'=>'Public email address','value'=>$site->email,'type'=>'email'])
            @include('admin.partials.text-field', ['field'=>'site.additional_phones_text','label'=>'Additional phone numbers','value'=>implode("\n",$site->additional_phones ?? []),'type'=>'textarea','max'=>2000,'hint'=>'Enter one phone number per line.'])
            @include('admin.partials.text-field', ['field'=>'site.whatsapp_number','label'=>'WhatsApp number','value'=>$site->whatsapp_number,'max'=>16,'type'=>'tel','hint'=>'Include the country code, for example +8801712345678.'])
            @include('admin.partials.text-field', ['field'=>'site.address','label'=>'Public address','value'=>$site->address,'type'=>'textarea','class'=>'full-width','max'=>5000])
        </div>
    </section>
    <section class="content-panel mb-4" aria-labelledby="social-heading">
        <h2 id="social-heading">Social links</h2><p>Use complete HTTPS links. Leave unused accounts empty.</p>
        <div class="settings-grid">
            @foreach(['facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','youtube'=>'YouTube channel'] as $key=>$label)
                @include('admin.partials.text-field', ['field'=>'site.social_links.'.$key,'label'=>$label,'value'=>$site->social_links[$key] ?? null,'type'=>'url','max'=>2000])
            @endforeach
        </div>
    </section>
    <section class="content-panel mb-4" aria-labelledby="seo-heading">
        <h2 id="seo-heading">Search engine information</h2>
        <div class="settings-grid">
            @include('admin.partials.text-field', ['field'=>'site.seo_title','label'=>'Search result title','value'=>$site->seo_title,'class'=>'full-width','hint'=>'A concise title describing the doctor and practice.'])
            @include('admin.partials.text-field', ['field'=>'site.seo_description','label'=>'Search result description','value'=>$site->seo_description,'type'=>'textarea','class'=>'full-width','max'=>2000])
            @include('admin.partials.text-field', ['field'=>'site.seo_keywords','label'=>'Keywords','value'=>$site->seo_keywords,'class'=>'full-width','max'=>2000,'hint'=>'Optional, separated by commas.'])
        </div>
    </section>
    <div class="settings-save"><span class="text-secondary small">Fields marked * are required.</span><button class="btn btn-success px-4" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Save settings</button></div>
</form>
@endsection
