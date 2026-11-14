@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')
<section class="dashboard-heading">
    <h1><i class="bi bi-activity" aria-hidden="true"></i> Admin Dashboard</h1>
    <p>Medical Module Management Control</p>
</section>
<section class="dashboard-grid" aria-label="Website content totals">
    @foreach($cards as $card)
    <a class="stat-tile tile-{{ $card['color'] }}" href="{{ array_key_exists($card['key'], config('content_editor.modules')) ? route('admin.content.index', $card['key']) : route('admin.modules.show', $card['key']) }}" data-module="{{ $card['key'] }}">
        <span class="stat-icon"><i class="bi bi-{{ $card['icon'] }}" aria-hidden="true"></i></span>
        <span><span class="stat-count">{{ $card['count'] }}</span><span class="stat-label">{{ $card['card'] }}</span></span>
    </a>
    @endforeach
</section>
@endsection
