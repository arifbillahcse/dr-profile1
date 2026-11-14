<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaImageController extends Controller
{
    public function __invoke(MediaAsset $media): StreamedResponse
    {
        abort_unless($media->disk === 'public' && preg_match('~^uploads/[0-9]{4}/[0-9]{2}/[a-f0-9-]{36}\\.webp$~D', $media->path), 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, null, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
    }
}
