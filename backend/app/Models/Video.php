<?php

namespace App\Models;

use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'youtube_id', 'description', 'thumbnail_image_id', 'sort_order', 'is_active'])]
class Video extends OrderedContent
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'thumbnail_image_id');
    }
}
