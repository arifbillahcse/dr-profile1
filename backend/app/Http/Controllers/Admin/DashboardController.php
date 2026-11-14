<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $modules = config('admin.modules');
        $cards = collect(config('admin.cards'))->map(function (string $key) use ($modules): array {
            $module = $modules[$key];

            return [...$module, 'key' => $key, 'count' => $module['model']::count()];
        });

        return view('admin.dashboard', ['cards' => $cards]);
    }
}
