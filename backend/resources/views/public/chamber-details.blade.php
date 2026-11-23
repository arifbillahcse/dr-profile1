<h3 class="practice-chamber-name">{{ $chamber->name }}</h3>
<address class="practice-chamber-address">{{ $chamber->address }}</address>
<div class="practice-chamber-hours">
    @foreach($chamber->schedules as $schedule)
    <span><i class="far fa-clock" aria-hidden="true"></i>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$schedule->day_of_week] }}: {{ substr($schedule->opens_at, 0, 5) }}–{{ substr($schedule->closes_at, 0, 5) }}</span>
    @endforeach
    @if($chamber->schedules->isNotEmpty())<small>{{ $chamber->timezone }}</small>@endif
</div>
<div class="practice-chamber-contacts">
    @foreach(array_filter([$chamber->phone, ...($chamber->additional_phones ?? [])]) as $number)
    @if($dial($number))<a href="{{ $dial($number) }}"><i class="fas fa-phone-alt" aria-hidden="true"></i>{{ $number }}</a>@endif
    @endforeach
    @if($chamber->email)<a href="mailto:{{ $chamber->email }}"><i class="far fa-envelope" aria-hidden="true"></i>{{ $chamber->email }}</a>@endif
    @if($chamber->map_url)<a href="{{ $chamber->map_url }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-map-marker-alt" aria-hidden="true"></i>View location</a>@endif
</div>
