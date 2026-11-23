@if($memberships->isNotEmpty())
<section class="introduction-memberships" aria-label="Professional memberships">
    <h3>Professional memberships</h3>
    <ul>@foreach($memberships as $membership)
        <li><i class="fas fa-certificate" aria-hidden="true"></i><span>{{ $membership }}</span></li>
    @endforeach</ul>
</section>
@endif
