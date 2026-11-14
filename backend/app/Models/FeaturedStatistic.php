<?php

namespace App\Models;

use Database\Factories\FeaturedStatisticFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['label', 'value', 'suffix', 'icon', 'sort_order', 'is_active'])]
class FeaturedStatistic extends OrderedContent
{
    /** @use HasFactory<FeaturedStatisticFactory> */
    use HasFactory;
}
