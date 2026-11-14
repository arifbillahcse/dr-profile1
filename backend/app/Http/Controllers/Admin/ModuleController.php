<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ModuleController extends Controller
{
    public function __invoke(string $module): View|RedirectResponse
    {
        if ($module === 'review') {
            return to_route('admin.reviews.index');
        }
        if ($module === 'setting') {
            return to_route('admin.settings.edit');
        }

        if (array_key_exists($module, config('content_editor.modules'))) {
            return to_route('admin.content.index', $module);
        }

        $definition = config('admin.modules')[$module] ?? null;
        abort_if($definition === null, 404);

        return view('admin.module', [
            'moduleKey' => $module,
            'module' => $definition,
            'count' => $definition['model']::count(),
        ]);
    }
}
