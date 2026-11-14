<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Database\Factories\AwardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['title', 'organization', 'year', 'description', 'image_id', 'sort_order', 'is_active'])]
class Award extends OrderedContent
{
    /** @use HasFactory<AwardFactory> */
    use HasFactory, HasImage;
}
