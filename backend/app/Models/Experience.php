<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['institution', 'position', 'started_on', 'ended_on', 'period_label', 'is_current', 'description', 'image_id', 'sort_order', 'is_active'])]
class Experience extends OrderedContent
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory, HasImage;

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'started_on' => 'date',
            'ended_on' => 'date',
            'is_current' => 'boolean',
        ]);
    }
}
