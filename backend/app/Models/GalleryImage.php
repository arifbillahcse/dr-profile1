<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Database\Factories\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['title', 'caption', 'image_id', 'sort_order', 'is_active'])]
class GalleryImage extends OrderedContent
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory, HasImage;
}
