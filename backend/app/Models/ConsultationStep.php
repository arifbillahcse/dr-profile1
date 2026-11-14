<?php

namespace App\Models;

use Database\Factories\ConsultationStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['title', 'description', 'icon', 'sort_order', 'is_active'])]
class ConsultationStep extends OrderedContent
{
    /** @use HasFactory<ConsultationStepFactory> */
    use HasFactory;
}
