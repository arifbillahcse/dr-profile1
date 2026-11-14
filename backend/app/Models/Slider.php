<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Database\Factories\SliderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['title', 'caption', 'image_id', 'sort_order', 'is_active'])]
class Slider extends OrderedContent
{
    /** @use HasFactory<SliderFactory> */
    use HasFactory, HasImage;
}
