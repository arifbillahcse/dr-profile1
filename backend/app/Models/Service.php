<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'slug', 'description', 'icon', 'image_id', 'sort_order', 'is_active'])]
class Service extends OrderedContent
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasImage;
}
