<?php

namespace App\Models;

use Database\Factories\SpecialistHighlightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['title', 'description', 'icon', 'sort_order', 'is_active'])]
class SpecialistHighlight extends OrderedContent
{
    /** @use HasFactory<SpecialistHighlightFactory> */
    use HasFactory;
}
