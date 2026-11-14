@extends('admin.layout')
@section('title', $module['label'])
@section('content')
<section class="dashboard-heading"><h1><i class="bi bi-{{ $module['icon'] }}" aria-hidden="true"></i> {{ $module['label'] }}</h1><p>Website content overview</p></section>
<section class="content-panel">
    <div class="module-total">{{ $count }} <span>{{ Str::plural('record', $count) }}</span></div>
    <h2>Content management is coming soon</h2>
    <p>Editing for this section is not available yet. Existing records are included in your dashboard totals.</p>
    <a class="btn btn-outline-success" href="{{ route('admin.dashboard') }}">Back to dashboard</a>
</section>
@endsection
