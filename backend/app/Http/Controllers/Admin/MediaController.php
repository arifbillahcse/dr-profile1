<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaRequest;
use App\Models\MediaAsset;
use App\Services\MediaLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '';
        $query = MediaAsset::where('disk', 'public')->latest('id');
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('original_name', 'like', '%'.$search.'%')->orWhere('alt_text', 'like', '%'.$search.'%');
            });
        }

        return view('admin.media.index', ['mediaItems' => $query->paginate(12)->withQueryString(), 'search' => $search]);
    }

    public function store(MediaRequest $request, MediaLibrary $library): RedirectResponse
    {
        $media = $library->store($request->file('image'), $request->validated('alt_text'), $request->user());

        return to_route('admin.media.edit', $media)->with('status', 'Image uploaded. You can now choose it in website settings.');
    }

    public function edit(MediaAsset $media, MediaLibrary $library): View
    {
        $library->assertManaged($media);

        return view('admin.media.edit', ['media' => $media, 'references' => $library->references($media)]);
    }

    public function update(MediaRequest $request, MediaAsset $media, MediaLibrary $library): RedirectResponse
    {
        $library->update($media, $request->file('image'), $request->validated('alt_text'));

        return to_route('admin.media.edit', $media)->with('status', 'Image details saved.');
    }

    public function destroy(MediaAsset $media, MediaLibrary $library): RedirectResponse
    {
        $library->delete($media);

        return to_route('admin.media.index')->with('status', 'Unused image deleted.');
    }
}
