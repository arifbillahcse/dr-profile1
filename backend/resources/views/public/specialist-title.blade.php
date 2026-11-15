@if(str_ends_with($specialist->title, ' Consultation'))
    {{ substr($specialist->title, 0, -strlen(' Consultation')) }} <br class="d-md-none">Consultation
@else
    {{ $specialist->title }}
@endif
