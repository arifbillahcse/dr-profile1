<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['qualification', 'institution', 'year', 'description', 'image_id', 'sort_order', 'is_active'])]
class Education extends OrderedContent
{
    protected $table = 'educations';

    /** @use HasFactory<EducationFactory> */
    use HasFactory, HasImage;
}
