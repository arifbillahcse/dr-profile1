<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.password');
    }

    public function update(PasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password'), 'remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.login')->with('status', 'Your password was updated. Please sign in again.');
    }
}
