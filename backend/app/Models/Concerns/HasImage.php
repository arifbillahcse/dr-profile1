<?php

namespace App\Models\Concerns;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasImage
{
    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }
}
