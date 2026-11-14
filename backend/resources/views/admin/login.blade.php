<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin sign in | {{ $adminSiteTitle }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/adminlte.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/admin.js') }}" defer></script>
</head>
<body class="login-page">
<main class="auth-card">
    <div class="auth-brand"><span class="admin-mark" aria-hidden="true">A</span><span>Admin</span></div>
    <p class="auth-practice">{{ $adminSiteTitle }}</p>
    <h1>Welcome back</h1>
    <p class="text-secondary">Sign in to manage your website.</p>
    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert" id="login-errors">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf
        <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="username" autofocus @if($errors->any()) aria-describedby="login-errors" @endif></div>
        <div class="mb-4"><label class="form-label" for="password">Password</label><div class="password-input"><input class="form-control" id="password" type="password" name="password" required maxlength="1024" autocomplete="current-password"><button type="button" data-toggle-password="password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
        <button class="btn btn-success auth-submit w-100" type="submit">Sign in <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></button>
    </form>
    <p class="auth-note">Authorized administrators only.</p>
</main>
</body>
</html>
