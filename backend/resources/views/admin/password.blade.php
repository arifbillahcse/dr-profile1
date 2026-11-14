@extends('admin.layout')
@section('title', 'Change password')
@section('content')
<section class="dashboard-heading"><h1><i class="bi bi-key" aria-hidden="true"></i> Change password</h1><p>Keep your administrator account secure.</p></section>
<section class="content-panel password-panel">
    <p>Use at least 12 characters, including uppercase and lowercase letters, a number and a symbol. You will be signed out after updating your password.</p>
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.password.update') }}">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label" for="current_password">Current password</label><input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required maxlength="1024"></div>
        <div class="mb-3"><label class="form-label" for="new_password">New password</label><input class="form-control" type="password" id="new_password" name="password" autocomplete="new-password" required minlength="12" maxlength="1024"></div>
        <div class="mb-4"><label class="form-label" for="password_confirmation">Confirm new password</label><input class="form-control" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required minlength="12" maxlength="1024"></div>
        <button class="btn btn-success" type="submit">Update password</button>
    </form>
</section>
@endsection
