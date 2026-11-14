<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') | {{ $adminSiteTitle }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/adminlte.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ asset('vendor/adminlte/adminlte.js') }}" defer></script>
    <script src="{{ asset('js/admin.js') }}" defer></script>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body" aria-label="Account navigation">
        <div class="container-fluid">
            <button class="nav-link border-0 bg-transparent" data-lte-toggle="sidebar" type="button" aria-label="Toggle navigation" aria-controls="admin-sidebar" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a class="nav-link" href="{{ route('public.home') }}" target="_blank" rel="noopener noreferrer" aria-label="View website"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">View website</span></a>
                <span class="account-name d-none d-sm-inline">{{ auth()->user()->name }}</span>
                <button class="nav-link border-0 bg-transparent" type="button" data-fullscreen aria-label="Toggle fullscreen"><i class="bi bi-fullscreen" aria-hidden="true"></i></button>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="logout-button" type="submit">Log Out</button></form>
            </div>
        </div>
    </nav>
    <aside id="admin-sidebar" class="app-sidebar shadow" data-bs-theme="dark">
        <div class="sidebar-brand"><a href="{{ route('admin.dashboard') }}" class="brand-link">@if($adminLogo)<img class="admin-brand-image" src="{{ $adminLogo->url() }}" alt="{{ $adminSiteTitle }}">@else<span class="admin-mark" aria-hidden="true">A</span>@endif<span class="brand-text">Admin</span></a></div>
        <div class="sidebar-wrapper">
            <nav aria-label="Administration">
                <ul class="nav sidebar-menu flex-column" role="list">
                    <li class="nav-item"><a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><i class="nav-icon bi bi-speedometer2" aria-hidden="true"></i><p>Dashboard</p></a></li>
                    @foreach(config('admin.modules') as $key => $section)
                    @php($sectionActive = request()->route('module') === $key || ($key === 'setting' && request()->routeIs('admin.settings.*')) || ($key === 'review' && request()->routeIs('admin.reviews.*')))
                    @php($sectionUrl = $key === 'setting' ? route('admin.settings.edit') : ($key === 'review' ? route('admin.reviews.index') : (array_key_exists($key, config('content_editor.modules')) ? route('admin.content.index', $key) : route('admin.modules.show', $key))))
                    <li class="nav-item"><a href="{{ $sectionUrl }}" class="nav-link {{ $sectionActive ? 'active' : '' }}" @if($sectionActive) aria-current="page" @endif><i class="nav-icon bi bi-{{ $section['icon'] }}" aria-hidden="true"></i><p>{{ $section['label'] }}</p></a></li>
                    @endforeach
                    <li class="nav-item"><a href="{{ route('admin.media.index') }}" class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}" @if(request()->routeIs('admin.media.*')) aria-current="page" @endif><i class="nav-icon bi bi-folder2-open" aria-hidden="true"></i><p>Media library</p></a></li>
                </ul>
            </nav>
            <a class="password-link {{ request()->routeIs('admin.password.*') ? 'active' : '' }}" href="{{ route('admin.password.edit') }}"><i class="bi bi-key" aria-hidden="true"></i> Change password</a>
        </div>
    </aside>
    <main id="main-content" class="app-main" tabindex="-1">
        <div class="admin-content">
            @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
